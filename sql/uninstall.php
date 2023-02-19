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
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ever_customer`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ever_order`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ever_carrier`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ever_country`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ever_module`;';
$sql[] = 'DROP TABLE IF EXISTS `'._DB_PREFIX_.'ever_log`;';
