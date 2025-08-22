<?php
if (version_compare(PHP_VERSION, '8.2.0') == -1)
{
    die ('A versão mínima requerida para o PHP é 8.2.0');
}

require_once 'lib/adianti/core/AdiantiCoreLoader.php';
spl_autoload_register(array('Adianti\Core\AdiantiCoreLoader', 'autoload'));
Adianti\Core\AdiantiCoreLoader::loadClassMap();

$loader = require 'vendor/autoload.php';
$loader->register();

AdiantiApplicationConfig::start();

define('PATH', dirname(__FILE__));

setlocale(LC_ALL, 'C');