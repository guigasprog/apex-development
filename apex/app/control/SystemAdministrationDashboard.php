<?php
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Template\THtmlRenderer;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Widget\Wrapper\TDBSeekButton;
use Adianti\Widget\Base\TElement;

/**
 * SystemAdministrationDashboard (Dashboard do Super Admin)
 *
 * @version    1.0
 * @package    control
 * @author     Seu Nome
 */
class SystemAdministrationDashboard extends TPage
{
    /**
     * Class constructor
     * Creates the page
     */
    public function __construct()
    {
        parent::__construct();
        
        try
        {
            $vbox = new TVBox;
            $vbox->style = 'width: 100%';

            $cards_div = new TElement('div');
            $cards_div->class = 'row';
            $cards_div->style = 'margin: 20px;';

            // --- Buscando Dados Globais ---
            TTransaction::open('permission');
            
            // Realiza as contagens
            $total_tenants = Tenant::count();
            $active_tenants = Tenant::where('status', '=', 'ativo')->count();
            $total_users = SystemUser::where('tenant_id', 'is not', null)->count();
            $all_tenants = Tenant::all();
            
            // Cria os cartões (InfoBox)
            $indicator1 = new THtmlRenderer('app/resources/info-box.html');
            $indicator2 = new THtmlRenderer('app/resources/info-box.html');
            $indicator3 = new THtmlRenderer('app/resources/info-box.html');
            
            $indicator1->enableSection('main', ['title' => 'Total de Lojas', 'icon' => 'store', 'background' => 'purple', 'value' => $total_tenants]);
            $indicator2->enableSection('main', ['title' => 'Lojas Ativas', 'icon' => 'check-circle', 'background' => 'green', 'value' => $active_tenants]);
            $indicator3->enableSection('main', ['title' => 'Usuários de Lojas', 'icon' => 'users', 'background' => 'blue', 'value' => $total_users]);

            $cards_div->add( TElement::tag('div', $indicator1, ['class' => 'col-md-4']) );
            $cards_div->add( TElement::tag('div', $indicator2, ['class' => 'col-md-4']) );
            $cards_div->add( TElement::tag('div', $indicator3, ['class' => 'col-md-4']) );

            // Cria a tabela de tenants
            $datagrid = new TDataGrid;
            $datagrid->style = 'width: 100%';
            
            $col_id     = new TDataGridColumn('id', 'ID', 'center', '10%');
            $col_name   = new TDataGridColumn('nome_loja', 'Nome da Loja', 'left');
            $col_status = new TDataGridColumn('status', 'Status', 'center', '20%');
            
            $datagrid->addColumn($col_id);
            $datagrid->addColumn($col_name);
            $datagrid->addColumn($col_status);
            
            // Transforma o status em uma label colorida
            $col_status->setTransformer( function($value) {
                $class = ($value == 'ativo') ? 'success' : 'danger';
                $label = new TElement('span');
                $label->class = "label label-{$class}";
                $label->add($value);
                return $label;
            });
            
            $datagrid->createModel();
            $datagrid->addItems($all_tenants);
            
            $panel = TPanelGroup::pack('Visão Geral das Lojas', $datagrid);
            $panel->style = 'margin: 20px;';

            TTransaction::close();
            
            // Adiciona os elementos à página
            $vbox->add(new TXMLBreadCrumb('menu-admin.xml', __CLASS__));
            $vbox->add($cards_div);
            $vbox->add($panel);
            parent::add($vbox);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
}