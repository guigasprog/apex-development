<?php

use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TForm; // TForm is needed to register the buttons

class WelcomeView extends TPage
{
    protected $form;

    public function __construct($param)
    {
        parent::__construct($param);

        // A TForm is still necessary to wrap the buttons and their actions
        $this->form = new TForm('form_welcome');

        $panel = new TPanelGroup('Bem-vindo ao StockTrack!');
        $this->form->add($panel);

        // Mensagem Explicativa
        $intro_text = new TElement('div');
        $intro_text->style = 'padding: 0 15px 15px 15px; text-align: center;';
        $intro_text->add('<p>Este é o seu painel de controle para gestão de e-commerce.</p>
                         <p>Utilize os cards abaixo para navegar pelas principais funcionalidades.</p><hr>');

        $panel->add($intro_text);

        // Container Responsivo para os Cards
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

            $card = $this->createCard($item[0], $item[1], $item[2]);
            $card_wrapper->add($card);
            $cards_container->add($card_wrapper);
        }

        $panel->add($cards_container);

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

        // A ação é definida no próprio botão
        $button->setAction(new TAction([$this, 'onNavigate'], ['page' => $pageName]), 'Acessar ' . $title);

        // O botão precisa ser adicionado ao TForm para que sua ação seja registrada
        $this->form->addField($button);

        $card->addFooter($button);

        return $card;
    }

    /**
     * Ação de navegação genérica para todos os botões.
     */
    public function onNavigate($param)
    {
        if (isset($param['page']))
        {
            AdiantiCoreApplication::gotoPage($param['page']);
        }
    }
}