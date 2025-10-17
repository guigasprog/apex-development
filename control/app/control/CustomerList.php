<?php
use Adianti\Base\TStandardList;
use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Datagrid\TDataGrid;
use Adianti\Widget\Datagrid\TDataGridAction;
use Adianti\Widget\Datagrid\TDataGridColumn;
use Adianti\Widget\Datagrid\TPageNavigation;
use Adianti\Widget\Util\TXMLBreadCrumb;
use Adianti\Wrapper\BootstrapFormBuilder;
use Adianti\Database\TTransaction;
use Adianti\Widget\Dialog\TMessage;

/**
 * Listagem de Clientes do Tenant
 */
class CustomerList extends TStandardList
{
    protected $form;
    protected $datagrid;
    
    public function __construct()
    {
        parent::__construct();
        
        parent::setDatabase(TSession::getValue('tenant_connection'));
        
        parent::setActiveRecord('Cliente');
        parent::setDefaultOrder('id', 'asc');
        parent::addFilterField('nome', 'like', 'nome');
        
        $this->form = new BootstrapFormBuilder('form_search_customer');
        $this->form->setFormTitle('Buscar Clientes');
        
        $nome = new TEntry('nome');
        $this->form->addFields( [new TLabel('Nome')], [$nome] );
        $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fa:search');
        $this->form->addAction('Novo Cliente', new TAction(['CustomerForm', 'onEdit']), 'fa:plus green');
        
        $this->datagrid = new TDataGrid;
        $this->datagrid->style = 'width: 100%';
        
        $col_id    = new TDataGridColumn('id', 'ID', 'center', '10%');
        $col_nome  = new TDataGridColumn('nome', 'Nome', 'left');
        $col_email = new TDataGridColumn('email', 'Email', 'left');
        $col_tel   = new TDataGridColumn('telefone', 'Telefone', 'left');
        
        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_email);
        $this->datagrid->addColumn($col_tel);
        
        $action_edit = new TDataGridAction(['CustomerForm', 'onEdit'], ['id'=>'{id}']);
        $action_del  = new TDataGridAction([$this, 'onDelete'], ['id'=>'{id}']);
        
        // --- INÍCIO DA ALTERAÇÃO ---
        // Ação para ver o endereço no mapa
        $action_map  = new TDataGridAction([$this, 'onViewMap'], ['id'=>'{id}']);
        
        $this->datagrid->addAction($action_edit, 'Editar', 'fa:edit blue');
        $this->datagrid->addAction($action_del, 'Deletar', 'fa:trash-alt red');
        $this->datagrid->addAction($action_map, 'Ver no Mapa', 'fa:map-marker-alt green');
        // --- FIM DA ALTERAÇÃO ---
        
        $this->datagrid->createModel();
        
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        
        $panel = new TPanelGroup;
        $panel->add($this->datagrid);
        $panel->addFooter($this->pageNavigation);
        
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu-tenant.xml', __CLASS__));
        $container->add($this->form);
        $container->add($panel);
        
        parent::add($container);
    }
    
    /**
     * NOVO MÉTODO: Abre o endereço do cliente no Google Maps
     */
    public static function onViewMap($param)
    {
        try
        {
            // Abre a transação no banco de dados do tenant
            TTransaction::open(TSession::getValue('tenant_connection'));
            
            $customer = new Cliente($param['id']);
            
            if ($customer->endereco_id)
            {
                $endereco = new Endereco($customer->endereco_id);
                
                // Monta a string de endereço a partir dos dados do banco
                $address_parts = [
                    $endereco->logradouro,
                    $endereco->numero,
                    $endereco->bairro,
                    $endereco->cidade,
                    $endereco->estado
                ];
                
                // Remove partes vazias e junta com vírgula
                $address_string = implode(', ', array_filter($address_parts));
                
                if (!empty(trim($address_string)))
                {
                    // Codifica o endereço para ser usado em uma URL
                    $url_address = urlencode($address_string);
                    $google_maps_url = "https://www.google.com/maps/search/?api=1&query={$url_address}";
                    
                    // Executa um JavaScript para abrir o link em uma nova aba
                    TScript::create("window.open('{$google_maps_url}', '_blank');");
                }
                else
                {
                    new TMessage('info', 'O cliente não possui um endereço completo cadastrado.');
                }
            }
            else
            {
                new TMessage('info', 'O cliente não possui um endereço cadastrado.');
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}