<?php
class TenantTheme extends TRecord
{
    const TABLENAME = 'tenant_themes';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'db_permission';
}