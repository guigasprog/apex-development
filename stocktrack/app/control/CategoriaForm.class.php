<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Control\TAction;
use Adianti\Validator\TRequiredValidator; // Adicionado

class CategoriaForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_categoria');
        $this->form->addContent( ['<h4>Cadastro de Categoria</h4><hr>'] );
        $this->form->setFieldSizes('100%');
        
        $this->initializeFormFields();
        $this->addFormActions();

        parent::add($this->form);
    }
    
    private function initializeFormFields()
    {
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $descricao = new TEntry('descricao');

        $nome->addValidation('Nome', new TRequiredValidator); // Adicionado

        $id->setEditable(false);
        $nome->setSize('100%');
        $descricao->setSize('100%');

        $this->form->addFields([new TLabel('ID')], [$id]);
        $this->form->addFields([new TLabel('Nome<span class="text-danger">*</span>')], [$nome]);
        $this->form->addFields([new TLabel('Descrição')], [$descricao]);
    }

    private function addFormActions()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
    }

    public function onSave()
    {
        try {
            TTransaction::open('main_db');

            $this->form->validate(); // Adicionado
            $data = $this->form->getData();

            $categoria = new Categoria();
            $categoria->fromArray((array) $data);
            $categoria->store();

            TTransaction::close();

            new TMessage('info', 'Categoria salva com sucesso');
            $this->form->clear();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onClear()
    {
        $this->form->clear();
    }

    public function onEdit($param)
    {
        try {
            if (!empty($param['id'])) {
                TTransaction::open('main_db');
                $categoria = new Categoria($param['id']);
                $this->form->setData($categoria);
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}