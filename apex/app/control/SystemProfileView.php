<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Util\TAlert;
use Adianti\Wrapper\BootstrapFormBuilder;

class SystemProfileView extends TPage
{
    protected $form;

    public function __construct($param)
    {
        parent::__construct($param);

        try 
        {
            $this->form = new BootstrapFormBuilder('form_profile_view');
            $this->form->setFormTitle('Meu Perfil');

            $name       = new TEntry('name');
            $login      = new TEntry('login');
            $email      = new TEntry('email');
            $nome_loja  = new TEntry('nome_loja');

            $name->setEditable(FALSE);
            $login->setEditable(FALSE);
            $email->setEditable(FALSE);
            $nome_loja->setEditable(FALSE);

            $this->form->addFields([new TLabel('Nome Completo')], [$name]);
            $this->form->addFields([new TLabel('Nome de Usuário (login)')], [$login]);
            $this->form->addFields([new TLabel('Email')], [$email]);
            
            TTransaction::open('permission');
            
            $user_id = TSession::getValue('userid');
            if (!$user_id) {
                throw new Exception('Sessão inválida ou expirada.');
            }

            $user = new SystemUser($user_id);
            
            // CORREÇÃO: Prepara um único objeto para os dados
            $data_to_set = $user;
            
            if ($user->tenant_id)
            {
                $tenant = new Tenant($user->tenant_id);

                if ($tenant)
                {
                    // Adiciona a propriedade 'nome_loja' ao objeto de dados
                    $data_to_set->nome_loja = $tenant->nome_loja;

                    $slug = strtolower($tenant->nome_loja);
                    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
                    $slug = trim($slug, '-');
                    
                    $base_domain = 'apex-store.com';
                    $url = "https://{$slug}.{$base_domain}";

                    $link = new TElement('a');
                    $link->href = $url;
                    $link->add($url);
                    $link->target = '_blank';
                    $link->class = 'btn btn-outline-primary btn-sm';
                    $link->style = 'text-transform: none;';

                    $this->form->addFields([new TLabel('Nome da Loja')], [$nome_loja]);
                    $this->form->addFields([new TLabel('Link da Loja')], [$link]);
                }
            }
            else
            {
                $alert = new TAlert('info', 'Esta é uma conta de administrador global, não associada a uma loja específica.');
                $this->form->addContent([$alert]);
            }

            // CORREÇÃO: Chama setData() APENAS UMA VEZ com todos os dados
            $this->form->setData($data_to_set);

            TTransaction::close();
            
            $panel = new TPanelGroup;
            $panel->add($this->form);
            $panel->style = 'max-width: 800px; margin: auto; margin-top: 20px;';
            
            parent::add($panel);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());
        }
    }

}