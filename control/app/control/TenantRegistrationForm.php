<?php
use Adianti\Control\TAction;
use Adianti\Control\TPage;
use Adianti\Database\TConnection;
use Adianti\Database\TTransaction;
use Adianti\Validator\TRequiredValidator;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TLabel;
use Adianti\Widget\Form\TPassword;
use Adianti\Wrapper\BootstrapFormBuilder;

class TenantRegistrationForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_tenant_registration');
        $this->form->setFormTitle('Criar Nova Conta e Loja');
        $this->form->setColumnClasses(12, 12);

        $nome_loja  = new TEntry('nome_loja');
        $name       = new TEntry('name');
        $login      = new TEntry('login');
        $email      = new TEntry('email');
        $password   = new TPassword('password');
        $repassword = new TPassword('repassword');
        
        $nome_loja->placeholder = 'Ex: Stock Track';
        $name->placeholder = 'Seu nome completo';
        $login->placeholder = 'Escolha um nome de usuário (ex: joao.silva)';
        $email->placeholder = 'email@dominio.com';
        $password->placeholder = 'Crie uma senha forte';
        $repassword->placeholder = 'Confirme a senha';

        $nome_loja->addValidation('Nome da Loja', new TRequiredValidator);
        $name->addValidation('Seu Nome', new TRequiredValidator);
        $login->addValidation('Login', new TRequiredValidator);
        $email->addValidation('Email', new TRequiredValidator);
        $password->addValidation('Senha', new TRequiredValidator);
        $repassword->addValidation('Confirmação de Senha', new TRequiredValidator);
        
        $this->form->addFields( [new TLabel('Nome da sua Loja')] );
        $this->form->addFields( [$nome_loja] )->style = 'margin-bottom: 15px';
        $this->form->addFields( [new TLabel('Seu Nome Completo')] );
        $this->form->addFields( [$name] )->style = 'margin-bottom: 15px';
        $this->form->addFields( [new TLabel('Seu Login de Acesso')] );
        $this->form->addFields( [$login] )->style = 'margin-bottom: 15px';
        $this->form->addFields( [new TLabel('Seu Email')] );
        $this->form->addFields( [$email] )->style = 'margin-bottom: 15px';
        $this->form->addFields( [new TLabel('Senha')] );
        $this->form->addFields( [$password] )->style = 'margin-bottom: 15px';
        $this->form->addFields( [new TLabel('Confirme a Senha')] );
        $this->form->addFields( [$repassword] )->style = 'margin-bottom: 15px';

        $this->form->addAction('Criar Conta', new TAction([$this, 'onSave']), 'fa:check-circle green')->class = 'btn btn-primary';
        
        $action_back = new TAction(['LoginForm', 'onLoad']);
        $action_back->setProperty('validate', FALSE);
        $this->form->addAction('Voltar para o Login', $action_back, 'fa:arrow-left')->class = 'btn btn-dark';
        
        $panel = new TPanelGroup;
        $panel->add($this->form);
        $panel->style = 'max-width: 600px; margin: auto; margin-top: 5vh;';
        
        parent::add($panel);
    }

    public static function onSave($param)
    {
        try
        {
            TTransaction::open('permission');
            
            if ($param['password'] !== $param['repassword']) {
                throw new Exception('As senhas não coincidem');
            }
            if (SystemUser::where('login', '=', $param['login'])->first()) {
                throw new Exception('Este login já está em uso');
            }
            if (SystemUser::where('email', '=', $param['email'])->first()) {
                throw new Exception('Este email já está cadastrado');
            }

            $tenant = new Tenant;
            $tenant->nome_loja = $param['nome_loja'];
            $tenant->status = 'ativo';

            $tenant = new Tenant;
            $tenant->nome_loja = $param['nome_loja'];
            
            // --- AUTOMAÇÃO DA CRIAÇÃO DO SLUG ---
            $slug = strtolower($tenant->nome_loja);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            
            // Verifica se o slug já existe para evitar duplicatas
            $count = Tenant::where('slug', '=', $slug)->count();
            if ($count > 0) {
                // Se já existe, adiciona um número aleatório no final
                $slug .= '-' . rand(100, 999);
            }
            
            $tenant->slug = $slug;

            $tenant->store();

            $db_path = 'app/database/tenant_' . $tenant->id . '.db';
            $conn_name = 'tenant_' . $tenant->id;
            
            self::createTenantDatabase($db_path);
            self::updateConnectionFile($conn_name, $db_path);

            $tenant->db_connection_name = $conn_name;
            $tenant->store();

            $theme = new TenantTheme;
            $theme->tenant_id = $tenant->id;
            $theme->store();

            $user = new SystemUser;
            $user->tenant_id = $tenant->id;
            $user->name = $param['name'];
            $user->login = $param['login'];
            $user->email = $param['email'];
            $user->password = password_hash($param['password'], PASSWORD_DEFAULT);
            $user->active = 'Y';
            
            // LINHA REMOVIDA ABAIXO
            // $user->addSystemUserGroup(new SystemGroup(2));
            
            $user->store();
            
            TTransaction::close();

            $pos_action = new TAction(['TenantThemeForm', 'onLoad'], ['tenant_id' => $tenant->id]);
            new TMessage('info', 'Sua conta foi criada! Agora, vamos personalizar a aparência da sua loja.', $pos_action);
        }
        catch (Exception $e)
        {
            new TMessage('error', 'Ocorreu um erro: ' . $e->getMessage());
            TTransaction::rollback();
        }
    }

    private static function createTenantDatabase($db_path)
    {
        $sql_script = file_get_contents('app/database/application.sql');
        if (empty($sql_script)) {
            throw new Exception('Arquivo de template do banco de dados (application.sql) não encontrado ou vazio.');
        }

        try
        {
            if (file_exists($db_path))
            {
                unlink($db_path);
            }
            
            $pdo = new PDO('sqlite:' . $db_path);
            $pdo->exec($sql_script);
            $pdo = null;
        }
        catch (Exception $e)
        {
            throw new Exception("Falha ao criar o banco de dados do tenant: " . $e->getMessage());
        }
    }
    
    private static function updateConnectionFile($conn_name, $db_path)
    {
        // O nome do arquivo .ini será o mesmo nome da conexão (ex: app/config/tenant_2.ini)
        $ini_file_path = 'app/config/' . $conn_name . '.ini';
        
        // O conteúdo do novo arquivo .ini
        $ini_content = "; Tenant connection created at " . date('Y-m-d H:i:s') . "\n";
        $ini_content .= "host = \n";
        $ini_content .= "port = \n";
        $ini_content .= "name = {$db_path}\n";
        $ini_content .= "user = \n";
        $ini_content .= "pass = \n";
        $ini_content .= "type = sqlite\n";
        $ini_content .= "prep = 1\n";
        
        file_put_contents($ini_file_path, $ini_content);
    }
    
    public function onLoad($param) { }
}