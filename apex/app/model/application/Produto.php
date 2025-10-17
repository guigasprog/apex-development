<?php
class Produto extends TRecord
{
    const TABLENAME = 'produtos';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'db_application_template'; // Conexão Padrão/Template
    
    private $categoria;

    /**
     * Retorna o objeto Categoria associado
     */
    public function get_categoria()
    {
        if (empty($this->categoria))
        {
            $this->categoria = new Categoria($this->categoria_id);
        }
        return $this->categoria;
    }
}