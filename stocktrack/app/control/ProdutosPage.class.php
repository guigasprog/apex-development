<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TDBCombo;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TScroll;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Dialog\TInputDialog;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Dialog\TQuestion;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TCarousel;
use Adianti\Validator\TRequiredValidator;
use Adianti\Wrapper\BootstrapDatagridWrapper;
use Adianti\Wrapper\BootstrapFormBuilder;

class ProdutosPage extends TPage
{
    private $form;
    private $dataGrid;

    public function __construct()
    {
        parent::__construct();
        $this->setupForm();
        $this->setupDataGrid();
        $this->form->addContent([$this->dataGrid]);
        $this->form->addHeaderAction('Adicionar', new TAction(['ProdutoForm', 'onEdit']), 'fas:plus green');
        parent::add($this->form);
        $this->loadDataGrid();
    }

    private function setupForm()
    {
        $this->form = new BootstrapFormBuilder('form_produtos');
        $this->form->setFormTitle('Produtos');
    }

    private function setupDataGrid()
    {
        $this->dataGrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->dataGrid->addColumn(new TDataGridColumn('id', 'ID', 'left', '5%'));
        $this->dataGrid->addColumn(new TDataGridColumn('nome', 'Nome', 'left', '45%'));
        $this->dataGrid->addColumn(new TDataGridColumn('preco', 'Preço', 'left', '25%'));
        $this->dataGrid->addColumn(new TDataGridColumn('quantidade', 'Quantidade em Estoque', 'left', '25%'));
        $this->addDataGridActions();
        $this->dataGrid->createModel();
    }

    private function addDataGridActions()
    {
        $action_view_details = new TDataGridAction([$this, 'onViewDetails'], ['id' => '{id}']);
        $action_view_details->setLabel('Ver Mais');
        $action_view_details->setImage('fas:info-circle green');

        $action_view_estoque = new TDataGridAction([$this, 'onViewEstoque'], ['id' => '{id}']);
        $action_view_estoque->setLabel('Ver Estoque');
        $action_view_estoque->setImage('fas:box-open blue');

        $action_edit = new TDataGridAction(['ProdutoForm', 'onEdit'], ['id' => '{id}']);
        $action_edit->setLabel('Editar');
        $action_edit->setImage('fas:edit blue');

        $action_delete = new TDataGridAction([$this, 'onDelete'], ['id' => '{id}']);
        $action_delete->setLabel('Excluir');
        $action_delete->setImage('fas:trash-alt red');

        $this->dataGrid->addAction($action_view_details);
        $this->dataGrid->addAction($action_view_estoque);
        $this->dataGrid->addAction($action_edit);
        $this->dataGrid->addAction($action_delete);
    }

