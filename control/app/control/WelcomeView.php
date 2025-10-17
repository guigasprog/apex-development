<?php
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Template\THtmlRenderer;

/**
 * WelcomeView (Dashboard do Lojista)
 *
 * @version    1.0
 * @package    control
 * @author     Seu Nome
 */
class WelcomeView extends TPage
{
    /**
     * Class constructor
     * Creates the page
     */
    function __construct()
    {
        parent::__construct();
        
        try
        {
            $vbox = new TVBox;
            $vbox->style = 'width: 100%';

            // Div para a mensagem de boas-vindas
            $welcome_div = new TElement('div');
            $welcome_div->class = 'alert alert-info';
            $welcome_div->style = 'margin: 20px; font-size: 1.2em;';

            // Div para os cartões de informação (InfoBox)
            $cards_div = new TElement('div');
            $cards_div->class = 'row';
            $cards_div->style = 'margin: 20px;';

            // --- Buscando Dados ---
            
            // 1. Abre transação com o banco de PERMISSÃO para buscar dados do usuário/tenant
            TTransaction::open('permission');
            
            $user = new SystemUser(TSession::getValue('userid'));
            $tenant = new Tenant($user->tenant_id);
            
            $welcome_div->add("👋 Bem-vindo, <b>{$user->name}</b>! Você está gerenciando a loja: <b>{$tenant->nome_loja}</b>.");
            
            TTransaction::close();

            // 2. Abre transação com o banco do TENANT para buscar dados da loja
            $tenant_connection = TSession::getValue('tenant_connection');
            if ($tenant_connection)
            {
                TTransaction::open($tenant_connection);

                // Realiza as contagens (estes são exemplos, você pode customizar)
                $pedidos_pendentes = Pedido::where('status', '=', 'pendente')->count();
                $total_clientes = Cliente::count();
                $total_produtos = Produto::count();
                $pedidos_entregues_mes = Pedido::where('status', '=', 'entregue')
                                                ->where('data_pedido', '>=', date('Y-m-01'))
                                                ->count();
                
                TTransaction::close();
                
                // 3. Cria os cartões (InfoBox) com os dados
                $indicator1 = new THtmlRenderer('app/resources/info-box.html');
                $indicator2 = new THtmlRenderer('app/resources/info-box.html');
                $indicator3 = new THtmlRenderer('app/resources/info-box.html');
                $indicator4 = new THtmlRenderer('app/resources/info-box.html');
                
                $indicator1->enableSection('main', ['title' => 'Pedidos Pendentes', 'icon' => 'shopping-cart', 'background' => 'orange', 'value' => $pedidos_pendentes]);
                $indicator2->enableSection('main', ['title' => 'Clientes Cadastrados', 'icon' => 'users', 'background' => 'blue', 'value' => $total_clientes]);
                $indicator3->enableSection('main', ['title' => 'Produtos no Catálogo', 'icon' => 'box-open', 'background' => 'purple', 'value' => $total_produtos]);
                $indicator4->enableSection('main', ['title' => 'Entregas no Mês', 'icon' => 'check-circle', 'background' => 'green', 'value' => $pedidos_entregues_mes]);

                $cards_div->add( TElement::tag('div', $indicator1, ['class' => 'col-md-3']) );
                $cards_div->add( TElement::tag('div', $indicator2, ['class' => 'col-md-3']) );
                $cards_div->add( TElement::tag('div', $indicator3, ['class' => 'col-md-3']) );
                $cards_div->add( TElement::tag('div', $indicator4, ['class' => 'col-md-3']) );
            }
            
            // Adiciona os elementos à página
            $vbox->add($welcome_div);
            $vbox->add($cards_div);
            parent::add($vbox);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}