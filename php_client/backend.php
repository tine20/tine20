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

$client = new Zend_Http_Client(
    $_POST['hostname'],
    array('keepalive' => true)
);

$client->setCookieJar();
$client->setHeaders('X-Requested-With',	'XMLHttpRequest');

// login
if($_POST['debug'] == 'yes') {
    echo "Try to login...</br>";
}
$client->setParameterPost(array(
    'username'  => $_POST['username'],
    'password'  => $_POST['password'],
    'method'    => 'Tinebase.login'
));

$response = $client->request('POST');

if(!$response->isSuccessful()) {
    die('alles schlecht');
}

$responseData = Zend_Json::decode($response->getBody());
if($_POST['debug'] == 'yes') {
    var_dump($responseData);
}

// add contact
if($_POST['debug'] == 'yes') {
    echo "Try to add contact...</br>";
}
$contactData = $_POST;
$contactData['owner'] = 5;

$client->setParameterPost(array(
    'method'   => 'Addressbook.saveContact',
    'contactData'  => Zend_Json::encode($contactData)
));

$response = $client->request('POST');

//var_dump( $client->getLastRequest());
//var_dump( $response );

if(!$response->isSuccessful()) {
    die('alles schlecht');
}

$responseData = Zend_Json::decode($response->getBody());
if($_POST['debug'] == 'yes') {
    var_dump($responseData);
}

// logout
if($_POST['debug'] == 'yes') {
    echo "Try to logout...</br>";
}
$client->setParameterPost(array(
    'method'   => 'Tinebase.logout'
));

$response = $client->request('POST');

//var_dump( $client->getLastRequest());
//var_dump( $response );

if(!$response->isSuccessful()) {
    die('alles schlecht');
}

$responseData = Zend_Json::decode($response->getBody());
if($_POST['debug'] == 'yes') {
    var_dump($responseData);
}

echo '<a href="index.html">Back to contact form</a>';