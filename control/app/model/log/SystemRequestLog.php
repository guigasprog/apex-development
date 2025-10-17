<?php
class SystemRequestLog extends TRecord
{
    const TABLENAME = 'system_request_log';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'log'; // Aponta para a conexão em app/config/log.ini
}