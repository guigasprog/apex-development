<?php
use Adianti\Control\TPage;
use Adianti\Core\AdiantiCoreApplication;

class Logout extends TPage
{
    public function __construct($param)
    {
        parent::__construct($param);
        
        // Simplesmente chama o método de logout que já criamos
        CustomLoginForm::onLogout();
    }
}