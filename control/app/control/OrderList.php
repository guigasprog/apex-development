<?php
use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;

class OrderList extends TStandardList
{
    protected $form;
    protected $datagrid;
    
    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase(TSession::getValue('tenant_connection'));
        parent::setActiveRecord('Pedido');
        parent::setDefaultOrder('id', 'desc'); // Pedidos mais novos primeiro
        parent::addFilterField('status', '=', 'status');
        
        $this->form = new BootstrapFormBuilder('form_search_order');
        $this->form->setFormTitle('Buscar Pedidos');
        
        $status = new TCombo('status');
        $status->addItems([
            'pendente' => 'Pendente',
            'pago' => 'Pago',
            'enviado' => 'Enviado',
            'entregue' => 'Entregue',
            'cancelado' => 'Cancelado'
        ]);
        $status->setSize('100%');

        $this->form->addFields( [new TLabel('Status')], [$status] );
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';
        
        $col_id      = new TDataGridColumn('id', '#', 'center', '10%');
        $col_cliente = new TDataGridColumn('cliente_id', 'Cliente', 'left');
        $col_data    = new TDataGridColumn('data_pedido', 'Data', 'center');
        $col_status  = new TDataGridColumn('status', 'Status', 'center');
        $col_total   = new TDataGridColumn('total', 'Total', 'right');

        $col_cliente->setTransformer(function($cliente_id){
            // NOTA: Para listas muito grandes, uma VIEW no banco seria mais performática
            TTransaction::open(TSession::getValue('tenant_connection'));
            $cliente = Cliente::find($cliente_id);
            TTransaction::close();
            return $cliente ? $cliente->nome : 'N/A';
        });

        $col_total->setTransformer(function($value) {
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

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_cliente);
        $this->datagrid->addColumn($col_data);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_total);
        
        $action_edit = new TDataGridAction(['OrderForm', 'onEdit'], ['id'=>'{id}']);
        $this->datagrid->addAction($action_edit, 'Visualizar/Editar', 'fa:edit blue');
        
        $this->datagrid->createModel();
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu-tenant.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
}