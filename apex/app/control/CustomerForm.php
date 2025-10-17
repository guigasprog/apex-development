<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Container\TNotebook;
use Adianti\Widget\Form\TForm;

class CustomerForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_customer');
        $this->form->setFormTitle('Formulário de Cliente');
        $this->form->setColumnClasses(6, 6); // Define um layout de 2 colunas

        // --- CAMPOS ---
        $id          = new THidden('id');
        $nome        = new TEntry('nome');
        $email       = new TEntry('email');
        $telefone    = new TEntry('telefone');
        $cpf         = new TEntry('cpf');
        $endereco_id = new THidden('endereco_id');
        $cep         = new TEntry('cep');
        $logradouro  = new TEntry('logradouro');
        $numero      = new TEntry('numero');
        $complemento = new TEntry('complemento');
        $bairro      = new TEntry('bairro');
        $cidade      = new TEntry('cidade');
        $estado      = new TEntry('estado');

        $cep->setExitAction(new TAction([$this, 'onExitCEP']));
        
        $nome->addValidation('Nome', new TRequiredValidator);
        $email->addValidation('Email', new TRequiredValidator);
        $cpf->addValidation('CPF', new TRequiredValidator);
        
        // Adiciona os campos ao formulário em 2 colunas
        $this->form->addFields([$id, $endereco_id]);
        
        $this->form->addContent(['<h4>Dados Pessoais</h4><hr>']);
        $this->form->addFields( [new TLabel('Nome', 'red')], [$nome] );
        $this->form->addFields( [new TLabel('Email', 'red')], [$email] );
        $this->form->addFields( [new TLabel('Telefone')], [$telefone] );
        $this->form->addFields( [new TLabel('CPF', 'red')], [$cpf] );
        
        $this->form->addContent(['<h4>Endereço</h4><hr>']);
        $this->form->addFields( [new TLabel('CEP')], [$cep] );
        $this->form->addFields( [new TLabel('Logradouro')], [$logradouro] );
        $this->form->addFields( [new TLabel('Número')], [$numero] );
        $this->form->addFields( [new TLabel('Complemento')], [$complemento] );
        $this->form->addFields( [new TLabel('Bairro')], [$bairro] );
        $this->form->addFields( [new TLabel('Cidade')], [$cidade] );
        $this->form->addFields( [new TLabel('Estado (UF)')], [$estado] );
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar para a lista', new TAction(['CustomerList', 'onReload']), 'fa:arrow-left');

        parent::add( $this->form );
    }

    public function onSave($param)
    {
        try
        {
            TTransaction::open(TSession::getValue('tenant_connection'));
            $this->form->validate();
            $data = $this->form->getData();
            
            // Lógica explícita para criar/atualizar o Endereço
            $endereco = new Endereco;
            if (!empty($data->endereco_id)) {
                $endereco = Endereco::find($data->endereco_id);
            }
            $endereco->logradouro  = $data->logradouro;
            $endereco->numero      = $data->numero;
            $endereco->complemento = $data->complemento;
            $endereco->bairro      = $data->bairro;
            $endereco->cidade      = $data->cidade;
            $endereco->estado      = $data->estado;
            $endereco->cep         = $data->cep;
            $endereco->store();

            // Lógica explícita para criar/atualizar o Cliente
            $cliente = new Cliente;
            if (!empty($data->id)) {
                $cliente = Cliente::find($data->id);
            }
            $cliente->nome     = $data->nome;
            $cliente->email    = $data->email;
            $cliente->telefone = $data->telefone;
            $cliente->cpf      = $data->cpf;
            $cliente->endereco_id = $endereco->id;
            
            if (empty($data->id)) {
                $cliente->password_hash = password_hash('1234', PASSWORD_DEFAULT);
            }
            
            $cliente->store();
            
            $data->id = $cliente->id;
            $data->endereco_id = $endereco->id;
            $this->form->setData($data);
            
            new TMessage('info', 'Cliente salvo com sucesso!');
            TTransaction::close();
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
            if (isset($param['key']))
            {
                TTransaction::open(TSession::getValue('tenant_connection'));
                
                $cliente = new Cliente($param['key']);
                $data = $cliente->toArray();

                if ($cliente->endereco_id) {
                    $endereco = new Endereco($cliente->endereco_id);
                    if ($endereco) {
                        $data = array_merge($data, $endereco->toArray());
                    }
                }
                $this->form->setData( (object) $data );
                TTransaction::close();
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public static function onExitCEP($param)
    {
        if (isset($param['cep']))
        {
            try
            {
                $cep = preg_replace('/[^0-9]/', '', $param['cep']);
                $url = "https://viacep.com.br/ws/{$cep}/json/";
                @$json = file_get_contents($url);
                $data = json_decode($json);

                if ($data && empty($data->erro)) {
                    $response = new stdClass;
                    $response->logradouro  = $data->logradouro;
                    $response->complemento = $data->complemento;
                    $response->bairro      = $data->bairro;
                    $response->cidade      = $data->localidade;
                    $response->estado      = $data->uf;
                    
                    TForm::sendData('form_customer', $response);
                }
            }
            catch (Exception $e) { /* Ignora o erro */ }
        }
    }
}