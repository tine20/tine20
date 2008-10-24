<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        call Tinebase_Core::dispatchRequest() instead of Tinebase_Controller->handleXYZ()
 */

$time_start = microtime(true);

set_include_path(dirname(__FILE__) .'/Zend' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$tineBase = Tinebase_Controller::getInstance();
if (( (isset($_SERVER['HTTP_X_TINE20_REQUEST_TYPE']) && $_SERVER['HTTP_X_TINE20_REQUEST_TYPE'] == 'JSON')  || 
      (isset($_POST['requestType']) && $_POST['requestType'] == 'JSON')
    ) && isset($_REQUEST['method'])) {
    $tineBase->handleJson();        
} elseif(preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $_SERVER['HTTP_USER_AGENT'])) {
    // SNOM api
    $tineBase->handleSnom();
} else {
    // HTTP api
    $tineBase->handleHttp();   
}

//Tinebase_Core::getInstance()->dispatchRequest();

// log profiling information
$time_end = microtime(true);
$time = $time_end - $time_start;

if(function_exists('memory_get_peak_usage')) {
    $memory = memory_get_peak_usage(true);
} else {
    $memory = memory_get_usage(true);
}

Zend_Registry::get('logger')->debug('index.php ('. __LINE__ . ') TIME: ' . $time . ' seconds ' . $memory/1024/1024 . ' MBytes');
