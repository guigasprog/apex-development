<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Util\TActionLink; // Adicionado
use Adianti\Widget\Util\TXMLBreadCrumb;

/**
 * Exibe as vendas de uma Loja (Tenant) específica
 */
class StoreSalesList extends TPage
{
    public function __construct($param)
    {
        parent::__construct();

        if (isset($param['tenant_id']))
        {
            TTransaction::open('permission');
            $tenant = new Tenant($param['tenant_id']);
            $connection_name = $tenant->db_connection_name;
            $tenant_name = $tenant->nome_loja;
            TTransaction::close();

            if (!$connection_name) {
                throw new Exception('Loja sem conexão de banco de dados configurada.');
            }

            TTransaction::open($connection_name);
            
            $repository = new TRepository('Pedido');
            $criteria = new TCriteria;
            $criteria->setProperty('limit', 10);
            $criteria->setProperty('order', 'id desc');
            $pedidos = $repository->load($criteria);

            $grid = new TDataGrid;
            $grid->style = 'width: 100%';
            $col_id = new TDataGridColumn('id', 'Pedido ID', 'center');
            $col_data = new TDataGridColumn('data_pedido', 'Data', 'center');
            $col_status = new TDataGridColumn('status', 'Status', 'center');
            $col_total = new TDataGridColumn('total', 'Total', 'right');

            $col_total->setTransformer(function($value){
                return 'R$ ' . number_format($value, 2, ',', '.');
            });
            
            $col_status->setTransformer(function($value){
                $class = 'secondary';
                if ($value == 'pago') { $class = 'info'; }
                if ($value == 'enviado') { $class = 'primary'; }
                if ($value == 'entregue') { $class = 'success'; }
                if ($value == 'cancelado') { $class = 'danger'; }
                $label = new TElement('span');
                $label->class = "label label-{$class}";
                $label->add($value);
                return $label;
            });
            
            $grid->addColumn($col_id);
            $grid->addColumn($col_data);
            $grid->addColumn($col_status);
            $grid->addColumn($col_total);
            
            $grid->createModel();
            $grid->addItems($pedidos);
            
            TTransaction::close();

            $panel = TPanelGroup::pack("Últimas 10 Vendas: {$tenant_name}", $grid);
            
            // --- INÍCIO DA CORREÇÃO ---
            // Cria a ação e o link de "Voltar" manualmente
            $action_back = new TAction(['StoresList', 'onReload']);
            $link_back = new TActionLink('Voltar para Lojas', $action_back, null, null, null, 'fa:arrow-left blue');
            $panel->addFooter(TPanelGroup::pack('', $link_back));
            // --- FIM DA CORREÇÃO ---

            $container = new TVBox;
            $container->style = 'width: 100%';
            $container->add(new TXMLBreadCrumb('menu-admin.xml', 'StoresList'));
            $container->add($panel);

            parent::add($container);
        }
    }

    public function onLoad($param)
    {
        
    }
}