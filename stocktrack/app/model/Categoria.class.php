<?php
use Adianti\Database\TRecord;

class Categoria extends TRecord
{
    const TABLENAME = 'categorias';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('nome');
        parent::addAttribute('descricao');
    }

    
}
