<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Util\TTableWriterCSV;
use Adianti\Util\TTableWriterPDF;
use Adianti\Base\TDate;

class VendaReport extends TPage // You might want to rename this class to PedidoReport later
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        // --- Formulário de Filtro ---
        $this->form = new BootstrapFormBuilder('form_pedido_report'); // Renamed form ID
        $this->form->setFormTitle('Relatório de Pedidos por Status'); // Renamed title

        $status = new TCombo('status');
        // --- ATENÇÃO: Ajuste os status conforme sua tabela 'pedidos' ---
        $status->addItems([
            'PENDENTE'  => 'Pendente',
            'PAGO'      => 'Pago', // Example status
            'ENVIADO'   => 'Enviado',
            'ENTREGUE'  => 'Entregue',
            'CANCELADO' => 'Cancelado'
        ]);
        $status->setValue('PENDENTE'); // Default value

        $this->form->addFields( [new TLabel('Status do Pedido')], [$status] ); // Renamed label
        
        $this->form->addAction('Exportar PDF', new TAction([$this, 'onExportPDF']), 'fa:file-pdf red');
        
        // --- Container da Página ---
        $panel = new TPanelGroup;
        $panel->add($this->form);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu-tenant.xml', __CLASS__));
        $container->add($panel);

        parent::add($container);
    }

    /**
     * Busca os dados de pedidos com base no filtro
     */
    private function getPedidosData($param) // Renamed method
    {
        // Pega os dados do formulário
        $data = $this->form->getData(); 
        $status_filter = $data->status ?? null;

        TTransaction::open(TSession::getValue('tenant_connection'));
        $conn = TTransaction::get();
        
        // --- CORREÇÃO: Query ajustada para 'pedidos' e 'pedido_itens' ---
        // Ajuste os nomes das colunas (data_pedido, cliente_id, pedido_id, etc.) se necessário
        $sql = "SELECT p.id, p.data_pedido, c.nome as cliente, prod.nome as produto, pi.quantidade, (pi.quantidade * pi.preco_unitario) as subtotal, p.status
                FROM pedidos p 
                LEFT JOIN clientes c ON c.id = p.cliente_id
                LEFT JOIN pedido_itens pi ON pi.pedido_id = p.id 
                LEFT JOIN produtos prod ON prod.id = pi.produto_id 
                ";
        // --- FIM DA CORREÇÃO ---

        $where = [];
        $params = [];
        
        // Adiciona o filtro de status se selecionado
        if ($status_filter) {
            $where[] = "p.status = :status"; // Changed v.status to p.status
            $params[':status'] = $status_filter;
        }
        
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY p.data_pedido DESC, p.id ASC"; // Changed v.data_venda to p.data_pedido

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC); 
        
        TTransaction::close();
        
        // Formata os dados para o relatório
        $formatted_data = [];
        foreach ($data as $row) {
            $row['data_pedido'] = TDate::date2br($row['data_pedido']); // Changed data_venda to data_pedido
            $row['subtotal'] = 'R$ ' . number_format($row['subtotal'], 2, ',', '.');
            $formatted_data[] = $row;
        }
        
        return $formatted_data;
    }

    /**
     * Exporta Pedidos para PDF
     */
    public function onExportPDF($param)
    {
        try {
            $data = $this->getPedidosData($param); // Changed method call
            
            if (empty($data))
            {
                new TMessage('info', 'Não há dados para exportar com o filtro selecionado.');
                return;
            }

            $file = 'tmp/pedidos.pdf'; // Renamed file
            
            $writer = new TTableWriterPDF('L'); // Landscape
            $writer->addStyle('header', 'Helvetica', 9, 'B', '#D5D5D5', '#000000');
            $writer->addStyle('row', 'Helvetica', 9, '', '#FFFFFF', '#000000');
             // --- CORREÇÃO: Ajustado cabeçalho ---
            $writer->addHeader(['ID', 'Data', 'Cliente', 'Produto', 'Qtd', 'Subtotal', 'Status']);
            
            foreach ($data as $row) {
                 $writer->addRow($row);
            }

            $writer->save($file);
            TPage::openFile($file);

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}