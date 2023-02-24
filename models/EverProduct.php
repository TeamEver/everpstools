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

class EverProduct extends ObjectModel
{
    public $id_ever_product;
    public $id_product;
    public $informations;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'ever_product',
        'primary' => 'id_ever_product',
        'multilang' => false,
        'fields' => array(
            'id_product' => array(
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
     * Get specific information from product
     * @param int id product
     * @param string information asked
     * @return string
    */
    public static function getProductInfo($idProduct, $information)
    {
        $obj = self::getObjByIdProduct(
            (int)$idProduct
        );
        if (!Validate::isLoadedObject($obj)) {
            return false;
        }
        if (!$obj->informations || empty($obj->informations)) {
            return false;
        }
        $productInfos = json_decode($obj->informations, true);
        foreach ($productInfos as $k => $info) {
            if ($k == $information) {
                return $info;
            }
        }
        return false;
    }

    /**
     * @param $idProduct
     * @return object | false
     */
    public static function getObjByIdProduct($idProduct)
    {
        $sql = new DbQuery;
        $sql->select('id_ever_product');
        $sql->from('ever_product');
        $sql->where('id_product = '.(int)$idProduct);
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
     * Auto set product into categories
     * @param int id product
     * @param array categories
     * @param int id shop
     * @return bool
    */
    public static function setProductCategories($idProduct, $categories, $idShop)
    {

    }
}