<?php
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TPageNavigation;
// Removido: use Adianti\Wrapper\BootstrapDatagridWrapper;
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

        if (TSession::getValue('is_super_admin') !== true)
        {
            new Adianti\Widget\Dialog\TMessage('error', 'Acesso negado');
            AdiantiCoreApplication::gotoPage('LoginForm');
            return;
        }

        $this->setDatabase('database');
        $this->setActiveRecord('Tenant');
        $this->setDefaultOrder('id', 'asc');
        $this->setLimit(10);

        $this->addFilterField('nome_loja', 'like', 'nome_loja');
        $this->addFilterField('schema_name', 'like', 'schema_name');
        
        // Formulário de busca
        $this->form = new TForm('form_search_tenant');
        $table = new TTable;
        $table->width = '100%';
        $this->form->add($table);

        $nome_loja = new TEntry('nome_loja');
        $schema_name = new TEntry('schema_name');
        
        $nome_loja->setSize('100%');
        $schema_name->setSize('100%');

        $row1 = $table->addRow();
        $cell_label1 = $row1->addCell(new TLabel('Nome da Loja:'));
        $cell_label1->width = '15%';
        $row1->addCell($nome_loja);

        $row2 = $table->addRow();
        $cell_label2 = $row2->addCell(new TLabel('Schema:'));
        $cell_label2->width = '15%';
        $row2->addCell($schema_name);
        
        $find_button = new TButton('find');
        $find_button->setAction(new TAction([$this, 'onSearch']), 'Buscar');
        $find_button->setImage('fa:search');
        
        $new_button = new TButton('new');
        $new_button->setAction(new TAction(['TenantForm', 'onEdit']), 'Novo');
        $new_button->setImage('fa:plus green');
        
        $panel_form = new TPanelGroup('Gerenciar Tenants');
        $panel_form->add($this->form);
        $panel_form->addFooter([$find_button, $new_button]);
        
        // --- INÍCIO DA CORREÇÃO ---

        // Datagrid
        // Correção: Usando TDataGrid diretamente, sem o wrapper
        $this->datagrid = new TDataGrid;
        // Adiciona um estilo para que a datagrid ocupe 100% da largura, como o wrapper faria
        $this->datagrid->style = 'width: 100%';
        
        // --- FIM DA CORREÇÃO ---
        
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
        
        // Container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($panel_form);
        $container->add($panel_datagrid);
        
        parent::add($container);
    }
}