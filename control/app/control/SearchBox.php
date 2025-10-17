<?php
use Adianti\Control\TPage;
use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreApplication;
use Adianti\Registry\TSession;
use Adianti\Widget\Form\TForm;
use Adianti\Widget\Form\TMultiSearch;
use Adianti\Widget\Menu\TMenuParser;

class SearchBox extends TPage
{
    private $form;
    
    public function __construct()
    {
        parent::__construct('search_box');
        
        $this->form = new TForm('search_box');
        
        $input = new TMultiSearch('input');
        $input->setSize('calc(100% - 20px)',28);
        $input->addItems( $this->getPrograms() );
        $input->setMinLength(1);
        $input->setMaxSize(1);
        $input->setOption('dropdownParent', '#sidebar');
        $input->setChangeAction(new TAction(array('SearchBox', 'loadProgram')));
        
        $this->form->add($input);
        $this->form->setFields(array($input));
        parent::add($this->form);
    }
    
    public function getPrograms()
    {
        $menu_file = TSession::getValue('is_super_admin') ? 'menu-admin.xml' : 'menu-tenant.xml';
        
        if (file_exists($menu_file))
        {
            $menu = new TMenuParser($menu_file);
            return $menu->getIndexedPrograms();
        }
        return [];
    }
    
    public static function loadProgram($param)
    {
        if (isset($param['input'][0]))
        {
            $program = $param['input'][0];
            if ($program)
            {
                AdiantiCoreApplication::loadPage($program); // CORRIGIDO
                TScript::create('Template.findQueryStringMenuItem(true)', true, 300);
            }
        }
    }
}