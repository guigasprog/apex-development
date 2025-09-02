<?php

use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Validator\TRequiredValidator;
use Adianti\Validator\TEmailValidator;

class ClienteForm extends TPage
{
    private $form;

    public function __construct($param = null)
    {
        parent::__construct($param);
        
        $this->form = new TForm('form_cliente');
        $panel = new TPanelGroup('Cadastro de Cliente');
        $panel->add($this->form);
        
        $this->criarCampos();
        $this->criarAcoes();

        parent::add($panel);
    }

    private function criarCampos()
    {
        $id          = new TEntry('id');
        $nome        = new TEntry('nome');
        $email       = new TEntry('email');
        $telefone    = new TEntry('telefone');
        $cpf         = new TEntry('cpf');
        $cep         = new TEntry('cep');
        $logradouro  = new TEntry('logradouro');
        $numero      = new TEntry('numero');
        $bairro      = new TEntry('bairro');
        $cidade      = new TEntry('cidade');
        $estado      = new TEntry('estado');
        $complemento = new TEntry('complemento');
        
        $nome->addValidation('Nome', new TRequiredValidator);
        $email->addValidation('Email', new TEmailValidator);
        $cpf->addValidation('CPF', new TRequiredValidator);
        $cep->addValidation('CEP', new TRequiredValidator);

        $id->setEditable(FALSE);
        $cpf->setMask('999.999.999-99');
        $cep->setMask('99999-999');
        
        $cep->setExitAction(new TAction([$this, 'onBuscarCep']));
        
        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add(new TLabel('ID'));
        $vbox->add($id);
        $vbox->add(new TLabel('Nome <span class="text-danger">*</span>'));
        $vbox->add($nome);
        $vbox->add(new TLabel('CPF <span class="text-danger">*</span>'));
        $vbox->add($cpf);
        $vbox->add(new TLabel('Email <span class="text-danger">*</span>'));
        $vbox->add($email);
        $vbox->add(new TLabel('Telefone'));
        $vbox->add($telefone);
        $vbox->add(new TElement('hr'));
        $vbox->add(new TElement('h4'))->add('Endereço');
        $vbox->add(new TLabel('CEP <span class="text-danger">*</span>'));
        $vbox->add($cep);
        $vbox->add(new TLabel('Logradouro'));
        $vbox->add($logradouro);
        $vbox->add(new TLabel('Número'));
        $vbox->add($numero);
        $vbox->add(new TLabel('Bairro'));
        $vbox->add($bairro);
        $vbox->add(new TLabel('Cidade'));
        $vbox->add($cidade);
        $vbox->add(new TLabel('Estado'));
        $vbox->add($estado);
        $vbox->add(new TLabel('Complemento'));
        $vbox->add($complemento);

        $this->form->add($vbox);
    }

    private function criarAcoes()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
        $this->form->addAction('Listar Clientes', new TAction(['ClientesPage', 'onShow']), 'fas:list blue');
    }
    
    public function onClear($param)
    {
        $this->form->clear(true);
    }

    public function onSave($param)
    {
        try {
            TTransaction::open('main_db');
            $this->form->validate();
            $data = $this->form->getData();

            $clienteExistente = Cliente::where('email', '=', $data->email)->first();
            if ($clienteExistente && $clienteExistente->id != $data->id) {
                throw new Exception('O email já está cadastrado para outro cliente.');
            }

            $endereco = new Endereco;
            // Carrega o endereço existente se o cliente já o tiver
            if (!empty($data->id)) {
                $cliente_temp = new Cliente($data->id);
                if ($cliente_temp->endereco_id) {
                    $endereco->id = $cliente_temp->endereco_id;
                }
            }
            
            $endereco->fromArray( (array) $data );
            $endereco->store();

            $cliente = new Cliente;
            $cliente->fromArray( (array) $data );
            $cliente->endereco_id = $endereco->id;
            $cliente->store();
            
            $data->id = $cliente->id;
            $this->form->setData($data);
            
            TTransaction::close();
            new TMessage('info', 'Cliente salvo com sucesso!');
            
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onBuscarCep($param) 
    {
        try {
            if (empty($param['cep'])) return;

            $cep = preg_replace('/[^0-9]/', '', $param['cep']);
            if (strlen($cep) !== 8) return;

            $endereco = ConsultaCepService::onBuscarCep($cep);

            if (isset($endereco->error)) {
                throw new Exception($endereco->error);
            }

            $object = new stdClass;
            $object->logradouro  = $endereco->logradouro ?? '';
            $object->bairro      = $endereco->bairro ?? '';
            $object->cidade      = $endereco->localidade ?? '';
            $object->estado      = $endereco->uf ?? '';
            $object->complemento = $endereco->complemento ?? '';

            TForm::sendData('form_cliente', $object);
        } catch (Exception $e) {
            new TMessage('error', '<b>Erro ao buscar CEP:</b> ' . $e->getMessage());
        }
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('main_db');
                $cliente = new Cliente($param['id']);
                $data = $cliente->toArray();
                
                if ($cliente->endereco) {
                    $data = array_merge($data, $cliente->endereco->toArray());
                }
                
                $this->form->setData( (object) $data );
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', '<b>Erro ao carregar dados:</b> ' . $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {
        $this->onReload($param);
        parent::show();
    }
}