<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TForm;

class ClientesPage extends TPage
{
    private $datagrid;
    private $pageNavigation;
    private $loaded;

    public function __construct($param = null)
    {
        parent::__construct($param);

        $this->form = new TForm('form_list_clientes');
        $panel = new TPanelGroup('Listagem de Clientes');
        $panel->style = 'width: 100%';
        $this->form->add($panel);
        
        $add_button = new TButton('add');
        $add_button->setAction(new TAction(['ClienteForm', 'onShow']), 'Adicionar');
        $add_button->setImage('fas:plus green');
        
        $panel->addFooter($add_button);
        
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%; margin-bottom: 10px;';
        
        $this->datagrid->addColumn(new TDataGridColumn('id', 'ID', 'center', '5%'));
        $this->datagrid->addColumn(new TDataGridColumn('nome', 'Nome', 'left', '40%'));
        $this->datagrid->addColumn(new TDataGridColumn('cpf', 'CPF', 'left', '15%'));
        $this->datagrid->addColumn(new TDataGridColumn('email', 'Email', 'left', '20%'));
        $this->datagrid->addColumn(new TDataGridColumn('telefone', 'Telefone', 'left', '20%'));

        $action_edit = new TDataGridAction(['ClienteForm', 'onEdit'], ['id'=>'{id}']);
        $action_del  = new TDataGridAction([$this, 'onDelete'], ['id'=>'{id}', 'nome'=>'{nome}']);
        
        $action_edit->setImage('fas:edit blue');
        $action_del->setImage('fas:trash-alt red');
        
        $this->datagrid->addAction($action_edit);
        $this->datagrid->addAction($action_del);
        
        $this->datagrid->createModel();
        $panel->add($this->datagrid);

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $panel->addFooter($this->pageNavigation);
        
        parent::add($this->form);
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('main_db');
            $repository = new TRepository('Cliente');
            $limit = 10;
            $criteria = new TCriteria;
            
            $criteria->setProperty('limit', $limit);
            $criteria->setProperty('offset', (isset($param['offset'])) ? $param['offset'] : 0);
            $criteria->setProperty('order', 'id asc');
            
            $clientes = $repository->load($criteria, FALSE);
            $this->datagrid->clear();
            
            if ($clientes) {
                $this->datagrid->addItems($clientes);
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

    public function onDelete($param)
    {
        $action = new TAction([$this, 'deleteConfirmado']);
        $action->setParameters($param);
        new TQuestion("Deseja realmente excluir o cliente \"{$param['nome']}\"?", $action);
    }

    public function deleteConfirmado($param)
    {
        try {
            TTransaction::open('main_db');
            
            $has_pedidos = Pedido::where('cliente_id', '=', $param['id'])->count();
            if ($has_pedidos > 0) {
                throw new Exception('O cliente possui pedidos e não pode ser excluído.');
            }
            
            $cliente = new Cliente($param['id']);
            
            if ($cliente->endereco_id) {
                (new Endereco($cliente->endereco_id))->delete();
            }
            $cliente->delete();
            
            TTransaction::close();
            
            $this->onReload($param);
            new TMessage('info', 'Cliente excluído com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onShow($param = null)
    {
        $this->onReload($param);
        parent::show();
    }
}