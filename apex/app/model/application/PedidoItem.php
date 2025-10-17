<?php
class PedidoItem extends TRecord
{
    const TABLENAME = 'pedido_itens';
    const PRIMARYKEY= 'id';
    const IDPOLICY =  'serial';
    const DATABASE = 'db_application_template';
}