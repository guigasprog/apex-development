<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;

class OrderForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_order');
        $this->form->setFormTitle('Detalhes do Pedido');
        $this->form->setColumnClasses(6,6);

        $id           = new THidden('id');
        $cliente_nome = new TEntry('cliente_nome');
        $data_pedido  = new TEntry('data_pedido');
        $total        = new TEntry('total');
        $status       = new TCombo('status');

        $status->addItems([
            'pendente' => 'Pendente',
            'pago' => 'Pago',
            'enviado' => 'Enviado',
            'entregue' => 'Entregue',
            'cancelado' => 'Cancelado'
        ]);
        
        $cliente_nome->setEditable(FALSE);
        $data_pedido->setEditable(FALSE);
        $total->setEditable(FALSE);
        
        $this->form->addFields([$id]);
        $this->form->addFields( [new TLabel('Cliente')], [$cliente_nome] );
        $this->form->addFields( [new TLabel('Data do Pedido')], [$data_pedido] );
        $this->form->addFields( [new TLabel('Total')], [$total] );
        $this->form->addFields( [new TLabel('Status do Pedido')], [$status] );
        
        $this->form->addAction('Salvar Status', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar para a lista', new TAction(['OrderList', 'onReload']), 'fa:arrow-left');

        parent::add($this->form);
    }

    public function onSave($param)
    {
        try
        {
            TTransaction::open(TSession::getValue('tenant_connection'));

            $data = $this->form->getData();
            $this->form->validate();
            
            $object = new Pedido($data->id);
            $object->status = $data->status; // Atualiza apenas o status
            $object->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Status do pedido salvo com sucesso!', new TAction(['OrderList', 'onReload']));
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onEdit($param)
    {
        try
        {
            if (isset($param['id']))
            {
                TTransaction::open(TSession::getValue('tenant_connection'));
                
                $pedido = new Pedido($param['id']);
                
                if ($pedido)
                {
                    $cliente = new Cliente($pedido->cliente_id);
                    $pedido->cliente_nome = $cliente->nome;
                    $pedido->total = 'R$ ' . number_format($pedido->total, 2, ',', '.');
                    $this->form->setData($pedido);
                }
                
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}