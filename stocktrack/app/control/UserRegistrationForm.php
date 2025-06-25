<?php
class UserRegistrationForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();
        parent::setTargetContainer('adianti_div_content');

        $this->form = new BootstrapFormBuilder('form_user_registration');
        $this->form->setFormTitle('Registrar Novo Usuário');

        $name  = new TEntry('name');
        $login = new TEntry('login');
        $email = new TEntry('email');
        $password = new TPassword('password');
        $password_confirm = new TPassword('password_confirm');

        $name->addValidation('Nome', new TRequiredValidator);
        $login->addValidation('Login', new TRequiredValidator);
        $email->addValidation('Email', new TEmailValidator);
        $password->addValidation('Senha', new TRequiredValidator);
        $password_confirm->addValidation('Confirmação de Senha', new TRequiredValidator);


        $this->form->addFields([new TLabel('Nome (*)')], $name);
        $this->form->addFields([new TLabel('Login (*)')], $login);
        $this->form->addFields([new TLabel('Email (*)')], $email);
        $this->form->addFields([new TLabel('Senha (*)')], $password);
        $this->form->addFields([new TLabel('Confirmar Senha (*)')], $password_confirm);

        $name->setSize('70%');
        $login->setSize('70%');
        $email->setSize('70%');
        $password->setSize('70%');
        $password_confirm->setSize('70%');

        $this->form->addAction('Salvar', new TAction([$this, 'onSave']), 'fa:save green');
        $this->form->addActionLink('Voltar para Login', new TAction(['LoginForm', 'onShow']), 'fa:arrow-left blue');

        $wrapper = new TElement('div');
        $wrapper->style = 'margin:auto; margin-top:50px; max-width:700px;';
        $wrapper->add($this->form);

        parent::add($wrapper);
    }

    public static function onSave($param)
    {
        try
        {
            TTransaction::open('permission'); // Banco de dados de permissão

            $data = (object) $param;

            if (empty($data->name)) {
                throw new Exception('Nome é obrigatório.');
            }
            if (empty($data->login)) {
                throw new Exception('Login é obrigatório.');
            }
            if (empty($data->password)) {
                throw new Exception('Senha é obrigatória.');
            }
            if ($data->password !== $data->password_confirm) {
                throw new Exception('As senhas não conferem.');
            }

            // Verifique se o login/email já existe
            $existing_user_login = SystemUser::where('login', '=', $data->login)->first();
            if ($existing_user_login) {
                throw new Exception('Este login já está em uso.');
            }
            $existing_user_email = SystemUser::where('email', '=', $data->email)->first();
            if ($existing_user_email) {
                throw new Exception('Este email já está em uso.');
            }

            $user = new SystemUser; // Modelo de usuário padrão do Adianti ou um customizado
            $user->name = $data->name;
            $user->login = $data->login;
            $user->email = $data->email;
            $user->password = password_hash($data->password, PASSWORD_DEFAULT); // Hashing da senha
            $user->active = 'Y';
            // Adicione a um grupo padrão se necessário
            // $user->addSystemUserGroup( new SystemGroup(id_do_grupo_padrao) );
            $user->store();

            // Adicionar o usuário a grupos padrão, se aplicável. Ex: um grupo "Usuários Comuns"
            // $default_group_id = SystemGroup::where('name', '=', 'NomeDoGrupoPadrao')->first()->id;
            // if ($default_group_id) {
            //    $user->addSystemUserGroup(new SystemGroup($default_group_id));
            // }


            TTransaction::close();

            new TMessage('info', 'Usuário registrado com sucesso!', new TAction(['LoginForm', 'onShow']));
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onEdit($param){} // Necessário para o fluxo de formulário do Adianti
}
?>