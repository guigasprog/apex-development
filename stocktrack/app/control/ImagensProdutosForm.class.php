<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TMultiFile;
use Adianti\Widget\Form\TLabel;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Widget\Wrapper\TDBCombo;

class ImagensProdutosForm extends TPage
{
    private $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_imagens_produtos');
        $this->form->setFormTitle('Cadastro de Imagens nos Produtos');
        $this->form->setFieldSizes('100%');

        $this->createFormFields();
        $this->addSaveButton();

        parent::add($this->form);
    }

    private function createFormFields()
    {
        $produto_id = new TDBCombo('produto_id', 'development', 'Produto', 'id', 'nome', 'nome');
        
        $image_urls = new TMultiFile('image_urls');
        $image_urls->setAllowedExtensions(['png', 'jpg', 'jpeg', 'gif']);
        $image_urls->enableFileHandling();

        $this->form->addFields([new TLabel('Produto<span class="text-danger">*</span>')], [$produto_id]);
        $this->form->addFields([new TLabel('Imagens<span class="text-danger">*</span>')], [$image_urls]);
        
        $produto_id->addValidation('Produto', new TRequiredValidator);
        $image_urls->addValidation('Imagens', new TRequiredValidator);
    }

    
    private function addSaveButton()
    {
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fas:save btn-success');
    }

    public function onSave()
    {
        try {
            TTransaction::open('development');

            $data = $this->form->getData();
            $this->form->validate();
            
            if (empty($data->image_urls)) {
                throw new Exception('Nenhum arquivo de imagem foi enviado.');
            }

            $produto = new Produto($data->produto_id);
            if (empty($produto->nome)) {
                throw new Exception('Produto selecionado não encontrado.');
            }
            
            $repositorioImagens = new TRepository('ImagensProduto');
            $criterio = new TCriteria();
            $criterio->add(new TFilter('produto_id', '=', $data->produto_id));
            $contador = $repositorioImagens->count($criterio);

            foreach ($data->image_urls as $fileJson) {
                $fileInfo = json_decode(urldecode($fileJson));
                $fileName = $fileInfo->fileName;

                if (empty($fileName)) {
                    continue;
                }
                
                $contador++;
                
                $imagemProduto = new ImagensProduto();
                $imagemProduto->descricao = $produto->nome . '_' . $contador;
                $imagemProduto->produto_id = $data->produto_id;
                
                $source_file   = $fileName;
                $target_dir    = 'product_images';
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                if (file_exists($source_file)) {
                    $target_file = $target_dir . '/' . uniqid() . '_' . basename($source_file);
                    rename($source_file, $target_file);
                    
                    $imagemProduto->image_url = $target_file;
                    $imagemProduto->store();
                } else {
                    error_log("Arquivo não encontrado na pasta tmp: " . $source_file);
                }
            }

            TTransaction::close();
            
            new TMessage('info', 'Imagens salvas com sucesso!');
            $this->form->clear();

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', '<b>Erro ao salvar:</b> ' . $e->getMessage());
        }
    }
}
