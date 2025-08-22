<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Database\TTransaction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TPanelGroup;
use Adianti\Widget\Container\TVBox;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TButton;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TPassword;
use Adianti\Widget\Util\TImage;

class LoginForm extends TPage
{
    protected $form;

    public function __construct($param)
    {
        parent::__construct($param);
        
        $panel = new TPanelGroup('Acessar o Sistema');
        $panel->style = 'max-width: 450px; margin: auto; margin-top: 10vh;';

        $this->form = new TForm('form_login');
        
        $login    = new TEntry('login');
        $password = new TPassword('password');

        $login->setSize('100%', 48);
        $password->setSize('100%', 48);
        $login->placeholder = 'Email';
        $password->placeholder = 'Senha';

        $vbox = new TVBox;
        $vbox->style = 'width: 100%';
        $vbox->add($login);
        $vbox->add($password);
        $this->form->add($vbox);

        $login_button = new TButton('login_button');
        $login_button->class = 'btn btn-primary w-100';
        $login_button->style = 'font-size: 1.1rem; padding: 10px; margin-top: 10px;';
        $login_button->setLabel('ENTRAR');
        $login_button->setImage('fas:sign-in-alt');
        $login_button->setAction(new TAction([$this, 'onLogin']), 'Login');
        
        $this->form->addField($login);
        $this->form->addField($password);
        $this->form->addField($login_button);
        
        $panel->add($this->form);
        $panel->addFooter($login_button);
        
        parent::add($panel);
    }

    public static function onLogin($param)
    {
        try
        {
            TTransaction::open('database');
            $data = (object) $param;
            
            if (empty($data->login)) { throw new Exception('O campo Email é obrigatório'); }
            if (empty($data->password)) { throw new Exception('O campo Senha é obrigatório'); }
            
            $user = User::where('email', '=', $data->login)->first();
            
            if ($user && password_verify($data->password, $user->password_hash))
            {
                TSession::setValue('logged', TRUE);
                TSession::setValue('userid', $user->id);
                TSession::setValue('username', $user->nome);
                TSession::setValue('usermail', $user->email);

                if (is_null($user->tenant_id))
                {
                    TSession::setValue('is_super_admin', true);
                    AdiantiCoreApplication::gotoPage('TenantList');
                }
                else
                {
                    TSession::setValue('is_super_admin', false);
                    $tenant = new Tenant($user->tenant_id);

                    if (!$tenant || $tenant->status !== 'ativo') {
                        throw new Exception('Loja não encontrada ou inativa.');
                    }
                    
                    TSession::setValue('tenant_id', $tenant->id);
                    TSession::setValue('tenant_schema', $tenant->schema_name);

                    $ini = AdiantiApplicationConfig::get();
                    $ini['main_db']['name'] = $tenant->schema_name;
                    Adianti\Database\TTransaction::reload($ini, 'main_db');
                    
                    AdiantiCoreApplication::gotoPage('WelcomeView');
                }
            }
            else
            {
                throw new Exception('Email ou senha inválidos.');
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TSession::clear();
            TTransaction::rollback();
        }
    }
    
    public static function onLogout($param = null)
    {
        TSession::clear();
        AdiantiCoreApplication::gotoPage('index.php');
    }
}