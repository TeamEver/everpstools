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

class EverTools extends ObjectModel
{
    /**
     * Get URL http code using curl
     * @param string url to check
     * @return int
    */
    public static function getUrlHttpCode($url)
    {
        if (!Validate::isUrl($url)) {
            return 0;
        }
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return (int)$httpCode;
    }

    /**
     * Get all wrong HTTP codes
     * @return array
    */
    public static function getWrongHttpCodes()
    {
        return [
            0, // default
            404, // Not found
            500, // Fatal error
            301 // Forbidden
        ];
    }

    /**
     * Enable debug mode only for allowed IP adresses
    */
    public static function debugMode()
    {
        if ((bool)Configuration::get('EVER_DEVELOPPER_DEBUG') === true
            && (bool)self::isAllowedIp() === true
        ) {
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
            if ((bool)Configuration::get('EVER_ERROR_LOG') === true) {
                ini_set('error_log', IWLog::GLOBAL_ERRORS_LOGS.'php-error.log');
            }
            // Assign to context
            Context::getContext()->smarty->assign(array(
                'current_ip_address' => self::getUserIpAddress(),
                'ever_mode_debug' => true
            ));
        } else {
            // Assign to context
            Context::getContext()->smarty->assign(array(
                'current_ip_address' => self::getUserIpAddress(),
                'ever_mode_debug' => false
            ));
        }
    }

    /**
     * Get current user IP address
     * @return string ip
    */
    public static function getUserIpAddress()
    {
        return Tools::getRemoteAddr();
        // if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        //     $ip = $_SERVER['HTTP_CLIENT_IP'];
        // } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        //     $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        // } else {
        //     $ip = $_SERVER['REMOTE_ADDR'];
        // }
        // return $ip;
    }

    /**
     * If is allowed / developper IP address
     * @return bool
    */
    public static function isAllowedIp()
    {
        $allowedIpConfiguration = Configuration::get('EVER_ALLOWED_IP');
        if (!$allowedIpConfiguration) {
            $allowedIpLists = [
                '176.156.0.96'
            ];
        } else {
            $allowedIpLists = explode(',', $allowedIpConfiguration);
        }
        $ip = self::getUserIpAddress();
        if (in_array($ip, $allowedIpLists)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if module is installed, enabled and can be loaded
     * @param string module name
     * @return bool
    */
    public static function isModuleAllowed($moduleName)
    {
        if (!Module::isInstalled($moduleName)) {
            return false;
        }
        if (!Module::isEnabled($moduleName)) {
            return false;
        }
        if (!Module::getInstanceByName($moduleName)) {
            return false;
        }
        return true;
    }

    /**
     * Check if table exists
     * @return bool
    */
    public static function checkDbForTableExists($table)
    {
        return Db::getInstance()->executeS("SHOW TABLES LIKE '". _DB_PREFIX_ . pSQL($table) ."'");
    }

    public static function executeCurl(
        $url,
        $curlDatas,
        $connectTimeout = 1200,
        $timeout = 1200,
        $maxRedir = 10,
        $post = 1,
        $verifyHost = 0,
        $verifyPeer = false,
        $verbose = 0
    ) {
        $httpCode = (int)self::getUrlHttpCode($url);
        if (in_array($httpCode, self::getWrongHttpCodes())) {
            return;
        }
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_ENCODING => "",
            CURLOPT_USERAGENT => "spider",
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => (int)$connectTimeout,
            CURLOPT_TIMEOUT => (int)$timeout,
            CURLOPT_MAXREDIRS => (int)$maxRedir,
            CURLOPT_POST => (int)$post,
            CURLOPT_POSTFIELDS => $curlDatas,
            CURLOPT_SSL_VERIFYHOST => (int)$verifyHost,
            CURLOPT_SSL_VERIFYPEER => (bool)$verifyPeer,
            CURLOPT_VERBOSE => (int)$verbose
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $time = microtime(true);
        $content = curl_exec($ch);
        $timer = microtime(true) - $time;
        curl_close($ch);
        return $content;
    }

    /**
     * Sent file to FTP/SFTP using PhpSecLib
     * @param string host
     * @param string password
     * @param string file on external server
     * @param string file on local server
     * @return bool
    */
    public static function sendFileToFtp($host, $user, $password, $sentFile, $localFile)
    {
        set_include_path(
            get_include_path() . PATH_SEPARATOR . _PS_MODULE_DIR_.'everpstools/models/tools/phpseclib'
        );
        require_once _PS_MODULE_DIR_.'everpstools/models/tools/phpseclib/Net/SFTP.php';
        $sftp = new Net_SFTP(
            $host
        );
        $sftp->login(
            $user,
            $password
        );
        $put = $sftp->put($sentFile, $localFile, NET_SFTP_LOCAL_FILE);
        // Let's log
        if ((bool)$put === false) {
            $logContent = '-----------------------------'.PHP_EOL;
            $logContent .= 'File not sent on date : '.date('Y-m-d H:i:s').PHP_EOL;
            $logContent .= 'Sent file '.$sentFile.PHP_EOL;
            $logContent .= 'Local file '.$localFile.PHP_EOL;
            $logContent .= '-----------------------------'.PHP_EOL;
            EverLog::addEverLogFile(
                EverLog::GLOBAL_ERRORS_LOGS,
                'connect2Ftp-'.date('Y-m-d').'.log',
                $logContent,
                true,
                true,
                true
            );
        }
        return $put;
    }
}