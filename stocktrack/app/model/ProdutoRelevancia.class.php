<?php
use Adianti\Database\TRecord;

class ProdutoRelevancia extends TRecord
{
    const TABLENAME = 'produto_interacoes';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';

    private $produto;

    public function set_produto(Produto $object)
    {
        $this->produto = $object;
        $this->produto_id = $object->id;
    }

    public function get_produto()
    {
        if (empty($this->produto) && !empty($this->produto_id))
        {
            $this->produto = new Produto($this->produto_id);
        }
        return $this->produto;
    }
}