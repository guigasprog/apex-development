<?php
class SystemAccessLog extends TRecord
{
    const TABLENAME = 'access_log';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'log';
}