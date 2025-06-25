<?php
class LoginForm extends TPage
{
    protected $form; // TForm

    public function __construct()
    {
        parent::__construct();
        // Permite o uso de ?class=LoginForm no modo debug
        parent::setTargetContainer('adianti_div_content');

        $this->form = new BootstrapFormBuilder('form_login');
        $this->form->setFormTitle('Login');

        $login = new TEntry('login');
        $password = new TPassword('password');

        // Validação de
        $login->addValidation('Login', new TRequiredValidator);
        $password->addValidation('Senha', new TRequiredValidator);

        $this->form->addFields([new TLabel('Login')], $login);
        $this->form->addFields([new TLabel('Senha')], $password);

        $login->setSize('70%');
        $password->setSize('70%');

        $this->form->addAction('Login', new TAction([$this, 'onLogin']), 'fa:sign-in-alt green');
        $this->form->addAction('Registrar', new TAction(['UserRegistrationForm', 'onEdit']), 'fa:user-plus blue'); // Exemplo de link para registro

        $wrapper = new TElement('div');
        $wrapper->style = 'margin:auto; margin-top:100px; max-width:600px;';
        $wrapper->add($this->form);

        parent::add($wrapper);
    }

    public static function onLogin($param)
    {
        try
        {
            TTransaction::open('permission'); // Use o banco de dados de permissão
            $data = (object) $param;

            // Autentica o usuário (exemplo simplificado, use o AuthService do Adianti)
            // AdiantiApplicationConfig::get()['auth_service']::authenticate($data->login, $data->password);

            // Exemplo usando o serviço de autenticação configurado
            $auth_service_class = AdiantiApplicationConfig::get()['auth_service'];
            $auth_service = new $auth_service_class;
            $user = $auth_service->authenticate($data->login, $data->password);

            if ($user)
            {
                AdiantiCoreApplication::gotoPage('WelcomeView'); // Ou a página inicial após login
            }
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onShow()
    {
    }
}
?>