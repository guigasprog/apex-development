<?php
use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Util\TXMLBreadCrumb;

/**
 * Listagem de Lojistas (usuários de tenants)
 */
class ShopkeepersList extends TStandardList
{
    protected $datagrid;

    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase('permission');
        parent::setActiveRecord('SystemUser');
        parent::setDefaultOrder('id', 'asc');

        // FILTRO: Exibe apenas usuários que pertencem a um tenant (não-admins)
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tenant_id', 'IS NOT', NULL));
        parent::setCriteria($criteria);

        // Cria a datagrid usando o TDataGrid base para compatibilidade
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);
        
        // Cria as colunas
        $col_id      = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_name    = new TDataGridColumn('name', 'Nome', 'left');
        $col_login   = new TDataGridColumn('login', 'Login', 'left');
        $col_email   = new TDataGridColumn('email', 'Email', 'left');
        $col_tenant  = new TDataGridColumn('tenant_id', 'Loja', 'left');

        // Adiciona um "Transformer" para mostrar o nome da loja em vez do ID
        $col_tenant->setTransformer( function($tenant_id, $object, $row) {
            if ($tenant_id) {
                // Abre uma transação rápida para buscar o nome do tenant
                // NOTA: Para listas muito grandes, uma VIEW no banco seria mais performática
                TTransaction::open('permission');
                $tenant = Tenant::find($tenant_id);
                TTransaction::close();
                return $tenant ? $tenant->nome_loja : 'N/A';
            }
            return '';
        });

        // Adiciona as colunas à datagrid
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_name);
        $this->datagrid->addColumn($col_login);
        $this->datagrid->addColumn($col_email);
        $this->datagrid->addColumn($col_tenant);
        
        $this->datagrid->createModel();
        
        // Cria a navegação da página
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel = new TPanelGroup();
        $panel->style = 'padding: 5px;';
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu-admin.xml', __CLASS__));
        $container->add($panel);
        
        parent::add($container);
    }
}