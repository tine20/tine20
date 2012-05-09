<?php
/**
 * Syncroton
 *
 * Example server file
 *
 * @package     doc
 * @license     http://www.tine20.org/licenses/lgpl.html LGPL Version 3
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @todo        still untested
 */

if(empty($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Syncroton"');
    header('HTTP/1.1 401 Unauthorized');
    return;
}

$paths = array(
    realpath(dirname(__FILE__)),
    realpath(dirname(__FILE__) . '/lib'),
    get_include_path()
);
set_include_path(implode(PATH_SEPARATOR, $paths));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

// authenticate user here if needed
// authentication is not part of Syncroton

// set database backend
$params = array (
    'dbname' => '/tmp/syncroton.sq3',
);
Syncroton_Registry::setDatabase(Zend_Db::factory('PDO_SQLITE', $params));

// setup logger
$writer = new Zend_Log_Writer_Stream('/tmp/syncroton.log');
$writer->addFilter(new Zend_Log_Filter_Priority(Zend_Log::DEBUG));

$logger = new Zend_Log($writer);

Syncroton_Registry::set('loggerBackend', $logger);

// set the classes to handle contacts, events, email and tasks 
Syncroton_Registry::setContactsDataClass('Syncroton_Data_Contacts');
Syncroton_Registry::setCalendarDataClass('Syncroton_Data_Calendar');
Syncroton_Registry::setEmailDataClass('Syncroton_Data_Email');
Syncroton_Registry::setTasksDataClass('Syncroton_Data_Tasks');

$server = new Syncroton_Server($_SERVER['PHP_AUTH_USER']);

$server->handle();