<?php
/**
 * this is just the first draft
 *
 * @package     Remote API
 * @license     yet unknown
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

require_once 'Zend/Loader.php';

Zend_Loader::registerAutoload();

$client = new TineClient_Connection(
    $_POST['url'],
    array('keepalive' => true)
);

if($_POST['debug'] == 'yes') {
    $client->setDebugEnabled(true);
}


echo "Try to login...<br>";

$client->login($_POST['username'], $_POST['password']);


echo "<hr>Try to add contact...<br>";

$contactData = $_POST;
$contactData['owner'] = 5;

$client->addContact($contactData);


echo "<hr>Try to logout...<br>";

$client->logout();

echo '<a href="index.html">Back to contact form</a>';