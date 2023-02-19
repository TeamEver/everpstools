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
require_once _PS_MODULE_DIR_.'everpstools/models/EverTools.php';
require_once _PS_MODULE_DIR_.'everpstools/models/EverCarrier.php';
require_once _PS_MODULE_DIR_.'everpstools/models/EverCountry.php';
require_once _PS_MODULE_DIR_.'everpstools/models/EverCustomer.php';
require_once _PS_MODULE_DIR_.'everpstools/models/EverOrder.php';
require_once _PS_MODULE_DIR_.'everpstools/models/EverModule.php';
require_once _PS_MODULE_DIR_.'everpstools/models/EverLog.php';

class Everpstools extends Module
{
    private $html;
    private $postErrors = [];
    private $objectsList = [
        _PS_MODULE_DIR_.'everpstools/models/EverTools.php',
        _PS_MODULE_DIR_.'everpstools/models/EverCarrier.php',
        _PS_MODULE_DIR_.'everpstools/models/EverCountry.php',
        _PS_MODULE_DIR_.'everpstools/models/EverCustomer.php',
        _PS_MODULE_DIR_.'everpstools/models/EverOrder.php',
        _PS_MODULE_DIR_.'everpstools/models/EverModule.php',
        _PS_MODULE_DIR_.'everpstools/models/EverLog.php',
    ];

    public function __construct()
    {
        $this->name = 'everpstools';
        $this->displayName = $this->l('Tools for developpers');
        $this->description = $this->l('Useful tools for developpers');
        $this->tab = 'administration';
        $this->version = '2.3.0';
        $this->author = 'Team Ever';
        $this->need_instance = 1;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        parent::__construct();
    }

    public function install()
    {
        // Install Ajax invisible tab
        $this->installModuleTabs();

        // Install SQL
        include(dirname(__FILE__).'/sql/install.php');

        Configuration::updateValue('IW_CANCELLED_STATES_ID', '["2"]');
        Configuration::updateValue('IW_ALLOWED_CURRENCIES', '["1, 2, 3"]');
        Configuration::updateValue('IW_LAYERED_FILTER_URL', '/pack-1,/promotions-promotions');

        return parent::install();
    }

    public function checkHooks()
    {
        return $this->registerHook('actionCartSummary')
            && $this->registerHook('header')
            && $this->registerHook('actionOrderStatusUpdate')
            && $this->registerHook('updateCarrier')
            && $this->registerHook('actionObjectCountryDeleteAfter')
            && $this->registerHook('displayAdminCustomers')
            && $this->registerHook('actionObjectCountryDeleteAfter')
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('actionAdminControllerSetMedia')
            && $this->registerHook('actionAdminOrderPostProcess')
            && $this->registerHook('actionObjectCustomerUpdateAfter')
            && $this->registerHook('actionCustomerAccountAdd')
            && $this->registerHook('displayOrderConfirmation')
            && $this->registerHook('updateCustomerInfo')
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('paymentControl')
            && $this->registerHook('displayCustomerInfoDetail')
            && $this->registerHook('adminOrderCustomer');
    }

    public function uninstall()
    {
        if ((bool)EverTools::isAllowedIp() === true) {
            // Uninstall SQL
            include(dirname(__FILE__).'/sql/uninstall.php');
            $this->uninstallModuleTabs();
            return parent::uninstall();
        } else {
            $this->_errors[] = Tools::displayError('You must be a developper to uninstall this module).');
            return false;
        }
    }

