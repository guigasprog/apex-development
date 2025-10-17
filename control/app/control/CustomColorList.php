<?php
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Base\TElement;
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

class CustomColorList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    public function __construct()
    {
        parent::__construct();
        
        // --- Formulário ---
        $this->form = new BootstrapFormBuilder('form_custom_color');
        $this->form->setFormTitle('Gerenciar Cor');

        $id        = new THidden('id');
        $type      = new TCombo('type');
        $name      = new TEntry('name');
        $label     = new TEntry('label');
        $hex_light = new TEntry('hex_light');
        $hex_dark  = new TEntry('hex_dark');

        $type->addItems(['primary' => 'Primária', 'secondary' => 'Secundária']);
        $name->setTip('Use apenas letras minúsculas e underscores (ex: "deep_blue")');

        // Adiciona validação
        $type->addValidation('Tipo', new TRequiredValidator);
        $name->addValidation('Nome (ID)', new TRequiredValidator);
        $label->addValidation('Label', new TRequiredValidator);

        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Tipo', 'red')],    [$type]);
        $this->form->addFields([new TLabel('Nome (ID)', 'red')], [$name]);
        $this->form->addFields([new TLabel('Label (Rótulo)', 'red')],    [$label]);
        $this->form->addFields([new TLabel('Hex (Tema Claro)')], [$hex_light]);
        $this->form->addFields([new TLabel('Hex (Tema Escuro)')], [$hex_dark]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        // --- Listagem ---
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        
        $col_label     = new TDataGridColumn('label', 'Label', 'left', '40%');
        $col_type      = new TDataGridColumn('type', 'Tipo', 'left', '15%');
        $col_hex_light = new TDataGridColumn('hex_light', 'Cor (Claro)', 'center', '20%');
        $col_hex_dark  = new TDataGridColumn('hex_dark', 'Cor (Escuro)', 'center', '20%');

        // Formatter para exibir um quadrado com a cor
        $col_hex_light->setTransformer([$this, 'formatColor']);
        $col_hex_dark->setTransformer([$this, 'formatColor']);
        
        $this->datagrid->addColumn($col_label);
        $this->datagrid->addColumn($col_type);
        $this->datagrid->addColumn($col_hex_light);
        $this->datagrid->addColumn($col_hex_dark);

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
    
    /**
     * Formata uma célula da datagrid para exibir a cor
     */
    public function formatColor($value, $object, $row)
    {
        if (empty($value)) {
            return 'N/A';
        }
        
        $span = new TElement('span');
        $span->style = "background-color: {$value}; padding: 5px 10px; border-radius: 3px; color: white; text-shadow: 1px 1px 1px black;";
        $span->add($value);
        return $span;
    }

    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('permission');
            $repository = new TRepository('CustomColor');
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
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onSave($param)
    {
        try {
            TTransaction::open('permission');
            
            $this->form->validate();
            $data = $this->form->getData();
            
            $object = new CustomColor;
            $object->fromArray( (array) $data);
            $object->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Registro salvo com sucesso!', new TAction([$this, 'onReload']));
            $this->form->clear();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                TTransaction::open('permission');
                $object = new CustomColor($param['id']);
                $this->form->setData($object);
                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
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
            $object = new CustomColor($param['id']);
            $object->delete();
            TTransaction::close();
            $this->onReload();
            new TMessage('info', 'Registro deletado com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
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