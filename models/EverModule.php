<?php
/**
 * Project : everpstools
 * @author Team Ever
 * @copyright Team Ever
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link https://www.celaneo.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class EverModule extends ObjectModel
{
    public $id_ever_module;
    public $id_customer;
    public $informations;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'ever_module',
        'primary' => 'id_ever_module',
        'multilang' => false,
        'fields' => array(
            'module_name' => array(
                'type' => self::TYPE_STRING,
                'lang' => false,
                'validate' => 'isString',
                'required' => true
            ),
            'informations' => array(
                'type' => self::TYPE_NOTHING,
                'validate' => 'isAnything',
                'required' => false
            ),
            'date_add' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
            'date_upd' => array(
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ),
        )
    );
}