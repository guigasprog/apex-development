<?php
use Adianti\Base\TStandardForm;
use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TText;
use Adianti\Wrapper\BootstrapFormBuilder;

/**
 * Formulário de Cadastro/Edição de Categorias
 */
class CategoryForm extends TStandardForm
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase(TSession::getValue('tenant_connection'));
        parent::setActiveRecord('Categoria');

        $this->form = new BootstrapFormBuilder('form_category');
        $this->form->setFormTitle('Cadastro de Categoria');

        $id        = new THidden('id');
        $nome      = new TEntry('nome');
        $descricao = new TText('descricao');
        
        $nome->setSize('100%');
        $descricao->setSize('100%', 80);

        $nome->addValidation('Nome', new TRequiredValidator);

        $this->form->addFields([$id]);
        $this->form->addFields( [new TLabel('Nome', 'red')], [$nome] );
        $this->form->addFields( [new TLabel('Descrição')], [$descricao] );
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar para a lista', new TAction(['CategoriesList', 'onReload']), 'fa:arrow-left');

        parent::add($this->form);
    }
}