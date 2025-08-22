<?php
require_once 'init.php';

$ini    = AdiantiApplicationConfig::get();
$theme  = $ini['general']['theme'];
$class  = isset($_REQUEST['class']) ? $_REQUEST['class'] : '';
$public = in_array($class, !empty($ini['permission']['public_classes']) ? $ini['permission']['public_classes'] : []);

new TSession;
// O serviço de autenticação padrão que você referenciou não é necessário aqui,
// pois este index já faz a verificação manualmente.

if (TSession::getValue('logged'))
{
    $content = file_get_contents("app/templates/{$theme}/layout.html");
    $content = str_replace('{MENU}', AdiantiMenuBuilder::parse('menu.xml', $theme), $content);
}
else
{
    $content = file_get_contents("app/templates/{$theme}/login.html");
}

$content = ApplicationTranslator::translateTemplate($content);
$content = AdiantiTemplateParser::parse($content);

echo $content;

if (TSession::getValue('logged') || $public)
{
    if ($class)
    {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
        AdiantiCoreApplication::loadPage($class, $method, $_REQUEST);
    }
}
else
{
    // Por padrão, carrega nosso formulário de login customizado
    AdiantiCoreApplication::loadPage('LoginForm', null, $_REQUEST);
}