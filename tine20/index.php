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

$time_start = microtime(true);

require_once 'bootstrap.php';

Tinebase_Core::dispatchRequest();

// log profiling information
$time_end = microtime(true);
$time = $time_end - $time_start;

if(function_exists('memory_get_peak_usage')) {
    $memory = memory_get_peak_usage(true);
} else {
    $memory = memory_get_usage(true);
}

if(function_exists('realpath_cache_size')) {
    $realPathCacheSize = realpath_cache_size();
} else {
    $realPathCacheSize = 'unknown';
}

if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) {
    Tinebase_Core::getLogger()->info('index.php ('. __LINE__ . ') ' .
        'METHOD: ' . Tinebase_Core::get(Tinebase_Core::METHOD) . ' / TIME: ' . $time . ' seconds / MEMORY: ' .
        $memory/1024/1024 . ' MBytes / REALPATHCACHESIZE: ' . $realPathCacheSize);
}
