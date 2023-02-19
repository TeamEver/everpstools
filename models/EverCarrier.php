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
}