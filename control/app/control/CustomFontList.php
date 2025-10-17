<?php
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Widget\Form\TEntry;
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

class CustomFontList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;

    public function __construct()
    {
        parent::__construct();
        
        // ADICIONADO: Carrega todas as fontes para que a datagrid possa exibi-las
        try {
            TTransaction::open('permission');
            $all_fonts = CustomFont::getObjects();
            TTransaction::close();

            if ($all_fonts) {
                foreach ($all_fonts as $font) {
                    TPage::include_css($font->import_url);
                }
            }
        } catch (Exception $e) {
            new TMessage('error', 'Falha ao carregar as fontes: ' . $e->getMessage());
        }

        // --- Formulário ---
        $this->form = new BootstrapFormBuilder('form_custom_font');
        $this->form->setFormTitle('Gerenciar Fonte');

        $id         = new THidden('id');
        $name       = new TEntry('name');
        $label      = new TEntry('label');
        $import_url = new TEntry('import_url');

        $name->setTip('Nome exato da fonte usado no CSS (ex: "Satoshi")');
        $import_url->placeholder = 'https://fonts.googleapis.com/...';
        
        $name->addValidation('Nome (ID)', new TRequiredValidator);
        $label->addValidation('Label', new TRequiredValidator);
        $import_url->addValidation('URL de Importação', new TRequiredValidator);

        $this->form->addFields([$id]);
        $this->form->addFields([new TLabel('Nome (ID)', 'red')], [$name]);
        $this->form->addFields([new TLabel('Label (Rótulo)', 'red')],    [$label]);
        $this->form->addFields([new TLabel('URL de Importação', 'red')], [$import_url]);
        
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fa:eraser red');

        // --- Listagem ---
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        
        $col_label = new TDataGridColumn('label', 'Label', 'left', '30%');
        $col_name  = new TDataGridColumn('name', 'Nome (ID)', 'left', '20%'); // ALTERADO: Criado como variável
        $col_url   = new TDataGridColumn('import_url', 'URL', 'left', '50%');

        // ALTERADO: Aplica o transformer na coluna 'Nome (ID)'
        $col_name->setTransformer([$this, 'formatFontName']);
        
        $this->datagrid->addColumn($col_label);
        $this->datagrid->addColumn($col_name); // ALTERADO: Adiciona a coluna já transformada
        $this->datagrid->addColumn($col_url);

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
     * ADICIONADO: Método Transformer para formatar o nome da fonte.
     * Este método é chamado para cada célula da coluna 'name'.
     * @param $value O valor da célula (ex: "Satoshi")
     * @return TElement Um <span> estilizado com a fonte correta
     */
    public function formatFontName($value, $object, $row)
    {
        if (empty($value)) {
            return '';
        }
        
        $span = new TElement('span');
        // Usa o próprio valor ($value) para definir o font-family
        $span->style = "font-family: '{$value}', sans-serif; font-size: 1.2em; font-weight: bold;";
        $span->add($value);
        return $span;
    }
    
    public function onReload($param = NULL)
    {
        try {
            TTransaction::open('permission');
            $repository = new TRepository('CustomFont');
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
            
            $object = new CustomFont;
            $object->fromArray( (array) $data);
            $object->store();
            
            TTransaction::close();
            
            new TMessage('info', 'Registro salvo com sucesso!', new TAction([$this, 'onReload']));
            $this->onClear($param);
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
                $object = new CustomFont($param['id']);
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
            $object = new CustomFont($param['id']);
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