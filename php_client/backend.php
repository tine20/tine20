<?php
/**
 * this is just an usage example for the Tine 2.0 PHP HTTP Client
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

echo "Fetching connection...<br>";
// feteching the connection
$connection = new Tinebase_Connection($_POST['url'], $_POST['username'], $_POST['password']);
// setting default connection
Tinebase_Connection::setDefaultConnection($connection);

// getting Addressbook service
$addressbook = new Addressbook_Service();

if($_POST['debug'] == 'yes') {
    $connection->debugEnabled;
    $addressbook->debugEnabled;
}

echo "Loggin in...<br>";
// login to remote tine installation
$connection->login();

echo "<hr>Adding contact...<br>";

// NOTE: all data are expected to be UTF-8 encoded!
$contactData = $_POST['contact'];

// creating a _local_ model of the contact data
// NOTE: with this we do client side data validation
$contact = new Addressbook_Model_Contact($contactData);

// call remote addContact
$updatedContact = $addressbook->addContact($contact);

var_dump($updatedContact->toArray());

echo "<hr>Logging out...<br>";
$connection->logout();

echo '<a href="index.html">Back to contact form</a>';