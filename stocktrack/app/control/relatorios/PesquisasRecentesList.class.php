<?php

use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Exception;

class PesquisasRecentesList extends TPage
{
    private $datagrid;
    private $pageNavigation;

    public function __construct()
    {
        parent::__construct();

        // Cria a DataGrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';

        // Adiciona as colunas
        $col_termo = new TDataGridColumn('texto_busca', 'Termo Pesquisado', 'left', '40%');
        $col_produto = new TDataGridColumn('produto_nome', 'Produto Encontrado', 'left', '40%');
        $col_data = new TDataGridColumn('created_at', 'Data da Busca', 'center', '20%');
        
        $this->datagrid->addColumn($col_termo);
        $this->datagrid->addColumn($col_produto);
        $this->datagrid->addColumn($col_data);

        // Formata a coluna de data
        $col_data->setTransformer(function($value) {
            return TDate::convertToMask($value, 'Y-m-d H:i:s', 'd/m/Y H:i');
        });
        
        // Formata a coluna de produto para destacar buscas sem resultado
        $col_produto->setTransformer(function($value, $object, $row) {
            if (empty($value)) {
                $span = new TElement('span');
                $span->class = 'badge bg-warning text-dark';
                $span->add('Nenhum produto clicado');
                return $span;
            }
            return $value;
        });

        $this->datagrid->createModel();

        // Navegação da página
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        // Monta a página
        $panel = new TPanelGroup('Análise de Pesquisas de Clientes');
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($panel);

        parent::add($vbox);
    }
    
    public function onReload($param = null)
    {
        try {
            TTransaction::open('development');

            $repository = new TRepository('ProdutoRelevancia');
            $limit = 10;
            
            $criteria = new TCriteria;
            $criteria->setProperties($param); // page, order, direction
            $criteria->setProperty('limit', $limit);
            $criteria->add(new TFilter('tipo_relevancia', '=', 'search'));
            $criteria->setProperty('order', 'created_at desc');

            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    // Adiciona o nome do produto ao objeto para exibição
                    $object->produto_nome = $object->get_produto() ? $object->get_produto()->nome : null;
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();
            $this->loaded = true;
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function show()
    {
        // Carrega a datagrid com os dados da primeira vez
        if (!$this->loaded)
        {
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }
}