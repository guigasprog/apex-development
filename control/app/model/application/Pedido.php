<?php
class Pedido extends TRecord
{
    const TABLENAME = 'pedidos';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'db_application_template';
}