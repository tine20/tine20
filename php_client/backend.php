<?php
/**
 * this is just an usage example for the Tine 2.0 PHP Client
 *
 * @package     Remote API Example
 * @license     New BSD License
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

var_dump($_POST);

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

echo "Try to login...<br>";
// feteching the connection
$connection = Tinebase_Connection::getInstance($_POST['url'], $_POST['username'], $_POST['password']);
// setting default connection
Tinebase_Service_Abstract::setDefaultConnection($connection);

// getting the Tinebase service
$TinbaseService = new Tinebase_Service($connection);
if($_POST['debug'] == 'yes') {
    $connection->setDebugEnabled(true);
    $TinbaseService->debugEnabled = true;
}
// login to remote tine installation
$TinbaseService->login();

echo "<hr>Try to add contact...<br>";

// NOTE: all data are expected to be UTF-8 encoded!
$contactData = $_POST['contact'];

// getting Addressbook service
$addressbook = new Addressbook_Service();

// creating a _local_ model of the contact data
// NOTE: with this we do client side data validation
$contact = new Addressbook_Model_Contact($contactData);

// call remote addContact
$updatedContact = $addressbook->addContact($contact);

var_dump($updatedContact->toArray());

echo "<hr>Try to logout...<br>";
$TinbaseService->logout();

echo '<a href="index.html">Back to contact form</a>';