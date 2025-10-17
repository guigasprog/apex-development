<?php
use Adianti\Registry\TSession;
use Adianti\Core\AdiantiApplicationConfig;
use Adianti\Widget\Menu\TMenuParser;

/**
 * SystemPermission
 *
 * @version    1.0
 * @package    service
 * @author     Seu Nome
 */
class SystemPermission
{
    public static function checkPermission($class)
    {
        $ini = AdiantiApplicationConfig::get();
        $public_classes = !empty($ini['permission']['public_classes']) ? $ini['permission']['public_classes'] : [];
        if (in_array($class, $public_classes))
        {
            return true;
        }
        
        if (TSession::getValue('logged'))
        {
            $allowed_internal_pages = [
                'StoreSalesList',      // A página de vendas da loja
                'SystemProfileView',   // A página de perfil do usuário
                'CommonPage'           // A página comum de exemplo
            ];

            if (in_array($class, $allowed_internal_pages))
            {
                return true;
            }

            $menu_file = TSession::getValue('is_super_admin') ? 'menu-admin.xml' : 'menu-tenant.xml';
            
            if (file_exists($menu_file))
            {
                $menu = new TMenuParser($menu_file);
                $programs = $menu->getIndexedPrograms();
            
                return isset($programs[$class]);
            }

            return false;
        }
        
        return false;
    }
}