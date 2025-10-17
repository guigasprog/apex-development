<?php
class SystemSqlLog extends TRecord
{
    const TABLENAME = 'sql_log';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'log'; // CORRIGIDO
    const CREATEDAT = 'logdate';
}