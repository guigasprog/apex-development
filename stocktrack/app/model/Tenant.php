<?php
use Adianti\Database\TRecord;

class Tenant extends TRecord
{
    const TABLENAME = 'tenants';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
}