<?php
use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;

/**
 * Listagem de Categorias do Tenant
 */
class CategoriesList extends TStandardList
{
    protected $form;
    protected $datagrid;
    
    public function __construct()
    {
        parent::__construct();
        
        // Conecta ao banco de dados do tenant logado
        parent::setDatabase(TSession::getValue('tenant_connection'));
        
        parent::setActiveRecord('Categoria');
        parent::setDefaultOrder('id', 'asc');
        parent::addFilterField('nome', 'like', 'nome');
        
        // Cria o formulário de busca
        $this->form = new BootstrapFormBuilder('form_search_category');
        $this->form->setFormTitle('Buscar Categorias');
        
        $nome = new TEntry('nome');
        $this->form->addFields( [new TLabel('Nome')], [$nome] );
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Nova Categoria', new TAction(['CategoryForm', 'onEdit']), 'fa:plus green');
        
        // Cria a datagrid manualmente para máxima compatibilidade
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';
        
        $col_id   = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_nome = new TDataGridColumn('nome', 'Nome', 'left');
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        
        // Adiciona ações de editar e deletar
        $action_edit = new TDataGridAction(['CategoryForm', 'onEdit'], ['id'=>'{id}']);
        $action_del  = new TDataGridAction([$this, 'onDelete'], ['id'=>'{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_del, 'Deletar', 'fa:trash-alt red');
        
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