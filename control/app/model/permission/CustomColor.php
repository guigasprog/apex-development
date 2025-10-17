<?php
/**
 * Modelo para a tabela custom_colors
 * Armazena as opções de cores personalizáveis.
 */
class CustomColor extends TRecord
{
    const TABLENAME  = 'custom_colors';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';
    const DATABASE   = 'permission';
}