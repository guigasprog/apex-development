<?php
use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreApplication; // ADICIONADO
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Util\TXMLBreadCrumb;

class StoresList extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase('permission');
        parent::setActiveRecord('Tenant');
        parent::setDefaultOrder('id', 'asc');

        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        
        $col_id      = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_name    = new TDataGridColumn('nome_loja', 'Nome da Loja', 'left');
        $col_status  = new TDataGridColumn('status', 'Status', 'center', '20%');
        $col_created = new TDataGridColumn('created_at', 'Criada em', 'center', '25%');

        $col_status->setTransformer( function($value) {
            $class = 'secondary';
            if ($value == 'ativo') { $class = 'success'; }
            if ($value == 'suspenso' || $value == 'inativo') { $class = 'danger'; }
            $label = new TElement('span');
            $label->class = "label label-{$class}";
            $label->add($value);
            return $label;
        });
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_name);
        $this->datagrid->addColumn($col_status);
        $this->datagrid->addColumn($col_created);

        $action = new TDataGridAction([$this, 'onViewSales']);
        $action->setLabel('Ver Vendas');
        $action->setImage('fa:shopping-cart green');
        $action->setField('id');
        $this->datagrid->addAction($action);

        $this->datagrid->createModel();
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel = new TPanelGroup();
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu-admin.xml', __CLASS__));
        $container->add($panel);
        
        parent::add($container);
    }

    public static function onViewSales($param)
    {
        AdiantiCoreApplication::gotoPage('StoreSalesList', 'onLoad', ['tenant_id' => $param['key']]);
    }
}