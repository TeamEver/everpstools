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

class EverCarrier extends ObjectModel
{
    public $id_ever_carrier;
    public $id_carrier;
    public $informations;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'ever_carrier',
        'primary' => 'id_ever_carrier',
        'multilang' => false,
        'fields' => array(
            'id_carrier' => array(
                'type' => self::TYPE_INT,
                'lang' => false,
                'validate' => 'isUnsignedInt',
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
     * Get specific information from carrier
     * @param int id carrier
     * @param string information asked
     * @return string
    */
    public static function getCarrierInfo($idCarrier, $information)
    {
        $obj = self::getObjByIdCarrier(
            (int)$idCarrier
        );
        if (!Validate::isLoadedObject($obj)) {
            return false;
        }
        if (!$obj->informations || empty($obj->informations)) {
            return false;
        }
        $carrierInfos = json_decode($obj->informations, true);
        foreach ($carrierInfos as $k => $info) {
            if ($k == $information) {
                return $info;
            }
        }
        return false;
    }

    /**
     * @param $idCarrier
     * @return object | false
     */
    public static function getObjByIdCarrier($idCarrier)
    {
        $sql = new DbQuery;
        $sql->select('id_ever_carrier');
        $sql->from('ever_carrier');
        $sql->where('id_carrier = '.(int)$idCarrier);
        $idObj = Db::getInstance()->getValue($sql);
        $obj = new self(
            (int)$idObj
        );
        if (Validate::isLoadedObject($obj)) {
            return $obj;
        } else {
            return false;
        }
    }

    /**
     * @param $idCarrier
     * @return bool
     */
    public static function isSpring(int $idCarrier)
    {
        $springIdCarrier = Configuration::get('IW_SPRING_CARRIER_ID');
        if (!$springIdCarrier) {
            return false;
        }
        if ((int)$idCarrier == (int)$springIdCarrier) {
            return true;
        }
        return false;
    }

    /**
     * Get PMS code for given carrier id reference
     * @return string | null
    */
    public static function getPmsCarrierCodeByCarrierReference($carrierIdReference)
    {
        return Configuration::get(
            'PMS_CARRIER_CODE_'.(int)$carrierIdReference
        );
    }

    /**
     * Get carrier id reference for given PMS code
     * @return string | null
    */
    public static function getCarrierIdByPmsCarrierCode($pmsCarrierCode)
    {
        return Configuration::get(
            'PMS_CARRIER_ID_REFERENCE_'.(int)$pmsCarrierCode
        );
    }
}