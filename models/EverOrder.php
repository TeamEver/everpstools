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

class EverOrder extends ObjectModel
{
    public $id_ever_order;
    public $id_order;
    public $informations;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'ever_order',
        'primary' => 'id_ever_order',
        'multilang' => false,
        'fields' => array(
            'id_order' => array(
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
     * Get specific information from order
     * @param int id order
     * @param string information asked
     * @return string
    */
    public static function getOrderInfo($idOrder, $information)
    {
        $obj = self::getObjByIdCountry(
            (int)$idOrder
        );
        if (!Validate::isLoadedObject($obj)) {
            return false;
        }
        if (!$obj->informations || empty($obj->informations)) {
            return false;
        }
        $orderInfos = json_decode($obj->informations);
        foreach ($orderInfos as $k => $info) {
            if ($k == $information) {
                return $info;
            }
        }
        return false;
    }

    /**
     * @param $idOrder
     * @return object | false
     */
    public static function getObjByIdOrder($idOrder)
    {
        $sql = new DbQuery;
        $sql->select('id_ever_order');
        $sql->from('ever_order');
        $sql->where('id_order = '.(int)$idOrder);
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
            if ($key == 'user_ip') {
                $return[$key] = pSQL($value);
            }
        }
        return $return;
    }

    public static function getOrderHistoryIdsList($idOrder)
    {
        $sql = new DbQuery;
        $sql->select('id_order_state');
        $sql->from('order_history');
        $sql->where('id_order = '.(int)$idOrder);
        $orderStates = Db::getInstance()->executeS($sql);
        $return = [];
        foreach ($orderStates as $orderState) {
            $return[] = $orderState['id_order_state'];
        }
        return $return;
    }

    public static function getAllowedIdCurrencies()
    {
        $allowedCurrencies = Configuration::get(
            'EVER_ALLOWED_CURRENCIES'
        );
        if (!$allowedCurrencies) {
            return [];
        }
        $allowedCurrencies = json_decode(
            $allowedCurrencies
        );

        if (!is_array($allowedCurrencies)) {
            $allowedCurrencies = array($allowedCurrencies);
        }
        return $allowedCurrencies;
    }

    /**
     * Check if currency is allowed for order
     * @param $idCurrency
     * @return bool
     */
    public static function isAllowedCurrency($idCurrency)
    {
        $allowedCurrencies = self::getAllowedIdCurrencies();
        foreach ($allowedCurrencies as $allowedIdCurrency) {
            if ((int)$idCurrency == (int)$allowedIdCurrency) {
                return true;
            }
        }
        return false;
    }

    public static function setAllowedCurrency()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        if ((bool)self::isAllowedCurrency($cart->id_currency) === true) {
            return;
        }
        $old_currency = $cart->id_currency;
        $context->cookie->__set('id_old_currencycart', (int)$old_currency);
        $cart->id_currency = 1;
        $context->currency->id = 1;
        if ((bool)$context->customer->logged === true) {
            $iwCustomer = EverCustomer::getObjByIdCustomer(
                $context->customer->id
            );
            $informations = [];
            if (!empty($iwCustomer->informations)) {
                $informations[] = json_decode(
                    $iwCustomer->informations,
                    true
                );
            }
            $informations['id_old_currencycart'] = (int)$old_currency;
            $iwCustomer->informations = json_encode($informations);
            $iwCustomer->save();
        }
        return $cart->save();
    }

    public static function setOldCurrency()
    {
        $context = Context::getContext();
        $cart = $context->cart;
        if ($context->cookie->__isset('id_old_currencycart')) {
            $old_currency = (int)$context->cookie->__get('id_old_currencycart');
            $cart->id_currency = $old_currency;
            $context->currency->id = $old_currency;
            return $cart->save();
        } else {
            $iwCustomer = EverCustomer::getObjByIdCustomer(
                $context->customer->id
            );
            if (!Validate::isLoadedObject($iwCustomer)) {
                return;
            }
            if (!empty($iwCustomer->informations)) {
                $informations = json_decode(
                    $iwCustomer->informations,
                    true
                );
                if (isset($informations['id_old_currencycart'])
                    && (int)$informations['id_old_currencycart'] > 0
                ) {
                    $old_currency = (int)$informations['id_old_currencycart'];
                    $cart->id_currency = $old_currency;
                    $context->currency->id = $old_currency;
                    return $cart->save();
                }
            }
        }
        return false;
    }

    public static function getCancelledEverOrderStates()
    {
        $order_states = json_decode(
            Configuration::get(
                'EVER_CANCELLED_STATES_ID'
            )
        );
        if (!is_array($order_states)) {
            $order_states = array($order_states);
        }
        return $order_states;
    }
}