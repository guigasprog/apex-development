<?php
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Registry\TSession;
use Adianti\Base\AdiantiStandardListTrait;

class TenantList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    
    use AdiantiStandardListTrait;

    public function __construct()
    {
        parent::__construct();

        // ** VERIFICAÇÃO DE SEGURANÇA **
        if (TSession::getValue('is_super_admin') !== true)
        {
            new Adianti\Widget\Dialog\TMessage('error', 'Acesso negado');
            AdiantiCoreApplication::gotoPage('LoginForm'); // Redireciona para o login
            return;
        }
        
        $this->setDatabase('database'); // Conecta no db_master
        $this->setActiveRecord('Tenant');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);
        
        $this->addFilterField('nome_loja', 'like', 'nome_loja');
        $this->addFilterField('schema_name', 'like', 'schema_name');
        
        // Formulário de busca usando TForm e TPanelGroup
        $this->form = new TForm('form_search_tenant');
        $panel_form = new TPanelGroup('Gerenciar Tenants');
        
        $nome_loja = new TEntry('nome_loja');
        $schema_name = new TEntry('schema_name');

        // Adicionando campos ao formulário de forma compatível
        $row1 = $this->form->addFields([new TLabel('Nome da Loja')], [$nome_loja]);
        $row2 = $this->form->addFields([new TLabel('Schema')], [$schema_name]);
        $row1->layout = ['col-sm-2', 'col-sm-10'];
        $row2->layout = ['col-sm-2', 'col-sm-10'];
        
        $panel_form->add($this->form);
        
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Novo', new TAction(['TenantForm', 'onEdit']), 'fa:plus green');
        
        // Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        
        $col_id = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_nome = new TDataGridColumn('nome_loja', 'Nome da Loja', 'left');
        $col_schema = new TDataGridColumn('schema_name', 'Schema', 'left');
        $col_status = new TDataGridColumn('status', 'Status', 'center');
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_schema);
        $this->datagrid->addColumn($col_status);

        $action_edit = new TDataGridAction(['TenantForm', 'onEdit'], ['id'=>'{id}']);
        $action_del = new TDataGridAction([$this, 'onDelete'], ['id'=>'{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_del, 'Excluir', 'fa:trash-alt red');
        
        $this->datagrid->createModel();
        
        // Page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel_datagrid = new TPanelGroup;
        $panel_datagrid->add($this->datagrid);
        $panel_datagrid->addFooter($this->pageNavigation);
        
        // Container vertical para organizar os painéis
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($panel_form);
        $container->add($panel_datagrid);
        
        parent::add($container);
    }
}