<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Form\TButton;
use Adianti\Wrapper\BootstrapFormBuilder;

class WelcomeView extends TPage
{
    protected $form;

    public function __construct($param)
    {
        parent::__construct($param);

        // 1. O formulário é necessário para encapsular os botões e suas ações
        $this->form = new BootstrapFormBuilder('form_welcome');
        $this->form->setFormTitle('Bem-vindo ao StockTrack!');
        
        // 2. Mensagem Explicativa
        $intro_text = new TElement('div');
        $intro_text->style = 'padding: 0 15px 15px 15px; text-align: center;';
        $intro_text->add('<p>Este é o seu painel de controle para gestão de e-commerce.</p>
                         <p>Utilize os cards abaixo para navegar pelas principais funcionalidades.</p><hr>');
        
        $this->form->addContent([$intro_text]);

        // 3. Container Responsivo para os Cards
        $cards_container = new TElement('div');
        $cards_container->class = 'row';
        $cards_container->style = 'padding: 0 15px 15px 15px; justify-content: center;';

        // Lista de funcionalidades
        $items = [
            ['Produto', 'Cadastre, edite e visualize todos os produtos da sua loja.', 'ProdutosPage'],
            ['Cliente', 'Gerencie sua base de clientes, consulte informações e históricos.', 'ClientesPage'],
            ['Estoque', 'Controle as entradas e saídas de produtos do seu estoque.', 'EstoquePage'],
            ['Pedidos', 'Acompanhe todos os pedidos realizados, desde a compra até a entrega.', 'PedidosPage']
        ];

        // Criação dos cards
        foreach ($items as $item) {
            $card_wrapper = new TElement('div');
            $card_wrapper->class = 'col-sm-12 col-md-6';
            $card_wrapper->style = 'margin-bottom: 15px;';
            
            // Passamos o formulário para o método createCard
            $card = $this->createCard($item[0], $item[1], $item[2]);
            $card_wrapper->add($card);
            $cards_container->add($card_wrapper);
        }
        
        $this->form->addContent([$cards_container]);
        
        // Adiciona o formulário completo à página
        parent::add($this->form);
    }

    /**
     * Cria um card de funcionalidade com título, descrição e um botão de ação.
     */
    private function createCard($title, $description, $pageName)
    {
        $card = new TPanelGroup($title);
        $card->style = 'height: 100%; display: flex; flex-direction: column; justify-content: space-between;';

        $card_body = new TElement('div');
        $card_body->style = 'padding: 15px; flex-grow: 1;';
        $card_body->add($description);
        $card->add($card_body);
        
        $button = new TButton($pageName); // Nome do botão
        $button->setLabel('Acessar ' . $title);
        $button->setImage('fa:arrow-right green');
        $button->class = 'btn btn-outline-primary';
        $button->style = 'width: 100%';
        
        $button->setAction(new TAction([$this, 'onView'.$pageName]), 'Acessar ' . $title);

        $this->form->addField($button);
        
        $card->addFooter($button);

        return $card;
    }

    /**
     * Ações de navegação para cada funcionalidade.
     * Esta abordagem é robusta e funciona em todas as versões.
     */
    public function onViewProdutosPage($param)
    {
        AdiantiCoreApplication::gotoPage('ProdutosPage');
    }

    public function onViewClientesPage($param)
    {
        AdiantiCoreApplication::gotoPage('ClientesPage');
    }

    public function onViewEstoquePage($param)
    {
        AdiantiCoreApplication::gotoPage('EstoquePage');
    }

    public function onViewPedidosPage($param)
    {
        AdiantiCoreApplication::gotoPage('PedidosPage');
    }
}