    public function loadDataGrid()
    {
        try {
            $this->dataGrid->clear();
            TTransaction::open('main_db');
            $repository = new TRepository('Produto');
            $produtos = $repository->load();
            if ($produtos) {
                foreach ($produtos as $produto) {
                    $produto->quantidade = $this->getEstoqueQuantidade($produto->id);
                }
                $this->dataGrid->addItems($produtos);
            }
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    private function getEstoqueQuantidade($produtoId)
    {
        $estoqueRepository = new TRepository('Estoque');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('produto_id', '=', $produtoId));
        $estoques = $estoqueRepository->load($criteria);
        return array_reduce($estoques, fn($sum, $estoque) => $sum + $estoque->quantidade, 0);
    }

    public function onDelete($param)
    {
        try {
            TTransaction::open('main_db');
            $pedidoProdutoRepository = new TRepository('PedidoProduto');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('produto_id', '=', $param['id']));
            $pedidosProdutos = $pedidoProdutoRepository->load($criteria);

            if (count($pedidosProdutos) > 0) {
                new TMessage('error', 'O Produto possui pedidos e não pode ser excluído.');
                TTransaction::close();
                return;
            }
            TTransaction::close();

            $action = new TAction([$this, 'Delete']);
            $action->setParameters($param);
            new TQuestion('Deseja realmente excluir este Produto?', $action);
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function Delete($param)
    {
        try {
            TTransaction::open('main_db');
            $produto = new Produto($param['id']);
            $produto->delete();
            TTransaction::close();
            $this->loadDataGrid();
            new TMessage('info', 'Produto excluído com sucesso!');
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onViewDetails($param)
    {
        try {
            TTransaction::open('main_db');
            $produto = new Produto($param['id']);

            $contentWrapper = new TElement('div');
            
            $this->addImagensToDialog($contentWrapper, $produto->id);
            
            if ($produto->categoria_id) {
                $categoria = new Categoria($produto->categoria_id);
                $panel_cat = new TPanelGroup('Categoria');
                $panel_cat->add($categoria->nome);
                $panel_cat->style = 'margin-bottom: 15px;';
                $contentWrapper->add($panel_cat);
            }
            
            $panel_desc = new TPanelGroup('Descrição do Produto');
            $panel_desc->add(nl2br($produto->descricao));
            $panel_desc->style = 'margin-bottom: 15px;';
            $contentWrapper->add($panel_desc);

            
            

            $scroll = new TScroll();
            $scroll->setSize('100%', '60vh');
            $scroll->add($contentWrapper);
            
            new TMessage('info', $scroll, null, '<b>Detalhes do Produto: ' . $produto->nome . '</b>');
            
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    private function addImagensToDialog($wrapper, $produtoId)
    {
        $imagemRepository = new TRepository('ImagensProduto');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('produto_id', '=', $produtoId));
        $imagens = $imagemRepository->load($criteria);

        $imagePanel = new TPanelGroup('Imagens');
        
        if ($imagens) {
            $carousel = new TElement('div');
            $carousel->class = 'carousel slide';
            $carousel->id = 'carousel_images_' . $produtoId;
            $carousel->{'data-ride'} = 'carousel';
            
            $indicators = new TElement('ol');
            $indicators->class = 'carousel-indicators';

            $carousel_inner = new TElement('div');
            $carousel_inner->class = 'carousel-inner';

            foreach ($imagens as $key => $imagem) {
                $item = new TElement('div');
                $item->class = 'carousel-item' . ($key==0 ? ' active' : '');
                
                $img = new TElement('img');
                $img->class = 'd-block w-100';
                $img->style = "height: 300px; object-fit: contain; background-color:rgb(185, 185, 185);";
                $img->src = $imagem->image_url;
                $item->add($img);
                
                $carousel_inner->add($item);
            }

            $carousel->add($carousel_inner);

            if (count($imagens) > 1) {
                $prev = new TElement('a');
                $prev->class = 'carousel-control-prev';
                $prev->href = '#'.$carousel->id;
                $prev->{'data-slide'} = 'prev';
                $prev->add('<span class="carousel-control-prev-icon" aria-hidden="true"></span>');
                $carousel->add($prev);
    
                $next = new TElement('a');
                $next->class = 'carousel-control-next';
                $next->href = '#'.$carousel->id;
                $next->{'data-slide'} = 'next';
                $next->add('<span class="carousel-control-next-icon" aria-hidden="true"></span>');
                $carousel->add($next);
            }
            
            $imagePanel->add($carousel);
        } else {
            $imagePanel->add(new TLabel('Não há imagens cadastradas para este produto.'));
        }

        $wrapper->add($imagePanel);
    }

    public function onViewEstoque($param)
    {
        try {
            TTransaction::open('main_db');
            $dialogForm = new BootstrapFormBuilder('form_view_estoque');
            $dialogForm->setFormTitle('Histórico de Estoque');
            $dialogForm->setFieldSizes('100%');

            $estoqueRepository = new TRepository('Estoque');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('produto_id', '=', $param['id']));
            $estoques = $estoqueRepository->load($criteria);

            if ($estoques) {
                $estoqueTable = new TTable;
                $estoqueTable->style = 'width: 100%; text-align: center';
                $estoqueTable->addRowSet('Quantidade', 'Data de Entrada')->style = 'font-weight: bold; background-color: #f0f0f0;';

                foreach ($estoques as $estoque) {
                    $quantidade = $estoque->quantidade ?? 'N/A';
                    $data_entrada = $estoque->data_entrada ? (new DateTime($estoque->data_entrada))->format('d/m/Y H:i:s') : 'N/A';
                    $estoqueTable->addRowSet($quantidade, $data_entrada);
                }
                $dialogForm->add($estoqueTable);
            } else {
                $label = new TLabel('Não há registros de estoque para este produto.');
                $label->style = 'width: 100%; text-align: center; padding: 20px;';
                $dialogForm->add($label);
            }

            new TInputDialog('Estoque do Produto', $dialogForm);
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}