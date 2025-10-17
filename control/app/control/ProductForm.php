<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Form\TText;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;

class ProductForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        
        $this->form = new BootstrapFormBuilder('form_product');
        $this->form->setFormTitle('Cadastro de Produto');
        $this->form->setColumnClasses(12, 12); // Define um layout de coluna única

        // --- Lógica para buscar as categorias manualmente ---
        $items_categorias = [];
        try
        {
            TTransaction::open(TSession::getValue('tenant_connection'));
            $categorias = Categoria::all();
            if ($categorias) {
                foreach ($categorias as $categoria) {
                    $items_categorias[$categoria->id] = $categoria->nome;
                }
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', 'Erro ao carregar categorias: ' . $e->getMessage());
        }

        // --- Criação de todos os campos do formulário ---
        $id              = new THidden('id');
        $nome            = new TEntry('nome');
        $tipo            = new TCombo('tipo');
        $categoria_id    = new TCombo('categoria_id');
        $descricao       = new TText('descricao');
        $sobre_o_item    = new TText('sobre_o_item');
        $preco           = new TNumeric('preco', 2, ',', '.', true);
        $peso_kg         = new TNumeric('peso_kg', 3, ',', '.', true);
        $comprimento_cm  = new TEntry('comprimento_cm');
        $largura_cm      = new TEntry('largura_cm');
        $altura_cm       = new TEntry('altura_cm');
        $validade        = new TDate('validade');

        // Popula o TCombo de categorias
        $categoria_id->addItems($items_categorias);

        // Configurações dos campos
        $nome->setSize('100%');
        $descricao->setSize('100%', 80);
        $sobre_o_item->setSize('100%', 80);
        $preco->setSize('100%');
        $tipo->setSize('100%');
        $categoria_id->setSize('100%');
        $peso_kg->setSize('100%');
        $comprimento_cm->setSize('100%');
        $largura_cm->setSize('100%');
        $altura_cm->setSize('100%');
        $validade->setSize('100%');
        
        $tipo->addItems(['PRODUTO' => 'Produto', 'SERVICO' => 'Serviço']);
        $preco->setNumericMask(2, ',', '.', true);
        $peso_kg->setNumericMask(3, ',', '.', true);
        $validade->setMask('dd/mm/yyyy');
        $validade->setDatabaseMask('yyyy-mm-dd');
        
        // Adiciona validação
        $nome->addValidation('Nome', new TRequiredValidator);
        $preco->addValidation('Preço', new TRequiredValidator);
        $tipo->addValidation('Tipo', new TRequiredValidator);
        
        // Adiciona os campos ao formulário em uma única coluna
        $this->form->addFields([$id]);
        $this->form->addFields( [new TLabel('Nome do Produto', 'red')], [$nome] );
        $this->form->addFields( [new TLabel('Tipo', 'red')], [$tipo] );
        $this->form->addFields( [new TLabel('Categoria')], [$categoria_id] );
        $this->form->addFields( [new TLabel('Preço (R$)', 'red')], [$preco] );
        $this->form->addFields( [new TLabel('Descrição Curta')], [$descricao] );
        $this->form->addFields( [new TLabel('Sobre o Item (detalhes)')], [$sobre_o_item] );
        $this->form->addFields( [new TLabel('Peso (kg)')], [$peso_kg] );
        $this->form->addFields( [new TLabel('Comprimento (cm)')], [$comprimento_cm] );
        $this->form->addFields( [new TLabel('Largura (cm)')], [$largura_cm] );
        $this->form->addFields( [new TLabel('Altura (cm)')], [$altura_cm] );
        $this->form->addFields( [new TLabel('Data de Validade')], [$validade] );

        // Adiciona as ações do formulário
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar para a lista', new TAction(['ProductList', 'onReload']), 'fa:arrow-left');

        parent::add($this->form);
    }

    public function onSave($param)
    {
        try
        {
            TTransaction::open(TSession::getValue('tenant_connection'));

            $this->form->validate();
            $data = $this->form->getData();
            
            $object = new Produto;
            if (!empty($data->id))
            {
                $object = Produto::find($data->id);
            }
            
            $object->fromArray( (array) $data);
            $object->store();
            
            $this->form->setData($object);
            
            new TMessage('info', 'Registro salvo com sucesso!');
            
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
                
                $object = new Produto($param['key']);
                $this->form->setData($object);
                
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