<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

$time_start = microtime(true);

set_include_path(dirname(__FILE__) .'/Zend' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

// @todo remove that as well? -> is checked in setup.php
/*
if(Zend_Version::compareVersion('1.5.000') === 1) {
    die('Sorry, your version of the Zend Framework is to old. You have version ' . Zend_Version::VERSION . ' and require at least version 1.5.0.');
}
*/

$tineBase = Tinebase_Controller::getInstance();

// select requested api
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && !empty($_REQUEST['method'])) {
    // JSON api
    $tineBase->handleJson();        
} elseif(preg_match('/^Mozilla\/4\.0 \(compatible; (snom...)\-SIP (\d+\.\d+\.\d+)/i', $_SERVER['HTTP_USER_AGENT'])) {
    // SNOM api
    $tineBase->handleSnom();
} else {
    // HTTP api
    $tineBase->handleHttp();   
}

// log profiling information
$time_end = microtime(true);
$time = $time_end - $time_start;

if(function_exists('memory_get_peak_usage')) {
    $memory = memory_get_peak_usage(true);
} else {
    $memory = memory_get_usage(true);
}

Zend_Registry::get('logger')->debug('index.php ('. __LINE__ . ') TIME: ' . $time . ' seconds ' . $memory/1024/1024 . ' MBytes');
