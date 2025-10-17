<?php
/**
 * LoginForm
 *
 * @version    8.2
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class LoginForm extends TPage
{
    protected $form; // form
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    public function __construct($param)
    {
        parent::__construct();
        
        $ini  = AdiantiApplicationConfig::get();
        
        // creates the form
        $this->form = new TModalForm('form_login');
        $this->form->setFormTitle('Login');
        
        if (!empty($ini['login']['logo']))
        {
            $logo = new TImage($ini['login']['logo']);
            $logo->style = 'margin:auto;max-width:5rem';
            $this->form->setFormTitle($logo);
        }
        
        // create the form fields
        $login               = new TEntry('login');
        $password            = new TPassword('password');
        $previous_class      = new THidden('previous_class');
        $previous_method     = new THidden('previous_method');
        $previous_parameters = new THidden('previous_parameters');
        
        $login->disableAutoComplete();
        $password->disableAutoComplete();
        $login->setSize('100%');
        $password->setSize('100%');
        $login->placeholder = _t('User');
        $password->placeholder = _t('Password');
        $password->disableToggleVisibility();
        $login->autofocus = 'autofocus';
        
        $this->form->addRowField(_t('Login'), $login, true );
        $this->form->addRowField(_t('Password'), $password, true );
        
        $this->form->addRowContent( $previous_class );
        $this->form->addRowContent( $previous_method );
        $this->form->addRowContent( $previous_parameters );
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            $recaptcha_html = str_replace('{sitekey}',$ini['recaptcha']['key'],file_get_contents('app/resources/recaptcha.html'));
            $this->form->addRowContent( $recaptcha_html );
        }
        
        if (!empty($param['previous_class']) && $param['previous_class'] !== 'LoginForm')
        {
            $previous_class->setValue($param['previous_class']);
            
            if (!empty($param['previous_method']))
            {
                $previous_method->setValue($param['previous_method']);
            }
            
            $previous_parameters->setValue(base64_encode(json_encode($param)));
        }
        
        $this->form->addAction("Entrar", new TAction([$this, 'onLogin']), '' );
        
        if (isset($ini['permission']['user_register']) && $ini['permission']['user_register'] == '1')
        {
            $this->form->addFooterAction(_t('Create account'), new TAction(['TenantRegistrationForm', 'onLoad']), '');
        }
        
        parent::add($this->form);
    }
    
    /**
     * Authenticate the User
     */
    public static function onLogin($param)
    {
        try
        {
            TSession::regenerate();
            $data = (object) $param;
            
            TTransaction::open('permission');
            
            (new TRequiredValidator)->validate( _t('Login'),    $data->login);
            (new TRequiredValidator)->validate( _t('Password'), $data->password);
            
            $user = SystemUser::authenticate( $data->login, $data->password );
            
            if ($user)
            {
                TSession::freeSession();
                TSession::setValue('logged', TRUE);
                TSession::setValue('login', $user->login);
                TSession::setValue('userid', $user->id);
                TSession::setValue('username', $user->name);
                TSession::setValue('usermail', $user->email);
                
                $redirect_page = '';

                if (empty($user->tenant_id))
                {
                    TSession::setValue('is_super_admin', TRUE);
                    $redirect_page = 'SystemAdministrationDashboard';
                }
                else
                {
                    TSession::setValue('is_super_admin', FALSE);
                    $tenant = new Tenant($user->tenant_id);
                    if ($tenant && $tenant->db_connection_name)
                    {
                        TSession::setValue('tenant_connection', $tenant->db_connection_name);
                    }
                    $redirect_page = 'WelcomeView';
                }
                
                SystemAccessLogService::registerLogin();
                AdiantiCoreApplication::gotoPage($redirect_page);
            }
            
            TTransaction::close();
        }
        catch (Exception $e)
        {
            TSession::freeSession();
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * Check if password needs to be renewed
     */
    private static function checkForPasswordRenew($user)
    {
        TTransaction::open('permission');
        if (SystemUserOldPassword::needRenewal($user->id))
        {
            TSession::setValue('login', $user->login);
            TSession::setValue('userid', $user->id);
            TSession::setValue('need_renewal_password', true);
            
            return true;
        }
        TTransaction::close();
    }
    
    /**
     * Check 2FA
     */
    private static function checkTwoFactor($user, $param)
    {
        if (!empty($user->otp_secret))
        {
            if (!empty($param['two_factor']))
            {
                $otp = \OTPHP\TOTP::create($user->otp_secret);
                if ($otp->verify($param['two_factor']))
                {
                    return true;
                }
            }
            
            $action = new TAction(['LoginForm', 'onLogin'], $param);
            $form = new BootstrapFormBuilder('two_factor_form');
            
            $two_factor = new TPassword('two_factor');
            $two_factor->style = 'height: 40px;';
            $two_factor->placeholder = _t('Authentication code');
            
            $form->addContent( [ new TLabel(_t('Enter the 6-digit code from your authenticator app')) ] );
            $form->addFields([$two_factor]);
            $form->addFields([new TEntry('lock_enter')])->style = 'display:none';;
            
            $btn = $form->addAction( _t('Authenticate'), $action, '');
            $btn->class = 'btn btn-primary';
            $btn->style = 'height: 40px;width: 90%;display: block;margin: auto;font-size: 17px;';
            
            return $form;
        }
    }
    
    /**
     * Policy terms verification
     */
    private static function policyTermsVerification($user, $param)
    {
        $ini  = AdiantiApplicationConfig::get();
        
        $term_policy = SystemPreference::findInTransaction('permission', 'term_policy');
        
        if (!empty($ini['general']['require_terms']) && $ini['general']['require_terms'] == '1')
        {
            if ($user->accepted_term_policy !== 'Y' && !empty($term_policy) && empty($param['accept']))
            {
                $param['usage_term_policy'] = 'Y';
                $action = new TAction(['LoginForm', 'onLogin'], $param);
                $form = new BootstrapFormBuilder('term_policy');
    
                $content = new TElement('div');
                $content->style = "max-height: 45vh; overflow: auto; margin-bottom: 10px;";
                $content->add($term_policy->value);
    
                $check = new TCheckGroup('accept');
                $check->addItems(['Y' => _t('I have read and agree to the terms of use and privacy policy')]);
    
                $form->addContent([$content]);
                $form->addFields([$check]);
                $btn = $form->addAction( _t('Accept'), $action, 'fas:check');
                $btn->class = 'btn btn-primary';
                return $form;
            }
            
            if ($user->accepted_term_policy !== 'Y' && !empty($term_policy) && !empty($param['accept']))
            {
                TTransaction::open('permission');
                $user->accepted_term_policy = 'Y';
                $user->accepted_term_policy_at = date('Y-m-d H:i:s');
                $user->accepted_term_policy_data = json_encode($_SERVER);
                $user->store();
                TTransaction::close();
            }
        }
    }
    
    /**
     * Pre validate recaptcha
     */
    private static function preCheckRecaptcha()
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            if (empty($_REQUEST["g-recaptcha-response"]))
            {
                throw new Exception(_t('Invalid captcha'));
            }
        }
    }
    
    /**
     * Check Recaptcha
     */
    private static function checkRecaptcha()
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            RecaptchaServices::validate();
        }
    }
    
    /**
     * Reset Recaptcha
     */
    private static function resetRecaptcha()
    {
        $ini  = AdiantiApplicationConfig::get();
        
        if (!empty($ini['recaptcha']) && $ini['recaptcha']['enabled'] == '1')
        {
            RecaptchaServices::reset();
        }
    }
    
    /** * Reload permissions
     */
    public static function reloadPermissions()
    {
        try
        {
            SystemPermissionService::reloadPermissions();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     *
     */
    public function onLoad($param)
    {
    }
    
    /**
     * Logout
     */
    public static function onLogout()
    {
        SystemAccessLogService::registerLogout();
        TSession::freeSession();
        AdiantiCoreApplication::gotoPage('LoginForm', '');
    }
}