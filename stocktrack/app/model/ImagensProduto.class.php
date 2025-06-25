<?php

use Adianti\Database\TRecord;

class ImagensProduto extends TRecord
{
    const TABLENAME = 'imagens_produto ';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = NULL)
    {
        parent::__construct($id);

        parent::addAttribute('produto_id');
        parent::addAttribute('image_url');
        parent::addAttribute('descricao');
    }
}
