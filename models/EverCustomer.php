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

class EverCustomer extends ObjectModel
{
    public $id_ever_customer;
    public $id_customer;
    public $informations;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'ever_customer',
        'primary' => 'id_ever_customer',
        'multilang' => false,
        'fields' => array(
            'id_customer' => array(
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
     * @param $idCustomer
     * @return object | false
     */
    public static function getObjByIdCustomer($idCustomer)
    {
        $sql = new DbQuery;
        $sql->select('id_ever_customer');
        $sql->from('ever_customer');
        $sql->where('id_customer = '.(int)$idCustomer);
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
     * Get specific information from customer
     * @param int id customer
     * @param string information asked
     * @return string
    */
    public static function getCustomerInfo($idCustomer, $information)
    {
        $obj = self::getObjByIdCustomer(
            (int)$idCustomer
        );
        if (!Validate::isLoadedObject($obj)) {
            return false;
        }
        if (!$obj->informations || empty($obj->informations)) {
            return false;
        }
        $customerInfos = json_decode($obj->informations, true);
        foreach ($customerInfos as $k => $info) {
            if ($k == $information) {
                return $info;
            }
        }
        return false;
    }

    public function parseObjInformations()
    {
        if (!$this->informations
            || empty($this->informations)
        ) {
            return [];
        }
        $return = [];
        $informations = json_decode(
            $this->informations,
            true
        );
        if (isset($informations[0]) && $informations[0]) {
            $informations = $informations[0];
        }
        foreach ($informations as $key => $value) {
            if ($key == 'id_currency'
                || $key == 'currency_id'
                || $key == 'id_old_currencycart'
                || $key == 'id_old_currency'
            ) {
                $currency = new Currency(
                    (int)$value
                );
                $return[$key] = $currency->name;
            }
            if ($key == 'id_customer') {
                $customer = new Customer(
                    (int)$value
                );
                $return[$key] = $customer->firstname.' '.$customer->lastname;
            }
            if ($key == 'id_country'
                || $key == 'id_delivery_country'
                || $key == 'id_shipping_country'
                || $key == 'id_billing_country'
            ) {
                $country = new Country(
                    (int)$value
                );
                $return[$key] = $country->name;
            }
            if ($key == 'has_been_razed') {
                $return[$key] = (bool)$value;
            }
        }
        return $return;
    }
}
