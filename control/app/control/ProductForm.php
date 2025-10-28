<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TCombo;
use Adianti\Widget\Form\TDate;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TMultiFile; // <-- Alterado de TFile para TMultiFile
use Adianti\Widget\Form\THidden;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TNumeric;
use Adianti\Widget\Form\TText;
use Adianti\Wrapper\BootstrapFormBuilder;
// Certifique-se de que ImagemProduto e Produto estejam acessíveis (autoload ou use)
// use App\Model\ImagemProduto; 
// use App\Model\Produto;
// use App\Model\Categoria;

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
        try {
            TTransaction::open(TSession::getValue('tenant_connection'));
            $categorias = Categoria::all();
            if ($categorias) {
                foreach ($categorias as $categoria) {
                    $items_categorias[$categoria->id] = $categoria->nome;
                }
            }
            TTransaction::close();
        } catch (Exception $e) {
            new TMessage('error', 'Erro ao carregar categorias: ' . $e->getMessage());
        }

        // --- Criação de todos os campos do formulário ---
        $id             = new THidden('id');
        $nome           = new TEntry('nome');
        $tipo           = new TCombo('tipo');
        $categoria_id   = new TCombo('categoria_id');
        $descricao      = new TText('descricao');
        $sobre_o_item   = new TText('sobre_o_item');
        $preco          = new TNumeric('preco', 2, ',', '.', true);
        $peso_kg        = new TNumeric('peso_kg', 3, ',', '.', true);
        $comprimento_cm = new TEntry('comprimento_cm');
        $largura_cm     = new TEntry('largura_cm');
        $altura_cm      = new TEntry('altura_cm');
        $validade       = new TDate('validade');

        $imagens = new TMultiFile('imagens');
        $imagens->enableFileHandling();  
        $imagens->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif']);
        $imagens->setTip('Arraste ou selecione múltiplas imagens (JPG, PNG, GIF)');
        $imagens->setSize('100%');
        // -----------------------------

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
        $this->form->addFields([new TLabel('Nome do Produto', 'red')], [$nome]);
        $this->form->addFields([new TLabel('Tipo', 'red')], [$tipo]);
        $this->form->addFields([new TLabel('Categoria')], [$categoria_id]);
        $this->form->addFields([new TLabel('Preço (R$)', 'red')], [$preco]);
        $this->form->addFields([new TLabel('Descrição Curta')], [$descricao]);
        $this->form->addFields([new TLabel('Sobre o Item (detalhes)')], [$sobre_o_item]);
        
        // --- ADICIONADO CAMPO DE IMAGEM AO FORM ---
        $this->form->addFields([new TLabel('Imagens')], [$imagens]);
        // ------------------------------------------
        
        $this->form->addFields([new TLabel('Peso (kg)')], [$peso_kg]);
        $this->form->addFields([new TLabel('Comprimento (cm)')], [$comprimento_cm]);
        $this->form->addFields([new TLabel('Largura (cm)')], [$largura_cm]);
        $this->form->addFields([new TLabel('Altura (cm)')], [$altura_cm]);
        $this->form->addFields([new TLabel('Data de Validade')], [$validade]);

        // Adiciona as ações do formulário
        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addAction('Voltar para a lista', new TAction(['ProductList', 'onReload']), 'fa:arrow-left');

        parent::add($this->form);
    }

    public function onSave($param)
    {
        try {
            TTransaction::open(TSession::getValue('tenant_connection'));

            $this->form->validate();
            $data = $this->form->getData();

            // 1. Separa os dados das imagens
            $form_data_images = $data->imagens ? $data->imagens : [];
            unset($data->imagens);
            
            // 2. Salva o objeto Produto
            $object = new Produto;
            if (!empty($data->id)) {
                $object = Produto::find($data->id);
            }
            $object->fromArray((array) $data);
            $object->store(); 

            // 3. Define o diretório de destino
            $target_dir = 'files/products';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // 4. Busca paths antigos para limpeza de arquivos
            $db_images_old = ImagemProduto::where('produto_id', '=', $object->id)->load();
            $db_paths_old = []; 
            if ($db_images_old) {
                foreach ($db_images_old as $img) {
                    $db_paths_old[] = $img->image_url;
                }
            }
            
            // 5. Processa os dados de imagem do formulário
            $final_paths = []; 
            if (is_array($form_data_images))
            {
                foreach ($form_data_images as $file_info_item)
                {
                    $source_file = null;
                    $is_new_file = false;

                    $decoded_string = urldecode($file_info_item);
                    $decoded_object = json_decode($decoded_string);
                    
                    // --- INÍCIO DA CORREÇÃO ---
                    
                    // Caso 1: É um objeto JSON
                    if (is_object($decoded_object))
                    {
                        // Caso 1a: É um ARQUIVO DELETADO (contém delFile)
                        // Esta verificação DEVE vir primeiro.
                        if (isset($decoded_object->delFile))
                        {
                            // Ação: Ignorar. Não adiciona ao $final_paths.
                            $is_new_file = false;
                        }
                        
                        // Caso 1b: É um ARQUIVO NOVO (vem de tmp/)
                        // (Usa 'newFile' ou 'fileName' se apontar para 'tmp/')
                        $file_path_check = $decoded_object->newFile ?? $decoded_object->fileName ?? '';
                        if ( (isset($decoded_object->newFile) || isset($decoded_object->fileName)) && (strpos($file_path_check, 'tmp/') === 0 || strpos($file_path_check, 'tmp\\') === 0) )
                        {
                            $source_file = $file_path_check;
                            $is_new_file = true;
                        }
                        // Caso 1c: É um ARQUIVO EXISTENTE para manter (tem fileName, NÃO tem delFile, e aponta para $target_dir)
                        else if (isset($decoded_object->fileName) && !isset($decoded_object->delFile) && strpos($decoded_object->fileName, $target_dir) === 0)
                        {
                            $final_paths[] = $decoded_object->fileName; // Adiciona direto
                            $is_new_file = false;
                        }
                    }
                    // Caso 2: É uma string simples (Fallback, caso enableFileHandling esteja desligado)
                    else if (is_string($decoded_string) && strpos($decoded_string, $target_dir) === 0) 
                    {
                         $final_paths[] = $decoded_string; // Adiciona direto
                         $is_new_file = false;
                    }
                    // --- FIM DA CORREÇÃO ---


                    // Move o arquivo novo, se houver
                    if ($is_new_file && $source_file && file_exists($source_file))
                    {
                        $new_filename = uniqid($object->id . '_') . '_' . basename($source_file);
                        $target_file = $target_dir . '/' . $new_filename;

                        try {
                            rename($source_file, $target_file); 
                            $final_paths[] = $target_file; 
                        } catch (Exception $e) {
                            new TMessage('error', 'Erro ao mover arquivo: ' . $e->getMessage());
                        }
                    }
                }
            }
            
            // Garante que a lista final não tenha duplicatas
            $final_paths = array_unique($final_paths);

            // --- Sincronização com o Banco ---
            
            // 6. Deleta TODOS os registros de imagem antigos
            ImagemProduto::where('produto_id', '=', $object->id)->delete();

            // 7. Readiciona apenas os registros que devem existir
            if ($final_paths)
            {
                foreach ($final_paths as $path)
                {
                    $new_img_obj = new ImagemProduto;
                    $new_img_obj->produto_id = $object->id;
                    $new_img_obj->image_url = $path; 
                    $new_img_obj->store();
                }
            }

            // 8. Limpa os arquivos físicos órfãos
            $paths_to_delete = array_diff($db_paths_old, $final_paths);
            foreach ($paths_to_delete as $path) 
            {
                if (file_exists($path) && strpos($path, $target_dir) === 0) 
                {
                    unlink($path); 
                }
            }
            // --- Fim da Sincronização ---

            // Recarrega o formulário com os dados corretos
            $form_data = $object->toArray();
            $form_data['imagens'] = $final_paths; // Passa o array PHP

            TTransaction::close(); 

            $this->form->setData((object) $form_data);
            new TMessage('info', 'Registro salvo com sucesso!');

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['key'])) {
                TTransaction::open(TSession::getValue('tenant_connection'));

                $object = new Produto($param['key']);

                // --- Início: Carregar Imagens ---
                // (Lógica idêntica, TMultiFile espera um JSON de array)
                $image_paths = [];
                $imagens = ImagemProduto::where('produto_id', '=', $object->id)->load();
                if ($imagens) {
                    foreach ($imagens as $img) {
                        $image_paths[] = $img->image_url;
                    }
                }
                // Adiciona os paths ao objeto para o setData funcionar
                // O TMultiFile espera um JSON string com o array de paths
                $object->imagens = $image_paths;
                // --- Fim: Carregar Imagens ---
                
                $this->form->setData($object);

                TTransaction::close();
            }
        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}