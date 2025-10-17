<?php
/**
 * Modelo para a tabela custom_hover_effects
 * Armazena os efeitos de hover personalizáveis.
 */
class CustomHoverEffect extends TRecord
{
    const TABLENAME  = 'custom_hover_effects';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'max';
    const DATABASE   = 'permission';
}