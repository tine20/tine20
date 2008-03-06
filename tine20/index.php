<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

$time_start = microtime(true);

// check php environment
$requiredIniSettings = array(
    'magic_quotes_sybase'  => 0,
    'magic_quotes_gpc'     => 0,
    'magic_quotes_runtime' => 0,
);

foreach ($requiredIniSettings as $variable => $newValue) {
    $oldValue = ini_get($variable);
    if ($oldValue != $newValue) {
        if (ini_set($variable, $newValue) === false) {
            die("Sorry, your environment is not supported. You need to set $variable from $oldValue to $newValue.");
        }
    }
}

if(!extension_loaded('pdo_mysql')) {
    die("Sorry, your environment is not supported. You need to enable the module pdo_mysql.");
}

set_include_path(dirname(__FILE__) .'/Zend' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$tineBase = Tinebase_Controller::getInstance();

$tineBase->handle();

// log profiling information
$time_end = microtime(true);
$time = $time_end - $time_start;

if(function_exists('memory_get_peak_usage')) {
    $memory = memory_get_peak_usage(true);
} else {
    $memory = memory_get_usage(true);
}

Zend_Registry::get('logger')->debug('index.php ('. __LINE__ . ') TIME: ' . $time . ' seconds ' . $memory/1024/1024 . ' MBytes');
