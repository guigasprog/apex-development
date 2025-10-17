<?php
use Adianti\Database\TTransaction;

class SystemUser extends TRecord
{
    const TABLENAME = 'SystemUser';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'max';
    const DATABASE = 'permission';

    /**
     * Retorna um usuário pelo login
     */
    public static function newFromLogin($login)
    {
        return self::where('login', '=', $login)->first();
    }
    
    /**
     * Autentica um usuário
     */
    public static function authenticate($login, $password)
    {
        $user = self::newFromLogin($login);
        
        if ($user instanceof SystemUser && $user->active == 'Y')
        {
            if (password_verify($password, $user->password))
            {
                return $user;
            }
        }
        
        throw new Exception(_t('Wrong password or user'));
    }
}