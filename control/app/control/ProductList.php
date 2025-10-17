<?php
use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
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
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;

// REMOVIDO: A linha "use Adianti\Util\AdiantiStringConversion;" foi apagada.

class ProductList extends TStandardList
{
    protected $form;
    protected $datagrid;
    
    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase(TSession::getValue('tenant_connection'));
        parent::setActiveRecord('Produto');
        parent::setDefaultOrder('id', 'asc');
        parent::addFilterField('nome', 'like', 'nome');
        
        $this->form = new BootstrapFormBuilder('form_search_product');
        $this->form->setFormTitle('Buscar Produtos');
        
        $nome = new TEntry('nome');
        $this->form->addFields( [new TLabel('Nome')], [$nome] );
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Novo Produto', new TAction(['ProductForm', 'onEdit']), 'fa:plus green');
        
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';
        
        $col_id    = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_nome  = new TDataGridColumn('nome', 'Nome', 'left');
        $col_tipo  = new TDataGridColumn('tipo', 'Tipo', 'center');
        $col_preco = new TDataGridColumn('preco', 'Preço', 'right');
        
        $col_preco->setTransformer(function($value) {
            if (is_numeric($value)) {
                return 'R$ ' . number_format($value, 2, ',', '.');
            }
            return $value;
        });

        $col_tipo->setTransformer(function($value) {
            if ($value == 'PRODUTO') {
                $class = 'warning';
                $label = 'Produto';
            } 
            else if ($value == 'SERVICO') {
                $class = 'info';
                $label = 'Serviço';
            }
            else {
                return $value;
            }
            
            $badge = new TElement('span');
            $badge->class = "label label-{$class}";
            
            // CORRIGIDO: Usando a função nativa do PHP
            $badge->add(strtoupper($label));
            return $badge;
        });
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_tipo);
        $this->datagrid->addColumn($col_preco);
        
        $action_edit = new TDataGridAction(['ProductForm', 'onEdit'], ['id'=>'{id}']);
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