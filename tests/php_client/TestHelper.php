<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     tests
 * @subpackage  php_client
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/*
 * Set error reporting 
 */
error_reporting( E_ALL | E_STRICT );


/*
 * Set white / black lists
 */
PHPUnit_Util_Filter::addDirectoryToFilter(dirname(__FILE__));

/*
 * Set include path
 */
$testRoot = dirname(__File__);
$tineRoot = dirname(dirname($testRoot));
$phpClientPath = $tineRoot . DIRECTORY_SEPARATOR . 'php_client';

$path = array(
    $testRoot,
    $tineRoot,
    $phpClientPath,
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

date_default_timezone_set('UTC');

// get config
if(file_exists(dirname(__FILE__) . '/config.inc.php')) {
    $config = new Zend_Config(require dirname(__FILE__) . '/config.inc.php');
} else {
    throw new Exception("Couldn't find config.inc.php! \n");
}

// copy connection data to global scope
$GLOBALS['TestHelper']['url'] = $config->url;
$GLOBALS['TestHelper']['username'] = $config->username;
$GLOBALS['TestHelper']['password'] = $config->password;

$connection = new Tinebase_Connection($config->url, $config->username, $config->password);
Tinebase_Connection::setDefaultConnection($connection);

unset($testRoot, $tineRoot, $phpClientPath, $connection);
