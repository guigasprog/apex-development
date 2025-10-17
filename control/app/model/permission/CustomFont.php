<?php
/**
 * Modelo para a tabela custom_fonts
 * Armazena as opções de fontes personalizáveis.
 */
class CustomFont extends TRecord
{
    const TABLENAME  = 'custom_fonts';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';
    const DATABASE   = 'permission';
}