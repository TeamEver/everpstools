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
            'IW_ALLOWED_CURRENCIES'
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

    /**
     * Refund order, check vouchers, change order state if required
     * @param object order
     * @param float amount to refund
     * @param bool fix voucher or not
     * @param int new order state ID
     * @param bool write order log or not
     * @return bool
    */
    public static function setOrderRefund($order, $amount, $fixVoucher = false, $newOrderState = 0, $addLog = false)
    {
        if (!Validate::isLoadedObject($order)) {
            if ((bool)$addLog === true) {
                $content = 'Order is not valid'.PHP_EOL;
                EverLog::addEverLogFile(
                    EverLog::ORDER_ERRORS_LOGS,
                    'order-0',
                    $content,
                    true
                );
            }
            return false;
        }
        if ((float)$amount <= 0) {
            if ((bool)$addLog === true) {
                $content = 'Cant refund 0 amount on order'.PHP_EOL;
                EverLog::addEverLogFile(
                    EverLog::ORDER_ERRORS_LOGS,
                    'order-'.$order->id,
                    $content,
                    true
                );
            }
            return false;
        }
        if ((bool)$fixVoucher === true) {
            // code...
        }
        if ((int)$newOrderState > 0) {
            $orderState = new OrderState(
                (int)$newOrderState
            );
            if (!Validate::isLoadedObject($orderState)) {
                if ((bool)$addLog === true) {
                    $content = 'Order state is not valid. Given ID : '.$newOrderState.PHP_EOL;
                    $content .= 'Order ID : '.$order->id.PHP_EOL;
                    EverLog::addEverLogFile(
                        EverLog::ORDER_ERRORS_LOGS,
                        'order-'.$order->id,
                        $content,
                        true
                    );
                }
                return false;
            }
        }
        if ((bool)$addLog === true) {
            return EverLog::addEverLogFileFile(
                EverLog::ORDER_LOGS,
                'order-'.$order->id,
                $content,
                true
            );
        }
        return $order->update();
    }

    public static function checkOrderStateUpdate($order, $oldStateId, $newStateId)
    {

    }

    public static function orderHasDiscounts($order)
    {   
        $context = Context::getContext();
        // In progress
        $totalAmount = (float)Tools::getValue('totalAmount', 0);
        $totalAmount = 0;

        $order_details = $order->getProductsDetail();
        $still_some_products = false;

        $array_discounts = array();

        $cancelQuantity = Tools::getValue('cancelQuantity');
        $deletedProduct = Tools::getValue('delete_product', false);
        $deleted_products = array();

        if ($deletedProduct) {
            foreach ($deletedProduct as $id_order_detail => $status) {
                $cancel_quantity = Tools::getIsset($cancelQuantity[$id_order_detail]) ? (int)$cancelQuantity[$id_order_detail] : 0;
                if ($cancel_quantity <= 0) {
                    continue;
                }
                $deleted_products[$id_order_detail] = $cancel_quantity;
            }
        }

        foreach ($order_details as $order_detail) {
            $id_order_detail = $order_detail['id_order_detail'];
            if ($order_detail['product_reference'] == 'magOptionSerenite') {
                continue;
            }
            if (!$deleted_products || !Tools::getIsset($deleted_products[$id_order_detail])) {
                $still_some_products = true;
            } else {
                $totalAmount += $order_detail['unit_price_tax_incl'] * $deleted_products[$order_detail['id_order_detail']];

                if ($deleted_products[$order_detail['id_order_detail']] < $order_detail['product_quantity']) {
                    $still_some_products = true;
                }
            }
        }

        if (!$still_some_products) {
            die(Tools::jsonEncode(
                array(
                    'status' => true,
                    'has_discounts' => false
                )
            ));
        }

        $discounts = $order->getCartRules();
        
        foreach ($discounts as $discount) {
            if ($discount['reduction_percent'] != '0.00') {
                $discount['tva'] = round((($discount['value'] / $discount['value_tax_excl']) - 1) * 100);
                $discount['calculated_tax_incl'] = round(round($order->total_products_wt - $totalAmount, 2) * $discount['reduction_percent'] / 100, 2);
                $discount['calculated_tax_excl'] = round($discount['calculated_tax_incl'] / (1 + $discount['tva'] / 100), 2);

                $array_discounts[] = $discount;
            } elseif ($discount['reduction_amount'] != '0.00') {
                $discount['tva'] = round((($discount['value'] / $discount['value_tax_excl']) - 1) * 100);
                if ($discount['reduction_tax'] === '0') {
                    $discount['calculated_tax_excl'] = $discount['reduction_amount'];
                    $discount['calculated_tax_incl'] = round($discount['calculated_tax_excl'] * (1 + $discount['tva'] / 100), 2);
                } else {
                    $discount['calculated_tax_incl'] = $discount['reduction_amount'];
                    $discount['calculated_tax_excl'] = round($discount['calculated_tax_incl'] / (1 + $discount['tva'] / 100), 2);
                }

                $array_discounts[] = $discount;
            }
        }

        if ($array_discounts) {
            $context->smarty->assign(array(
                'discounts' => $array_discounts,
                'amount_after' => round($order->total_products_wt - $totalAmount, 2)
            ));
            $template = $context->smarty->fetch(_PS_MODULE_DIR_.'everpstools/views/templates/admin/order/popup-discount-form.tpl');
            
            die(Tools::jsonEncode(
                array(
                    'status' => true,
                    'has_discounts' => true,
                    'discounts' => $array_discounts,
                    'template' => $template
                )
            ));
        } else {
            die(Tools::jsonEncode(
                array(
                    'status' => true,
                    'has_discounts' => false
                )
            ));
        }
    }

    public static function orderMissingProductProcess($order)
    {
        if (!Validate::isLoadedObject($order)) {
            return;
        }
        $iwConf = new Ever_configuration();
        if (!EverTools::isModuleAllowed('fastmag')) {
            return;
        }
        $fastmag = new Fastmag();
        $context = Context::getContext();
        $id_order_detail = Tools::getValue('missing_product');

        $order = new Order((int)Tools::getValue('id_order'));
        $discounts = Tools::getValue('discounts');

        if (count($discounts)) {
            foreach ($discounts as $id_cart_rule => $discount) {
                Db::getInstance()->update('order_cart_rule', array(
                    'value' => $discount['tax_incl'],
                    'value_tax_excl' => $discount['tax_excl']
                ), 'id_order = ' . (int)$order->id . ' AND id_cart_rule = ' . (int)$id_cart_rule);
            }

            $order_discounts = $order->getCartRules();
            $total_discount_tax_incl = 0;
            $total_discount_tax_excl = 0;
            foreach ($order_discounts as $order_discount) {
                $total_discount_tax_incl += $order_discount['value'];
                $total_discount_tax_excl += $order_discount['value_tax_excl'];
            }

            $order->total_discounts_tax_incl = $total_discount_tax_incl;
            $order->total_discounts_tax_excl = $order->total_discounts = $total_discount_tax_excl;

            $order->total_paid = $order->total_paid_tax_incl = $order->total_products_wt + $order->total_shipping_tax_incl + $order->total_wrapping_tax_incl - $order->total_discounts_tax_incl;
            $order->total_paid_tax_excl = $order->total_products + $order->total_shipping_tax_excl + $order->total_wrapping_tax_excl - $order->total_discounts_tax_excl;

            if ($order->total_paid < 0)
                $order->total_paid = 0;

            if ($order->total_paid_tax_excl < 0) {
                $order->total_paid_tax_excl = 0;
            }
            if ($order->total_paid_tax_incl < 0) {
                $order->total_paid_tax_incl = 0;
            }
            $order->save();

        }

        // Check FID User
        $products = $order->getProducts();
        foreach ($products AS $product) {
            Tools::getValue('taxeRate')[$product['id_order_detail']] = $product['tax_rate'];
            Tools::getValue('priceHT')[$product['id_order_detail']] = $product['product_price'];
            Tools::getValue('priceTTC')[$product['id_order_detail']] = $product['product_price_wt'];
            Tools::getValue('qty')[$product['id_order_detail']] = $product['product_quantity'];
        }

        $modifyorders = new modifyorders();
        $change_s03 = false;

        foreach (Tools::getValue('correct_stock') as $id_order_detail => $x) {
            foreach ($x as $shop_name => $y) {
                Hook::exec('registerMissingProduct', array('id_order_detail' => $id_order_detail, 'quantity' => Tools::getValue('cancelQuantity')[$id_order_detail], 'change_stock' => 1, 'shop_name' => $shop_name));
            }
        }

        foreach (Tools::getValue('delete_product') as $id_order_detail => $x) {

            $cancel_quantity = isset(Tools::getValue('cancelQuantity')[$id_order_detail]) ? (int)Tools::getValue('cancelQuantity')[$id_order_detail] : 0;
            if ($cancel_quantity <= 0)
                continue;

            //update order
            if ((Tools::getValue('qty')[$id_order_detail] - $cancel_quantity) <= 0) {
                // Task https://projets.celaneo.com/issues/14829
                // Si le produit est le dernier de la commande, nous ne le supprimons pas
                    $details = $order->getProductsDetail();
                    $count = 0;
                    foreach ($details as $detail) {
                        if ($detail['product_reference'] != 'magOptionSerenite' && $detail['product_reference'] != 'HIPLI')
                            $count += 1;
                    }
                if ($count > 1) {
                    $modifyorders->delProduct($order->id, array($id_order_detail => 1));
                } else {
                    $change_s03 = true;
                }
            } else
                $modifyorders->updateQuantities($order->id, array($id_order_detail => (Tools::getValue('qty')[$id_order_detail] - $cancel_quantity)), NULL);

        }

        if (count(Tools::getValue('delete_product')) && !$change_s03 && !fidelite::isFidAndFreeDelivery($order->id_customer, $order->id_cart)) {

            $is_rule_exist = true;
            $rule_counter = 1;
            while ($is_rule_exist) {
                if (!CartRule::cartRuleExists('FP' . $order->id . $rule_counter))
                    $is_rule_exist = false;
                else
                    $rule_counter++;
            }

            $cart_rule = new CartRule();
            $cart_rule->id_customer = (int)$order->id_customer;
            $cart_rule->description = $iwConf->l('Free shipping');
            $cart_rule->name = Array(1 => $iwConf->l('Free delivery'), 2 => $iwConf->l('Livraison gratuite'));
            $cart_rule->code = 'FP' . $order->id . $rule_counter;
            $cart_rule->quantity = 1;
            $cart_rule->partial_use = 0;
            $cart_rule->quantity_per_user = 1;
            $cart_rule->reduction_tax = 1;
            $cart_rule->date_from = date("Y-m-d");
            $cart_rule->date_to = date("Y-m-d h:m:s", mktime(0, 0, 0, date('m'), date('d'), date('Y') + 1));
            $cart_rule->free_shipping = 1;
            $cart_rule->active = 1;
            $cart_rule->add();

            Db::getInstance()->insert('cart_rules_options', array(
                'id_cart_rule' => $cart_rule->id,
                'cumul_percent' => '0',
                'cumul_amount' => '1',
                'cumul_discount' => '1'
            ));

            $message = new Message();
            $message->id_employee = $context->employee ? $context->employee->id : 0;
            $message->message = $iwConf->l('Bon de réduction livraison pour manquant :') . $cart_rule->code;
            $message->id_order = $order->id;
            $message->private = 1;
            $add = $message->add();

            $context->cookie->bo_messages .= $message->id . '-';

        }

        $amountRemboursement = floatval(Tools::getValue('returnDeleteProduct_amount'));
        if ($amountRemboursement > 0) {

        // GENERATION AVOIR
        $invoices = $order->getInvoicesCollection()->getFirst();
        if ($invoices && $change_s03) {
                self::addOrderSlip($order->id, $amountRemboursement, $iwConf->l('Avoir commande #') . $order->id, $order->payment, Tools::getValue('returnDeleteProduct_fdp') == '1');
            }
            // END GENERATION AVOIR

        }

        $total_shipping = $order->total_shipping_tax_incl;
        if (!$change_s03) {
            if (Tools::getValue('returnDeleteProduct_fdp') == '1') {
                $order->total_shipping = $order->total_shipping_tax_incl = $order->total_shipping_tax_excl = 0;
                $order->save();
                $modifyorders->updateInvoice($order->id, 0, 0);
            }
        }

        $modifyorders->updateOrder($order->id, false);
        $fastmag->hookActionValidateOrder(array(
            'order' => $order
        ));

        // START REFUND

        if (Tools::getValue('returnDeleteProduct_fdp') == '1') {
            $amountRemboursement += $total_shipping;
        }

        if ($amountRemboursement > 0) {
            if ($order->module == 'paybox') {
                include_once(_PS_MODULE_DIR_ . 'paybox/paybox.php');
                $paybox = new Paybox();
                $paybox->shellRemboursement($order->id, $amountRemboursement);
            } elseif ($order->module == 'amex') {
                include_once(_PS_MODULE_DIR_ . 'amex/amex.php');
                $amex = new Amex();
                $amex->shellRemboursement($order->id, $amountRemboursement);
            } else {
                Hook::exec('paymentRefund', array('module' => $order->module, 'id_order' => $order->id, 'amount' => $amountRemboursement));
            }
        }

        // END REFUND

        if (Tools::getValue('returnDeleteProduct_mail') == '1') {

            if ($change_s03) {
                $template = 'manquant_fid_S03';
            } elseif (!fidelite::isFidAndFreeDelivery($order->id_customer, $order->id_cart)) {
                $template = 'manquant_non_fid';
            } else {
                $template = 'manquant_fid';
            }

            $ordersProducts = $order->getProductsDetail();
            $listProducts = array();
            $nb = 0;
            foreach ($ordersProducts as $product) {
                if ($product['product_reference'] != 'magOptionSerenite' && $product['product_reference'] != 'HIPLI') {
                    $nb += $product['product_quantity'];
                }
            }

            if ($order->id_lang == 1) {
                $itemsLang = Array(
                    1 => $iwConf->l('article va être expédié'),
                    2 => $iwConf->l('articles vont être expédiés'),
                );
                $account = Array(
                    'paypal' => $iwConf->l('votre compte Paypal'),
                    'bankwire' => $iwConf->l('votre compte bancaire'),
                    'carte bancaire' => $iwConf->l('le compte de votre carte bancaire'),
                    'cheque' => $iwConf->l('votre compte chèque'),
                    'paybox' => $iwConf->l('le compte de votre carte bancaire'),
                    'kwixo' => $iwConf->l('votre compte Kwixo'),
                    'amazon' => $iwConf->l('votre compte Amazon'),
                    'amex' => $iwConf->l('le compte de votre carte American Express'),
                    'stripepayment' => $iwConf->l('le compte de votre carte bancaire')
                );
            } else {
                $itemsLang = Array(
                    1 => $iwConf->l('item will be shipped soon'),
                    2 => $iwConf->l('items will be shipped soon'),
                );
                $account = Array(
                    'paypal' => $iwConf->l('Paypal account'),
                    'bankwire' => $iwConf->l('bank account'),
                    'carte bancaire' => $iwConf->l('credit card account'),
                    'cheque' => $iwConf->l('bank account'),
                    'paybox' => $iwConf->l('credit card account'),
                    'kwixo' => $iwConf->l('Kwixo account'),
                    'amazon' => $iwConf->l('Amazon account'),
                    'amex' => $iwConf->l('American Express account'),
                    'stripepayment' => $iwConf->l('credit card account')
                );
            }

            foreach (Tools::getValue('cancelQuantity') as $id_order_detail => $qty) {
                if ($qty == '') {
                    continue;
                }

                if (!isset($listProducts[sha1(Tools::getValue('product_name')[$id_order_detail])])) {
                    $listProducts[sha1(Tools::getValue('product_name')[$id_order_detail])] = Tools::getValue('product_name')[$id_order_detail];
                }
            }

            $order_currency = $order->id_currency;
            if (strtolower($order->payment) == 'paypal') {
                $order_conversion = Db::getInstance()->getValue('SELECT conversion_rate FROM ' . _DB_PREFIX_ . 'order_conversion WHERE id_order = ' . (int)$order->id);
                if ($order_conversion) {
                    $amountRemboursement = Tools::ps_round($amountRemboursement * $order_conversion, 2);
                }
                if ($order->origin_currency) {
                    $order_currency = $order->origin_currency;
                }
            }

            $customer = new Customer((int)$order->id_customer);
            $vars = Array(
                '{NOM_ITEM}' => implode(' ', $listProducts),
                '{P_ITEMS}' => $itemsLang[($nb > 1 ? 2 : 1)],
                '{NB_ITEMS}' => $nb,
                '{MONTANT}' => Tools::displayPrice($amountRemboursement, new Currency((int)$order_currency)),
                '{ACCOUNT_NAME}' => $account[strtolower($order->module)],
                '{DISCOUNT_NAME}' => isset($cart_rule) ? $cart_rule->code : '',
                '{order_name}' => $order->id,
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
            );

            $message = new Message();
            $message->id_employee = $context->employee ? $context->employee->id : 0;
            $message->message = $iwConf->l('E-mail manquant envoyé');
            $message->id_order = $order->id;
            $message->private = 1;
            $add = $message->add();

            $context->cookie->bo_messages .= $message->id . '-';

            $custEmail = $customer->email;

            global $_LANGMAIL;
            if ($order->id_lang == 1) {
                $subject = ($iwConf->l('Nouveau message concernant votre commande'));
            } else {
                $subject = ($iwConf->l('Missing product for your Order'));
            }

            Mail::Send(intval($order->id_lang), $template, ((is_array($_LANGMAIL) AND key_exists($subject, $_LANGMAIL)) ? $_LANGMAIL[$subject] : $subject) . ' ' . (int)$order->id, $vars, $custEmail, $customer->lastname . ' ' . $customer->firstname, Configuration::get('PS_SHOP_EMAIL'), 'INDERWEAR', null, null);
            Mail::Send(intval($order->id_lang), $template, ((is_array($_LANGMAIL) AND key_exists($subject, $_LANGMAIL)) ? $_LANGMAIL[$subject] : $subject) . ' ' . (int)$order->id, $vars, Configuration::get('IW_SERVICE_MAIL'), $customer->lastname . ' ' . $customer->firstname, Configuration::get('PS_SHOP_EMAIL'), 'INDERWEAR', null, null);
        }

        if (count(Tools::getValue('delete_product')) && !$change_s03) {
            $r_products = Db::getInstance()->executeS('SELECT id_order_detail, magasinFrom FROM ' . _DB_PREFIX_ . 'magavenue_resa_magasins_details WHERE id_order = ' . $order->id . ' AND magasinFrom != ""');

            $formatted_products = array();
            foreach ($r_products as $r_product) {
                $formatted_products[$r_product['id_order_detail']] = $r_product['magasinFrom'];
            }

            $reservation = new Reservation();

            if (!class_exists('ExportOrders', false)) {
                include_once(_PS_MODULE_DIR_ . 'pmsinderwear/pmsinderwear.php');
            }

            $reservation->process($order, true);
            $reservation->manageResa2Vente($order->id);

            $r_products = Db::getInstance()->executeS('SELECT id_order_detail, magasinFrom FROM ' . _DB_PREFIX_ . 'magavenue_resa_magasins_details WHERE id_order = ' . $order->id);
            foreach ($r_products as $r_product) {
                if ($r_product['magasinFrom'] != $formatted_products[$r_product['id_order_detail']]) {
                    Db::getInstance()->update('magavenue_resa_magasins_details', array('magasinFrom' => $formatted_products[$r_product['id_order_detail']]), 'id_order_detail = ' . $r_product['id_order_detail']);
                }
            }
        }

        if (count(Tools::getValue('delete_product')) && !$change_s03) {
            Hook::exec(
                'updatePMSOrder',
                [
                    'id_order' => (int)$order->id,
                    'origin' => $iwConf->l('Article manquant')
                ]
            );
        }

        if ($change_s03) {
            $history = new OrderHistory();
            $history->id_order = $order->id;
            $history->date_add = date('Y-m-d H:i:s');
            $history->id_employee = $context->employee->id;
            $history->changeIdOrderState(6, $order);
            $history->addWithemail(false, '');
        }

        Tools::redirectAdmin('index.php?controller=AdminOrders&id_order='.(int)Tools::getValue('id_order').'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders'));
    }

    public static function addOrderSlip($id_order, $slipTotal, $slipText, $slipMethod, $shipping_cost = false)
    {

        $order = new Order(
            (int)$id_order
        );

        $tax = 0;
        foreach ($order->getOrderDetailTaxes() as $order_detail_tax) {
            if ($order_detail_tax['rate'] > $tax) {
                $tax = $order_detail_tax['rate'];
            }
        }

        if (!$tax) {
            $taxName = 'HT';
            $taxRate = 0;
            $value = (float)str_replace(',', '.', $slipTotal);
        } else {
            $taxName = $tax;
            $taxRate = $tax;
            $value = (float)str_replace(',', '.', $slipTotal) / (1 + ($tax / 100));
        }

        $amount = (float)str_replace(',', '.', $slipTotal);

        //Creation de lavoir
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = $order->id_customer;
        $orderSlip->id_order = (int)$id_order;
        $orderSlip->shipping_cost = $shipping_cost;
        $orderSlip->payment_method = $slipMethod;
        $orderSlip->conversion_rate = 1;
        $orderSlip->total_products_tax_excl = 0;
        $orderSlip->total_products_tax_incl = 0;
        $orderSlip->total_shipping_tax_excl = $shipping_cost ? $order->total_shipping_tax_excl : 0;
        $orderSlip->total_shipping_tax_incl = $shipping_cost ? $order->total_shipping_tax_incl : 0;
        $orderSlip->amount = $amount;
        $orderSlip->order_slip_type = 2;
        $orderSlip->add();

        //Ajout des detail de lavoir dans la base de donnees

        $parameters = array(
            'id_order_slip' => $orderSlip->id,
            'libelle' => pSQL($slipText),
            'tarif' => pSQL(str_replace(',', '.', $value)),
            'tax_name' => $taxName,
            'tax_rate' => $taxRate
        );

        Db::getInstance()->insert('magavenue_orderslip', $parameters);
        return $orderSlip->id;
    }

    public static function dropMixteDatas($orderId)
    {
        $tableExists = EverTools::checkDbForTableExists('mixte');
        if (!$tableExists) {
            return false;
        }
        if ((bool)Configuration::get('IW_ALLOW_DROP_MIXTE') === true) {
            return Db::getInstance()->delete(
                'mixte',
                'id_order = '.(int)$orderId
            );
        }
    }

    public static function getCancelledEverOrderStates()
    {
        $order_states = json_decode(
            Configuration::get(
                'IW_CANCELLED_STATES_ID'
            )
        );
        if (!is_array($order_states)) {
            $order_states = array($order_states);
        }
        return $order_states;
    }

    /**
     * @param $idOrder
     * @return bool
     */
    public function migrateMagavenueData($idOrder)
    {
        $order = new Order(
            (int)$idOrder
        );
        $query = new DbQuery();
        $query->select('ip, country')
            ->from('magavenue_ipbyordermagavenue')
            ->where('id_cart = '.(int)$order->id_cart);

        $row = Db::getInstance()->getRow($query->build());
        if ($row && is_array($row) && count($row) > 0) {
            $informations = [];
            if (!empty($iwCustomer->informations)) {
                $informations[] = json_decode(
                    $iwCustomer->informations,
                    true
                );
            }
            $informations[] = [
                'user_ip' => $row['ip']
            ];
            $iwCustomer->id_customer = (int)$order->id_customer;
            $informations = call_user_func_array('array_merge', $informations);
            $this->informations = json_encode($informations);
            return $this->save();
        }
        return false;
    }
}