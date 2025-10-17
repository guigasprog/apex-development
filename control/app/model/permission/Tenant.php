<?php
class Tenant extends TRecord
{
    const TABLENAME = 'tenants';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'db_permission';
}