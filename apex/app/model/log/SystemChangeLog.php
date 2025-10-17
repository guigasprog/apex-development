<?php
class SystemChangeLog extends TRecord
{
    const TABLENAME = 'change_log';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'log'; // CORRIGIDO
    const CREATEDAT = 'logdate';
}