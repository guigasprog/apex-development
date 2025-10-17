<?php
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TText; // Usado para o campo de CSS
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Validator\TRequiredValidator;

class CustomHoverEffectList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    public function __construct()
    {
        parent::__construct();
        
        // --- Formulário ---
        $this->form = new BootstrapFormBuilder('form_custom_hover');
        $this->form->setFormTitle('Gerenciar Efeito de Hover');

        $id       = new THidden('id');
        $name     = new TEntry('name');
        $label    = new TEntry('label');
        $css_code = new TText('css_code');
        
        $css_code->setSize('100%', 120);
        $name->setTip('Use um nome único em minúsculas (ex: "pulse_effect")');
        $css_code->placeholder = "Exemplo:\n.hover-effect-meu-efeito:hover {\n    transform: rotate(3deg);\n}";
        
        // Adiciona validação
        $name->addValidation('Nome (ID)', new TRequiredValidator);
        $label->addValidation('Label', new TRequiredValidator);
        $css_code->addValidation('Código CSS', new TRequiredValidator);
        
        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Nome (ID)', 'red')], [$name]);
        $this->form->addFields([new TLabel('Label (Rótulo)', 'red')],    [$label]);
        $this->form->addFields([new TLabel('Código CSS', 'red')], [$css_code]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        // --- Listagem ---
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        
        $this->datagrid->addColumn(new TDataGridColumn('label', 'Label', 'left', '40%'));
        $this->datagrid->addColumn(new TDataGridColumn('name', 'Nome (ID)', 'left', '30%'));
        // A coluna de CSS é muito grande para ser exibida aqui, mas pode ser adicionada se desejado
        // $this->datagrid->addColumn(new TDataGridColumn('css_code', 'CSS', 'left', '30%'));

        $action_edit = new TDataGridAction([$this, 'onEdit'], ['id' => '{id}']);
        $action_del  = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_del, 'Deletar', 'fa:trash-alt red');
        
        $this->datagrid->createModel();
        
        // --- Paginação ---
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add($this->form);
        $container->add($this->datagrid);
        $container->add($this->pageNavigation);
        
        parent::add($container);
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('permission');
            $repository = new TRepository('CustomHoverEffect');
            $criteria = new TCriteria;
            $limit = 10;
            
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);
            
            $objects = $repository->load($criteria, FALSE);
            
            $this->datagrid->clear();
            if ($objects) {
                foreach ($objects as $object) {
                    $this->datagrid->addItem($object);
                }
            }
            
            $criteria->resetProperties();
            $count = $repository->count($criteria);
            
            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);
            
            TTransaction::close();
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); TTransaction::rollback(); }
    }
    
    public function onSave($param)
    {
        try {
            TTransaction::open('permission');
            
            $this->form->validate();
            $data = $this->form->getData();
            
            $object = new CustomHoverEffect;
            $object->fromArray( (array) $data);
            $object->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Registro salvo com sucesso!', new TAction([$this, 'onReload']));
            $this->onClear($param);
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); TTransaction::rollback(); }
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('permission');
                $object = new CustomHoverEffect($param['id']);
                $this->form->setData($object);
                TTransaction::close();
            }
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); TTransaction::rollback(); }
    }
    
    public function onDelete($param)
    {
        $action1 = new TAction([$this, 'Delete'], $param);
        new TQuestion('Tem certeza que deseja deletar este registro?', $action1);
    }
    
    public function Delete($param)
    {
        try {
            TTransaction::open('permission');
            $object = new CustomHoverEffect($param['id']);
            $object->delete();
            TTransaction::close();
            $this->onReload();
            new TMessage('info', 'Registro deletado com sucesso!');
        } catch (Exception $e) { new TMessage('error', $e->getMessage()); TTransaction::rollback(); }
    }
    
    public function onClear($param)
    {
        $this->form->clear(true);
    }
    
    public function show()
    {
        $this->onReload();
        parent::show();
    }
}