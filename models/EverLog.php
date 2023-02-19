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

class EverLog extends ObjectModel
{
    // Log global errors
    const GLOBAL_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/global/';
    const GLOBAL_ERRORS_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/global/errors/';
    // Log orders
    const ORDER_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/orders/';
    const ORDER_ERRORS_LOGS  = _PS_MODULE_DIR_.'everpstools/output/logs/orders/errors/';
    // Log carts
    const CART_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/carts/';
    const CART_ERRORS_LOGS  = _PS_MODULE_DIR_.'everpstools/output/logs/carts/errors/';
    // Log customers
    const CUSTOMER_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/customers/';
    const CUSTOMER_ERRORS_LOGS  = _PS_MODULE_DIR_.'everpstools/output/logs/customers/errors/';
    // Log products
    const PRODUCT_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/products/';
    const PRODUCT_ERRORS_LOGS  = _PS_MODULE_DIR_.'everpstools/output/logs/products/errors/';
    // Log modules
    const MODULE_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/modules/';
    const MODULE_ERRORS_LOGS = _PS_MODULE_DIR_.'everpstools/output/logs/modules/errors/';

    public $id_ever_log;
    public $informations;
    public $date_add;
    public $date_upd;

    public static $definition = array(
        'table' => 'ever_log',
        'primary' => 'id_ever_log',
        'multilang' => false,
        'fields' => array(
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
     * Write IW log and chmod 0444 after writing on it
     * @param file path (see const, must have trailing slash)
     * @param filename
     * @param file content
     * @param bool append content on existing file or not
     * @param bool send email to configured developper
     * @param bool save log on database
     * @return bool
    */
    public static function addEverLogFile($path, $filename, $content, $append = false, $sendMail = false, $dbInsert = false)
    {
        // Write and chmod file
        if (file_exists($path.$filename)) {
            chmod($path.$filename, 0755);
        }
        if ((bool)$append === true) {
            $write = file_put_contents($path.$filename, $content, FILE_APPEND);
        } else {
            $write = file_put_contents($path.$filename, $content);
        }
        $chmod = chmod($path.$filename, 0444);
        if ((bool)$dbInsert === true) {
            $log = new self();
            $informations = [
                'path' => $path,
                'filename' => $filename,
                'content' => $content
            ];
            $log->informations = json_encode($informations);
            $log->save();
        }
        if ((bool)$sendMail === true) {
            $params = [
                '{content}' => $content
            ];
            $developperMail = Configuration::get('EVER_DEVELOPPER_MAIL');
            if ($developperMail
                && Validate::isEmail($developperMail)
            ) {
                $sent = Mail::Send(
                    Configuration::get('PS_LANG_DEFAULT'),
                    'log_mail',
                    'Email log',
                    $params,
                    $developperMail,
                    'Ever Log',
                    null,
                    null,
                    null,
                    null,
                    dirname(__FILE__).'/../mails/'
                );
                return $sent;
            }
        }
        return $write;
    }

    public static function getAllLogs()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('ever_log');
        return Db::getInstance()->executeS($sql);
    }

    public static function getLogByDate($dateAdd)
    {
        $dateAdd = date('Y-m-d H:i:s', $dateAdd);
        $return = [];
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('ever_log');
        $sql->where('date_add = '.pSQL($dateAdd));
        $arrLogs = Db::getInstance()->executeS($sql);
        foreach ($arrLogs as $arrLog) {
            $log = new self(
                (int)$arrLog['id_ever_log']
            );
            $return[] = $log;
        }
        return $return;
    }

    /**
     * Get specific information from log
     * @param int id log
     * @param string information asked
     * @return string
    */
    public static function getLogInfo($idLog, $information)
    {
        $obj = self::getObjByIdCountry(
            (int)$idLog
        );
        if (!Validate::isLoadedObject($obj)) {
            return false;
        }
        if (!$obj->informations || empty($obj->informations)) {
            return false;
        }
        $logInfos = json_decode($obj->informations, true);
        foreach ($logInfos as $k => $info) {
            if ($k == $information) {
                return $info;
            }
        }
        return false;
    }

    /**
     * @param $idLog
     * @return object | false
     */
    public static function getObjByIdLog($idLog)
    {
        $sql = new DbQuery;
        $sql->select('id_ever_log');
        $sql->from('ever_log');
        $sql->where('id_ever_log = '.(int)$idLog);
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

    public static function dropObsoleteLogs()
    {
        $expirationDelay = (int)Configuration::get('EVER_LOGS_EXPIRATION');
        if ((int)$expirationDelay <= 0) {
            $expirationDelay = 3;
        }
        $expirationDelayDate = date('Y-m-d H:i:s', strtotime('-'.(int)$expirationDelay.' months'));
        // First, drop datas where no id customer is stored
        $dropObsoleteLogs = 'DELETE FROM `'._DB_PREFIX_.'ever_log`
        WHERE date_add <= "' . $expirationDelayDate . '"';
        Db::getInstance()->execute($dropObsoleteLogs);
        return self::dropObsoleteLogFiles();
    }

    public static function dropObsoleteLogFiles()
    {
        $expirationDelay = (int)Configuration::get('EVER_LOGS_EXPIRATION');
        if ((int)$expirationDelay <= 0) {
            $expirationDelay = 3;
        }
        $expirationDelayDate = date('Y-m-d H:i:s', strtotime('-'.(int)$expirationDelay.' months'));
        $logFolders = self::getLogFolders();
        foreach ($logFolders as $logFolder) {
            $logFiles = glob($logFolder.'*');
            foreach ($logFiles as $logFile) {
                $info = new SplFileInfo(basename($logFile));
                if (is_file($logFile) && $info->getExtension() == 'log') {
                    $fileCreationDate = filectime($logFile);
                    $fileCreationDate = date('Y-m-d H:i:s', $fileCreationDate);
                    if ($fileCreationDate <= $expirationDelayDate) {
                        unlink(
                            $logFile
                        );
                    }
                }
            }
        }
    }

    public static function dropGlobalLogs()
    {
        $logFiles = glob(self::GLOBAL_ERRORS_LOGS.'*');
        foreach ($logFiles as $logFile) {
            $info = new SplFileInfo(basename($logFile));
            if (is_file($logFile) && $info->getExtension() == 'log') {
                unlink(
                    $logFile
                );
            }
        }
        $logFiles = glob(self::GLOBAL_LOGS.'*');
        foreach ($logFiles as $logFile) {
            $info = new SplFileInfo(basename($logFile));
            if (is_file($logFile) && $info->getExtension() == 'log') {
                unlink(
                    $logFile
                );
            }
        }
    }

    public static function dropLogsWithoutFiles()
    {
        $arrLogs = self::getAllLogs();
        foreach ($arrLogs as $arrLog) {
            $log = new self(
                (int)$arrLog['id_ever_log']
            );
            if (empty($log->informations)) {
                continue;
            }
            $infos = json_decode($log->informations, true);
            if (!isset($info['path'])
                || !isset($info['filename'])
                || empty($info['path'])
                || empty($info['filename'])
            ) {
                continue;
            }
            $filename = $info['path'].$info['filename'];
            if (!file_exists($filename)) {
                $log->delete();
            }
        }
    }

    /**
     * Get all logs folders
     * @return array
    */
    public static function getLogFolders()
    {
        return [
            self::GLOBAL_ERRORS_LOGS,
            self::GLOBAL_LOGS,
            self::ORDER_LOGS,
            self::ORDER_ERRORS_LOGS,
            self::CART_LOGS,
            self::CART_ERRORS_LOGS,
            self::CUSTOMER_LOGS,
            self::CUSTOMER_ERRORS_LOGS,
            self::PRODUCT_LOGS,
            self::PRODUCT_ERRORS_LOGS,
            self::MODULE_LOGS,
            self::MODULE_ERRORS_LOGS,
        ];
    }
}