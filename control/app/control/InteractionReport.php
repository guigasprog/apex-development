<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Form\THidden;

// --- Imports para Dompdf ---
use Dompdf\Dompdf;
use Dompdf\Options;

class InteractionReport extends TPage
{
    protected $form; // Formulário para os botões
    protected $datagrid_searches;
    protected $datagrid_views;

    // ... (__construct e onReload permanecem os mesmos) ...
    public function __construct()
    {
        parent::__construct();
        $this->form = new BootstrapFormBuilder('form_interaction_report');
        $this->form->setFormTitle('Relatório de Interações');
        $this->form->addFields( [ new THidden('fix_foreach_warning') ] );
        $this->form->addAction('Buscas (PDF)', new TAction([$this, 'onExportSearchesPDF']), 'fa:file-pdf red');
        $this->form->addAction('Visualizações (PDF)', new TAction([$this, 'onExportViewsPDF']), 'fa:file-pdf red');
        $this->datagrid_searches = new TDataGrid; $this->datagrid_searches->style = 'width: 100%'; $this->datagrid_searches->addColumn(new TDataGridColumn('texto_busca', 'Termo Buscado', 'left')); $this->datagrid_searches->addColumn(new TDataGridColumn('total', 'Quantidade', 'center', '20%')); $this->datagrid_searches->createModel();
        $panel_searches = new TPanelGroup('Buscas Mais Populares'); $panel_searches->add($this->datagrid_searches);
        $this->datagrid_views = new TDataGrid; $this->datagrid_views->style = 'width: 100%'; $this->datagrid_views->addColumn(new TDataGridColumn('nome', 'Produto', 'left')); $this->datagrid_views->addColumn(new TDataGridColumn('total', 'Visualizações', 'center', '20%')); $this->datagrid_views->createModel();
        $panel_views = new TPanelGroup('Produtos Mais Vistos'); $panel_views->add($this->datagrid_views);
        $container = new TVBox; $container->style = 'width: 100%'; $container->add(new TXMLBreadCrumb('menu-tenant.xml', __CLASS__)); $container->add($this->form); $container->add($panel_searches); $container->add($panel_views);
        parent::add($container);
    }
    public function onReload($param = NULL) { try { TTransaction::open(TSession::getValue('tenant_connection')); $conn = TTransaction::get(); $sql_searches = "SELECT texto_busca, COUNT(*) as total FROM produto_interacoes WHERE tipo = 'search' AND texto_busca IS NOT NULL GROUP BY texto_busca ORDER BY total DESC LIMIT 50"; $result_searches = $conn->query($sql_searches); $items_searches = $result_searches->fetchAll(PDO::FETCH_OBJ); $this->datagrid_searches->clear(); if ($items_searches) { foreach ($items_searches as $item) { $this->datagrid_searches->addItem($item); } } $sql_views = "SELECT p.nome, COUNT(i.id) as total FROM produto_interacoes i JOIN produtos p ON p.id = i.produto_id WHERE i.tipo = 'view' GROUP BY p.id, p.nome ORDER BY total DESC LIMIT 50"; $result_views = $conn->query($sql_views); $items_views = $result_views->fetchAll(PDO::FETCH_OBJ); $this->datagrid_views->clear(); if ($items_views) { foreach ($items_views as $item) { $this->datagrid_views->addItem($item); } } TTransaction::close(); } catch (Exception $e) { new TMessage('error', $e->getMessage()); TTransaction::rollback(); } }

    private function getSearchesData() { TTransaction::open(TSession::getValue('tenant_connection')); $conn = TTransaction::get(); $sql_searches = "SELECT texto_busca, COUNT(*) as total FROM produto_interacoes WHERE tipo = 'search' AND texto_busca IS NOT NULL GROUP BY texto_busca ORDER BY total DESC LIMIT 500"; $result = $conn->query($sql_searches); $data = $result->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $data; }
    private function getViewsData() { TTransaction::open(TSession::getValue('tenant_connection')); $conn = TTransaction::get(); $sql_views = "SELECT p.nome, COUNT(i.id) as total FROM produto_interacoes i JOIN produtos p ON p.id = i.produto_id WHERE i.tipo = 'view' GROUP BY p.id, p.nome ORDER BY total DESC LIMIT 500"; $result = $conn->query($sql_views); $data = $result->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $data; }

    // --- MÉTODO DE EXPORTAÇÃO PDF ATUALIZADO (BUSCAS) ---
    public function onExportSearchesPDF($param)
    {
        try {
            $data = $this->getSearchesData();
            if (empty($data)) {
                 new TMessage('info', 'Não há dados de busca para exportar.'); return;
            }

            // Gera o HTML
            $html = "<style>
                        body { font-family: sans-serif; font-size: 10px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .center { text-align: center; }
                        h1 { text-align: center; font-size: 14px; margin-bottom: 15px;}
                     </style>";
            $html .= "<h1>Buscas Mais Populares</h1>";
            $html .= "<table>";
            $html .= "<thead><tr><th>Termo Buscado</th><th class='center'>Quantidade</th></tr></thead>";
            $html .= "<tbody>";
            foreach ($data as $row) {
                $html .= "<tr>";
                $html .= "<td>" . htmlspecialchars($row['texto_busca']) . "</td>";
                $html .= "<td class='center'>" . $row['total'] . "</td>";
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";

            // Configura Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false); // Defina como true se precisar carregar imagens externas

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait'); // 'portrait' ou 'landscape'
            $dompdf->render();

            $filename = 'buscas_populares.pdf';
            // Força o download
            $dompdf->stream($filename, ["Attachment" => true]);

            exit; // Interrompe a execução

        } catch (Exception $e) {
            new TMessage('error', 'Erro ao gerar PDF: ' . $e->getMessage());
            if (TTransaction::isActive()) TTransaction::rollback();
        }
    }

    // --- MÉTODO DE EXPORTAÇÃO PDF ATUALIZADO (VISUALIZAÇÕES) ---
    public function onExportViewsPDF($param)
    {
         try {
            $data = $this->getViewsData();
            if (empty($data)) {
                 new TMessage('info', 'Não há dados de visualização para exportar.'); return;
            }

            // Gera o HTML
            $html = "<style>
                        body { font-family: sans-serif; font-size: 10px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; word-wrap: break-word; } /* break-word para nomes longos */
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .center { text-align: center; }
                        h1 { text-align: center; font-size: 14px; margin-bottom: 15px;}
                     </style>";
            $html .= "<h1>Produtos Mais Vistos</h1>";
            $html .= "<table>";
            $html .= "<thead><tr><th>Produto</th><th class='center'>Visualizações</th></tr></thead>";
            $html .= "<tbody>";
            foreach ($data as $row) {
                $html .= "<tr>";
                $html .= "<td>" . htmlspecialchars($row['nome']) . "</td>";
                $html .= "<td class='center'>" . $row['total'] . "</td>";
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";

            // Configura Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = 'produtos_mais_vistos.pdf';
            $dompdf->stream($filename, ["Attachment" => true]);

            exit;

        } catch (Exception $e) {
            new TMessage('error', 'Erro ao gerar PDF: ' . $e->getMessage());
            if (TTransaction::isActive()) TTransaction::rollback();
        }
    }

    // ... (show permanece o mesmo) ...
    public function show() { $this->onReload(); parent::show(); }
}