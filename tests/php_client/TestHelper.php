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

$url      = 'http://demo.tine20.org';
$username = 'utester';
$password = 'utester1234';

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

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

date_default_timezone_set('UTC');

// copy connection data to global scope
$GLOBALS['TestHelper']['url'] = $url;
$GLOBALS['TestHelper']['username'] = $username;
$GLOBALS['TestHelper']['password'] = $password;

$connection = new Tinebase_Connection($url, $username, $password);
Tinebase_Connection::setDefaultConnection($connection);

unset($url, $username, $password, $testRoot, $tineRoot, $phpClientPath, $connection);
