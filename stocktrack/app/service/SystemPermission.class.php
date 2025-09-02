<?php

use Adianti\Registry\TSession;
use Adianti\Control\TPage;

/**
 * SystemPermission
 *
 * @version    1.0
 * @author     Guilherme B. G. de Matos
 */
class SystemPermission
{
    /**
     * Check if the user has permission for a given class
     * @param $className The class name
     */
    public static function checkPermission($className)
    {
        // Verifica se o utilizador está logado
        if (TSession::getValue('logged'))
        {
            // Se estiver logado, permite o acesso a qualquer página.
            // (Aqui você poderia adicionar lógicas mais complexas de permissão no futuro)
            return true;
        }

        // Se não estiver logado, nega o acesso
        return false;
    }

    /**
     * Check if the user has permission for a given class (Alias)
     * Algumas versões do Adianti usam 'check' como nome do método.
     */
    public static function check($className)
    {
        return self::checkPermission($className);
    }
}