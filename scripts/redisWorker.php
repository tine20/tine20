#!/usr/bin/env php
<?php
/**
 * redis worker run script
 *
 * @package     Cli
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * you need to adjust some paths (tine + daemon)
 * 
 * you need an config.ini file that should look like this:

[redis]
host = localhost
port = 6379

 */

// TODO adjust paths
$tine20path = dirname(dirname(__FILE__)). '/tine20';
$workerPath = dirname(dirname(__FILE__)) . '/tests/tine20/Tinebase/Redis/RedisWorker.php';

if (php_sapi_name() != 'cli') {
    die('Not allowed: wrong sapi name!');
}

set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);

$paths = array(
    $tine20path,
    $tine20path . '/library',
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);
Tinebase_Autoloader::initialize($autoloader);

// NOTE: you need to include your worker class here
require_once $workerPath;
$worker = new RedisWorker();

$worker->run();
