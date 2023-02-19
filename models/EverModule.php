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

    /**
     * Get all PMS order states with ids
     * @return array
    */
    public static function getPmsOrderStates()
    {
        if (EverTools::isModuleAllowed('pms') || EverTools::isModuleAllowed('pmsinderwear')) {
            $pmsOrderStates = [
                [
                    'id_pms_state' => 1,
                    'name' => 'order uploaded'
                ],
                [
                    'id_pms_state' => 10,
                    'name' => 'picking in progress'
                ],
                [
                    'id_pms_state' => 20,
                    'name' => 'picking completed'
                ],
                [
                    'id_pms_state' => 30,
                    'name' => 'sorting to batch completed'
                ],
                [
                    'id_pms_state' => 39,
                    'name' => 'expecting products from warehouses'
                ],
                [
                    'id_pms_state' => 40,
                    'name' => 'sorting to bin completed'
                ],
                [
                    'id_pms_state' => 50,
                    'name' => 'packing (delivery note scanned)'
                ],
                [
                    'id_pms_state' => 60,
                    'name' => 'parcel scanned'
                ],
                [
                    'id_pms_state' => 90,
                    'name' => 'Shipped'
                ],
                [
                    'id_pms_state' => 99,
                    'name' => 'Cancelled'
                ],
            ];
            return $pmsOrderStates;
        }
        return [];
    }

    public static function getFastmagTablesList()
    {

    }

    public static function getAllFastmagResults($query, $trim = true, $addLog = true)
    {
        $result = self::fastmagQuery($query);
        // Error
        if (preg_match("/^KO/", $result)) {
            if ((bool)$addLog === true) {
                // Let's log
                $logContent = '-----------------------------'.PHP_EOL;
                $logContent .= 'Fastmag results KO on date '.date('Y-m-d H:i:s').PHP_EOL;
                $logContent .= 'Query : '.print_r($query, true).PHP_EOL;
                $logContent .= '-----------------------------'.PHP_EOL;
                EverLog::addEverLogFile(
                    EverLog::MODULE_ERRORS_LOGS,
                    'fastmagQuery-KO-'.date('Y-m-d').'.log',
                    $logContent,
                    true
                );
            }
            return false;
        }
        $lines = explode("\n", $result);
        $headers = [];
        $entries = [];
        $k = 0;
        foreach ($lines as $line) {
            if (!trim($line)) {
                continue;
            }
            $datas = explode("\t", preg_replace('#[\n\r]#', '', $line));
            if (!$k) {
                foreach ($datas as $data)
                    array_push($headers, $data);
            } else {
                $entry = [];
                foreach ($datas as $k => $data) {
                    $entry[$headers[$k]] = preg_replace('#\|#', '', $data);
                    if ($trim) {
                        $entry[$headers[$k]] = trim($entry[$headers[$k]]);
                    }
                }
                array_push($entries, $entry);
            }
            $k++;
        }
        return $entries;
    }

    public static function fastmagQuery($query, $edi = false)
    {
        $curl_data['data'] = $query;
        $curl_data['enseigne'] = Configuration::get('FM_ENSEIGNE');
        $curl_data['magasin'] = Configuration::get('FM_MAGASIN');
        $curl_data['compte'] = Configuration::get('FM_COMPTE');
        $curl_data['motpasse'] = Configuration::get('FM_PASSWORD');
        $curl_data['remoteAddr'] = $_SERVER['REMOTE_ADDR'];
        $url = $edi ? Configuration::get('FM_EDI_URL') : Configuration::get('FM_QUERY_URL');
        return EverTools::executeCurl($url, $curl_data);
    }
}