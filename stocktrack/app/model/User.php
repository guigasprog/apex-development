<?php
use Adianti\Database\TRecord;

class User extends TRecord
{
    const TABLENAME = 'users';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';

    /**
     * Opcional, mas recomendado: Carrega o tenant associado
     */
    public function get_tenant()
    {
        if (isset($this->tenant_id))
        {
            return new Tenant($this->tenant_id);
        }
    }
}