<?php
/**
 * Project : everpstools
 * @author Celaneo
 * @copyright Celaneo
 * @license   Tous droits réservés / Le droit d'auteur s'applique (All rights reserved / French copyright law applies)
 * @link https://www.celaneo.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ever_customer` (
        `id_ever_customer` int(10) unsigned NOT NULL auto_increment,
        `id_customer` int(11) unsigned NOT NULL,
        `informations` text DEFAULT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_ever_customer`, `id_customer`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ever_order` (
        `id_ever_order` int(10) unsigned NOT NULL auto_increment,
        `id_order` int(11) unsigned NOT NULL,
        `id_currency` int(1) unsigned NOT NULL DEFAULT 0,
        `informations` text DEFAULT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_ever_order`, `id_order`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ever_carrier` (
        `id_ever_carrier` int(10) unsigned NOT NULL auto_increment,
        `id_carrier` int(11) unsigned NOT NULL,
        `informations` text DEFAULT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_ever_carrier`, `id_carrier`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ever_country` (
        `id_ever_country` int(10) unsigned NOT NULL auto_increment,
        `id_country` int(11) unsigned NOT NULL,
        `informations` text DEFAULT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_ever_country`, `id_country`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ever_module` (
        `id_ever_module` int(10) unsigned NOT NULL auto_increment,
        `module_name` varchar(255) NOT NULL,
        `informations` text DEFAULT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_ever_module`, `module_name`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ever_log` (
        `id_ever_log` int(10) unsigned NOT NULL auto_increment,
        `informations` text DEFAULT NULL,
        `date_add` datetime DEFAULT NULL,
        `date_upd` datetime DEFAULT NULL,
        PRIMARY KEY (`id_ever_log`)
    ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8';

foreach ($sql as $s) {
    if (!Db::getInstance()->execute($s)) {
        return false;
    }
}
