<?php
/**
 * Tine 2.0
 * 
 * @package     tests
 * @subpackage  php_client
 * @license     yet unknown
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

$url      = 'http://demo.tine20.org';
$username = 'tine20demo';
$password = 'demo';

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
$tineRoot = dirname(dirname(dirname(__FILE__)));
$phpClientPath = $tineRoot . DIRECTORY_SEPARATOR . 'php_client';

$path = array(
    $tineRoot,
    $phpClientPath,
    get_include_path(),
);
set_include_path(implode(PATH_SEPARATOR, $path));

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

/*
 * login to remote install
 */
$client = Tinebase_Connection::getInstance($url, $username, $password);
//$client->setDebugEnabled(true);
$client->login();

unset($url, $username, $password, $tineRoot, $phpClientPath, $client);