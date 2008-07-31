<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

$path = '../tine20';
set_include_path(get_include_path() . PATH_SEPARATOR . $path);

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$config = new Zend_Config(require dirname(__FILE__) . '/config.inc.php');

Zend_Registry::set('config', $config);
define(SQL_TABLE_PREFIX, (isset($config->database->tableprefix) ? $config->database->tableprefix : ''));

$db = Zend_Db::factory($config->database->adapter, $config->database->toArray());
Zend_Db_Table_Abstract::setDefaultAdapter($db);

$phoneBackend = new Voipmanager_Backend_Snom_Phone();

$phone = $phoneBackend->getByMacAddress($_GET['mac']);

// legacy code, generate http_client username and password on demand
if(empty($phone->http_client_user) || empty($phone->http_client_pass)) {
    $phone->http_client_user = Tinebase_Record_Abstract::generateUID();
    $phone->http_client_pass = Tinebase_Record_Abstract::generateUID();
    $phone->http_client_info_sent = false;
    
    $phoneBackend->update($phone);
}

$phone = updateStatus($phone, $_SERVER['HTTP_USER_AGENT']);
#$phone = updateStatus($phone, 'Mozilla/4.0 (compatible; snom320-SIP 7.1.30');
$phoneBackend->updateStatus($phone);

header('Content-Type: text/xml');

$xmlBackend = new Voipmanager_Backend_Snom_Xml();        
echo $xmlBackend->getConfig($phone);

$phone->http_client_info_sent = true;
$phoneBackend->update($phone);


function updateStatus($_phone, $_userAgent)
{
    if(preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $_userAgent, $matches)) {
        $_phone->current_model = $matches[1];
        $_phone->current_software = $matches[2];
    } else {
        throw new Exception('unparseable useragent string');
    }
    
    $_phone->ipaddress = $_SERVER["REMOTE_ADDR"];
    $_phone->settings_loaded_at = Zend_Date::now();
    
    return $_phone;
}