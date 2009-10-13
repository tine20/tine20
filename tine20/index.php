<?php
/**
 * this is the general file any request should be routed trough
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: index.php 5122 2008-10-27 11:53:24Z p.schuele@metaways.de 
 * 
 */


/**
 * magic_quotes_gpc Hack!!!
 * @author Florian Blasel
 * 
 * If you are on a shared host you may not able to change the php setting for magic_quotes_gpc
 * this hack will solve this BUT this takes performance (speed)!
 */
/*
if (ini_get('magic_quotes_gpc')) {
    function __magic_quotes_gpc($requests) {
        foreach($requests AS $k=>&$v) {
            if (is_array($v)) {
                $requests[stripslashes($k)] = __magic_quotes_gpc($v);
            } else {
                $requests[stripslashes($k)] = stripslashes($v);
            }
        }
        return $requests;
    } 
    
    // Change the incomming data if needed
    $_GET = __magic_quotes_gpc( $_GET );
    $_POST = __magic_quotes_gpc( $_POST );
    $_COOKIE = __magic_quotes_gpc( $_COOKIE );
    $_ENV = __magic_quotes_gpc( $_ENV );
    $_REQUEST = __magic_quotes_gpc( $_REQUEST );
} // end magic_quotes_gpc Hack
*/

$time_start = microtime(true);

set_include_path('.' . PATH_SEPARATOR . dirname(__FILE__) . '/library' . PATH_SEPARATOR . get_include_path());

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

Tinebase_Core::dispatchRequest();

// log profiling information
$time_end = microtime(true);
$time = $time_end - $time_start;

if(function_exists('memory_get_peak_usage')) {
    $memory = memory_get_peak_usage(true);
} else {
    $memory = memory_get_usage(true);
}

Tinebase_Core::getLogger()->info('index.php ('. __LINE__ . ') TIME: ' . $time . ' seconds ' . $memory/1024/1024 . ' MBytes');
