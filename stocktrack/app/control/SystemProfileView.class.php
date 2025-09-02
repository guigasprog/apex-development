<?php

use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;

class SystemProfileView extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        try
        {
            $this->form = new TForm('form_profile');

            $table = new TTable;
            $table->width = '100%';
            $this->form->add($table);

            $id    = new TEntry('id');
            $nome  = new TEntry('nome');
            $email = new TEntry('email');
            
            TTransaction::open('main_db'); 
            
            $user = new User(TSession::getValue('userid'));
            if ($user)
            {
                $this->form->setData($user);
            }
            
            TTransaction::close();

            $id->setEditable(false);
            $nome->setEditable(false);
            $email->setEditable(false);
            
            $table->addRowSet(new TLabel('ID:'), $id);
            $table->addRowSet(new TLabel('Nome:'), $nome);
            $table->addRowSet(new TLabel('Email:'), $email);
            
            $panel = new TPanelGroup('Meu Perfil');
            $panel->add($this->form);
            
            $vbox = new TVBox;
            $vbox->style = 'width: 100%';
            $vbox->add($panel);
            
            parent::add($vbox);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            // Garante que a transação seja desfeita em caso de erro
            TTransaction::rollback();
        }
    }
}