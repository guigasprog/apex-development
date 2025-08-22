<?php

use Adianti\Control\TPage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Control\TAction;
use Adianti\Widget\Wrapper\TDBCombo;
use Adianti\Widget\Form\TDate;

class ProdutoForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_produto');
        $this->form->addContent( ['<h4>Cadastro de Produto</h4><hr>'] );
        $this->form->setFieldSizes('100%');

        $this->createFormFields();

        $this->addActions();

        parent::add($this->form);
    }

    private function createFormFields()
    {
        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $descricao = new THtmlEditor('descricao');
        $descricao->setSize('100%', 150);
        $sobre_o_item = new THtmlEditor('sobre_o_item');
        $sobre_o_item->setSize('100%', 150);
        $preco = new TEntry('preco');
        $validade = new TDate('validade');
        $peso_kg = new TNumeric('peso_kg', 2, ',', '.', true);
        $altura_cm = new TNumeric('altura_cm', 0, '', '');
        $largura_cm = new TNumeric('largura_cm', 0, '', '');
        $comprimento_cm = new TNumeric('comprimento_cm', 0, '', '');

        $categorias = new TDBCombo('categoria_id', 'main_db', 'Categoria', 'idCategoria', 'nome', 'nome');

        $id->setEditable(false);
        $preco->setNumericMask(2, ',', '.', true);
        $validade->setMask('dd/mm/yyyy');

        $row = $this->form->addFields([new TLabel('ID'), $id],
                                       [new TLabel('Nome<span style="color: #a00000">*</span>'), $nome],
                                       [new TLabel('Preço por unidade<span style="color: #a00000">*</span>'), $preco]);
        $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        $row = $this->form->addFields([new TLabel('Descrição'), $descricao]);
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields([new TLabel('Sobre o item'), $sobre_o_item]);
        $row->layout = ['col-sm-12'];

        $row = $this->form->addFields([new TLabel('Categoria'), $categorias],
        [new TLabel('Validade (se tiver)'), $validade]);
        $row->layout = ['col-sm-6', 'col-sm-6'];

        $this->form->addContent( ['<hr><h4>Dimensões para Frete</h4>'] );
        $row = $this->form->addFields( [new TLabel('Peso (KG) <span class="text-danger">*</span>'), $peso_kg],
                                      [new TLabel('Altura (CM) <span class="text-danger">*</span>'), $altura_cm], 
                                      [new TLabel('Largura (CM) <span class="text-danger">*</span>'), $largura_cm],
                                      [new TLabel('Comprimento (CM) <span class="text-danger">*</span>'), $comprimento_cm] );
        $row->layout = ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3'];
    }

    private function addActions()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save');
        $this->form->addActionLink('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
    }

    public function onSave()
    {
        try
        {
            TTransaction::open('main_db');
            $data = $this->form->getData();
            
            $produto = new Produto();
            $produto->fromArray((array) $data);
            
            if (!empty($data->categoria_id)) {
                $produto->set_categoria(new Categoria($data->categoria_id));
            }
            
            $produto->store();
            
            TTransaction::close();
            new TMessage('info', 'Produto salvo com sucesso');
            $this->form->clear();
        }
        catch (Exception $e)
        {
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
        try
        {
            if (isset($param['id']))
            {
                $id = $param['id'];
                TTransaction::open('main_db');

                $produto = new Produto($id);

                $this->form->setData($produto);

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
