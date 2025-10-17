<?php
require_once 'init.php';

$ini = AdiantiApplicationConfig::get();
$theme  = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
$public = in_array($class, !empty($ini['permission']['public_classes']) ? $ini['permission']['public_classes'] : []);

new TSession;
ApplicationAuthenticationService::checkMultiSession();
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );

if ( TSession::getValue('logged') )
{
    // Lógica para carregar o menu correto baseado no tipo de usuário
    $menu_file = TSession::getValue('is_super_admin') ? 'menu-admin.xml' : 'menu-tenant.xml';
    
    if (isset($_REQUEST['template']) AND $_REQUEST['template'] == 'iframe')
    {
        $content = file_get_contents("app/templates/{$theme}/iframe.html");
    }
    else
    {
        $content = file_get_contents("app/templates/{$theme}/layout.html");
        
        // CORREÇÃO: Carrega o menu principal dinamicamente SEM CHECAR PERMISSÕES (parâmetro FALSE)
        $content = str_replace('{MENU}', AdiantiMenuBuilder::parse($menu_file, $theme, FALSE), $content);
        
        // Removemos os menus superior e inferior para simplificar a interface
        $content = str_replace('{MENUTOP}', '', $content);
        $content = str_replace('{MENUBOTTOM}', '', $content);
    }
}
else
{
    $content = file_get_contents("app/templates/{$theme}/login.html");
}

$content = ApplicationTranslator::translateTemplate($content);
$content = AdiantiTemplateParser::parse($content);

echo $content;

if (TSession::getValue('logged') OR $public)
{
    if ($class)
    {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
        AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);
    }
}
else
{
    AdiantiCoreApplication::loadPage('LoginForm', '', $_REQUEST);
}