    protected function checkTables()
    {
        include(dirname(__FILE__).'/sql/install.php');
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->checkHooks();
        $this->checkTables();
        if ((bool)Tools::isSubmit('dropPhpLogFiles') === true) {
            EverLog::dropGlobalLogs();
        }
        if ((bool)Tools::isSubmit('dropObsoletesLogFiles') === true) {
            EverLog::dropObsoleteLogs();
        }
        if ((bool)Tools::isSubmit('submitEverConfiguration') === true) {
            $this->postValidation();

            if (!count($this->postErrors)) {
                $this->postProcess();
            }
        }
        if (count($this->postErrors)) {
            foreach ($this->postErrors as $error) {
                $this->html .= $this->displayError($error);
            }
        }
        $this->context->smarty->assign(array(
            'everpstools_dir' => $this->_path,
            'ever_crons' => $this->getEverCronLinks(),
        ));
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/header.tpl');
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        $this->html .= $this->renderForm();
        $this->html .= $this->context->smarty->fetch($this->local_path.'views/templates/admin/footer.tpl');

        return $this->html;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitEverConfiguration';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $orderStates = OrderState::getOrderStates((int)$this->context->language->id);
        $form_fields = array();
        $form_fields[] = array(
            'form' => array(
                'legend' => [
                    'title' => $this->l('Developper Settings'),
                    'icon' => 'icon-smile',
                ],
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Logs expiration date (per monthes)'),
                        'desc' => $this->l('Please add logs expiration date per month. Leaving empty will set 3'),
                        'hint' => $this->l('Must be an integer'),
                        'name' => 'IW_LOGS_EXPIRATION',
                        'col' => 5,
                    ),
                    array(
                        'type' => 'textarea',
                        'label' => $this->l('Allowed IP addresses for developpers'),
                        'desc' => $this->l('Please add developper IP addresses separated by ","'),
                        'hint' => $this->l('Leave empty for no use'),
                        'name' => 'IW_ALLOWED_IP',
                        'col' => 5,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable debug mode on developpers IP addresses ?'),
                        'desc' => $this->l('Will enable debug mode but only for developpers IP addresses'),
                        'hint' => $this->l('Set No to disable developper debug mode'),
                        'name' => 'IW_DEVELOPPER_DEBUG',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            ]
                        ],
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Log PHP errors on developpers debug mode ?'),
                        'desc' => $this->l('Will log PHP errors on file if developper mode is enabled and IP is allowed'),
                        'hint' => $this->l('Set No to disable global PHP errors log file'),
                        'name' => 'IW_ERROR_LOG',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Yes')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('No')
                            ]
                        ],
                    ),
                ),
                'buttons' => array(
                    'dropPhpLogFiles' => array(
                        'name' => 'dropPhpLogFiles',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon' => 'process-icon-delete',
                        'title' => $this->l('Drop php global logs')
                    ),
                    'dropObsoletesLogFiles' => array(
                        'name' => 'dropObsoletesLogFiles',
                        'type' => 'submit',
                        'class' => 'btn btn-default pull-right',
                        'icon' => 'process-icon-delete',
                        'title' => $this->l('Drop obsoletes logs')
                    ),
                ),
                'submit' => [
                    'name' => 'submit',
                    'title' => $this->l('Save'),
                ],
            ),
        );
        $form_fields[] = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General Settings'),
                    'icon' => 'icon-smile',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Cancelled order states'),
                        'hint' => $this->l('Will be used for deleting entries on mixte table'),
                        'desc' => $this->l('Specify the cancelled order states'),
                        'name' => 'IW_CANCELLED_STATES_ID[]',
                        'class' => 'chosen',
                        'multiple' => true,
                        'required' => true,
                        'options' => array(
                            'query' => $orderStates,
                            'id' => 'id_order_state',
                            'name' => 'name'
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Developper email'),
                        'hint' => $this->l('Will send email logs to this email'),
                        'desc' => $this->l('Enter a valid email'),
                        'name' => 'IW_DEVELOPPER_MAIL',
                    ),
                ),
                'submit' => array(
                    'name' => 'submit',
                    'title' => $this->l('Save'),
                ),
            ),
        );
        return $form_fields;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $formValues = [];
        $formValues[] = [
            'IW_LOGS_EXPIRATION' => Configuration::get('IW_LOGS_EXPIRATION'),
            'IW_ALLOWED_IP' => Configuration::get('IW_ALLOWED_IP'),
            'IW_DEVELOPPER_DEBUG' => Configuration::get('IW_DEVELOPPER_DEBUG'),
            'IW_ERROR_LOG' => Configuration::get('IW_ERROR_LOG'),
            'IW_CANCELLED_STATES_ID[]' => Tools::getValue(
                'IW_CANCELLED_STATES_ID',
                json_decode(
                    Configuration::get(
                        'IW_CANCELLED_STATES_ID'
                    )
                )
            ),
        ];
        $values = call_user_func_array('array_merge', $formValues);
        return $values;
    }

    protected function postValidation()
    {
        if (!Tools::getValue('IW_CANCELLED_STATES_ID')
            || !Validate::isArrayWithIds(Tools::getValue('IW_CANCELLED_STATES_ID'))
        ) {
            $this->postErrors[] = $this->l('Error : [Cancelled order states] is not valid');
        }
    }

    /**
     * Save form data.
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitEverConfiguration')) {
            $form_values = $this->getConfigFormValues();
            foreach (array_keys($form_values) as $key) {
                if ($key == 'IW_CANCELLED_STATES_ID[]') {
                    Configuration::updateValue(
                        'IW_CANCELLED_STATES_ID',
                        json_encode(Tools::getValue('IW_CANCELLED_STATES_ID')),
                        true
                    );
                } else {
                    Configuration::updateValue($key, Tools::getValue($key));
                }
            }
        }

        $this->html .= $this->displayConfirmation($this->l('Setting updated'));
    }

    public function hookDisplayBackOfficeHeader()
    {
        EverTools::debugMode();
    }

    public function hookHeader()
    {
        EverTools::debugMode();
        $this->context->smarty->assign(array(
            'current_ip_address' => EverTools::getUserIpAddress(),
            'idw_img_dir' => _PS_IMG_DIR_,
            'idw_theme_img' => _PS_THEME_DIR_.'assets/img/',
        ));
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $order = $params['order'];
        $customer = new Customer(
            (int)$order->id_customer
        );
        $informations = [];
        $iwCustomer = EverCustomer::getObjByIdCustomer(
            (int)$params['id_customer']
        );
        if (!Validate::isLoadedObject($iwCustomer)) {
            $iwCustomer = new EverCustomer();
        } else {
            if (!empty($iwCustomer->informations)) {
                $informations[] = json_decode(
                    $iwCustomer->informations,
                    true
                );
            } else {
                $informations = [];
            }
        }
        $iwCustomer->id_customer = (int)$customer->id;
        $informations[] = [
            'newsletter' => (bool)$customer->newsletter,
            'optin' => (bool)$customer->optin,
            'user_ip' => Tools::getRemoteAddr()
        ];
        $informations = call_user_func_array('array_merge', $informations);
        $iwCustomer->informations = json_encode($informations);
        $iwCustomer->save();
        // Check & reset old currency if needed
        EverOrder::setOldCurrency();
    }

    /**
     * Successful create account
     *
     * @param array $params
     * @return boolean
     */
    public function hookActionCustomerAccountAdd($params)
    {
        $newCustomer = $params['newCustomer'];
        $params['object'] = $newCustomer;
        return $this->hookActionObjectCustomerUpdateAfter($params);
    }

    public function hookActionObjectCustomerUpdateAfter($params)
    {
        $customer = $params['object'];
        $informations = [];
        $iwCustomer = EverCustomer::getObjByIdCustomer(
            (int)$params['id_customer']
        );
        if (!Validate::isLoadedObject($iwCustomer)) {
            $iwCustomer = new EverCustomer();
        } else {
            if (!empty($iwCustomer->informations)) {
                $informations[] = json_decode(
                    $iwCustomer->informations,
                    true
                );
            }
        }
        $iwCustomer->id_customer = (int)$customer->id;
        $informations[] = [
            'newsletter' => (bool)$customer->newsletter,
            'optin' => (bool)$customer->optin,
            'user_ip' => Tools::getRemoteAddr()
        ];
        $informations = call_user_func_array('array_merge', $informations);
        $iwCustomer->informations = json_encode($informations);
        $iwCustomer->save();
    }

    // CARRIER HOOKS //
    public function hookUpdateCarrier($params)
    {
        $carrier = $params['carrier'];
        $id_carrier_old = (int)$params['id_carrier'];
        $id_carrier_new = (int)$carrier->id;
        // Save carrier informations on EverCarrier object
        $informations = [];
        $iwCarrier = EverCarrier::getObjByIdCarrier(
            (int)$params['id_carrier']
        );
        if (!Validate::isLoadedObject($iwCarrier)) {
            $iwCarrier = new EverCarrier();
        } else {
            if (!empty($iwCarrier->informations)) {
                $informations[] = json_decode(
                    $iwCarrier->informations,
                    true
                );
            }
        }
        // Set new carrier ID
        $iwCarrier->id_carrier = (int)$carrier->id;
        $informations['id_reference'] = (int)$carrier->id_reference;
        $informations = call_user_func_array('array_merge', $informations);
        $iwCarrier->informations = json_encode($informations);
        $iwCarrier->save();
    }

    public function hookActionObjectCarrierDeleteAfter($params)
    {
        $iwCarrier = EverCarrier::getObjByIdCarrier(
            (int)$params['id_carrier']
        );
        if (Validate::isLoadedObject($iwCarrier)) {
            $iwCarrier->delete();
        }
    }

    // COUNTRY HOOKS //
    public function hookActionObjectCountryDeleteAfter($params)
    {
        $iwCountry = EverCountry::getObjByIdCountry(
            (int)$params['id_country']
        );
        if (Validate::isLoadedObject($iwCountry)) {
            $iwCountry->delete();
        }
    }

    public function hookAdminOrderCustomer($params)
    {
        if (!Tools::getValue('id_order')
            || (int)Tools::getValue('id_order') <= 0
        ) {
            return;
        }
        $order = new Order(
            (int)Tools::getValue('id_order')
        );
        $iwOrder = EverOrder::getObjByIdOrder(
            (int)$order->id
        );
        if (!Validate::isLoadedObject($iwOrder)) {
            return;
        }
        $informations = $iwOrder->parseObjInformations();
        $this->context->smarty->assign(array(
            'ever_informations' => $informations
        ));
        return $this->display(__FILE__, 'views/templates/admin/customer/displayCustomerInfoDetail.tpl');
    }

    // IW MODULE METHODS //
    public function getEverConfigurationObjects()
    {
        foreach ($this->objectsList as $obj) {
            require_once $obj;
        }
    }

    protected function getEverCronLinks()
    {
        $cronList = [];
        $token = Tools::substr(Tools::encrypt($this->name.'/cron'), 0, 10);
        // Drop logs
        $dropLogsLink = $this->context->link->getModuleLink(
            $this->name,
            'cronlogs',
            array(
                'token' => $token,
                'id_shop' => (int)$this->context->shop->id,
                'droplogs' => 1
            ),
            true
        );
        $cronList[] = [
            'link' => $dropLogsLink,
            'description' => $this->l('Drop logs depending on module configuration')
        ];
        return $cronList;
    }
}
