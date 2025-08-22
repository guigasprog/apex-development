<?php
use Adianti\Control\TPage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\THidden;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;

class TenantForm extends TPage
{
    protected $form;

    use Adianti\Base\AdiantiStandardFormTrait;

    public function __construct()
    {
        parent::__construct();

        // ** VERIFICAÇÃO DE SEGURANÇA **
        if (TSession::getValue('is_super_admin') !== true)
        {
            new Adianti\Widget\Dialog\TMessage('error', 'Acesso negado');
            return;
        }

        $this->setDatabase('database'); // Usa 'db_master'
        $this->setActiveRecord('Tenant');

        $this->form = new BootstrapFormBuilder('form_tenant');
        $this->form->setFormTitle('Cadastro de Tenant');

        $id = new THidden('id');
        $nome_loja = new TEntry('nome_loja');
        $schema_name = new TEntry('schema_name');
        $status = new TCombo('status');

        $schema_name->setTip('Nome único, sem espaços ou caracteres especiais (ex: tenant_loja_x)');
        $status->addItems(['ativo' => 'Ativo', 'inativo' => 'Inativo', 'suspenso' => 'Suspenso']);

        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Nome da Loja', 'red')], [$nome_loja]);
        $this->form->addFields([new TLabel('Nome do Schema', 'red')], [$schema_name]);
        $this->form->addFields([new TLabel('Status', 'red')], [$status]);
        
        $nome_loja->addValidation('Nome da Loja', new TRequiredValidator);
        $schema_name->addValidation('Nome do Schema', new TRequiredValidator);
        $status->addValidation('Status', new TRequiredValidator);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar', new TAction(['TenantList', 'onReload']), 'fa:arrow-left');

        parent::add($this->form);
    }

    /**
     * ATENÇÃO:
     * Este onSave apenas salva os dados na tabela 'tenants'.
     * A lógica para CRIAR O SCHEMA no banco de dados e popular as tabelas
     * deve ser adicionada aqui. Isso é um passo mais avançado.
     */
    public function onSave($param)
    {
        // Lógica padrão de salvar do trait
        parent::onSave($param);
        
        // TODO: Adicionar aqui a lógica para criar e popular o schema no banco de dados.
    }
}