<?php
/**
 * Socket Adapter client test script
 * - see \Zend_Http_Client_Adapter_Socket::connect
 *
 * @package     HelperScripts
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/*********** config ***********/

$config = array(
    'host'      => 'host.to.test.net',
    'port'      => 993,
);


$contextOptions = array('ssl' => array(
    'verify_peer' => false,
    'verify_peer_name' => false
));
//$context = stream_context_create($contextOptions);
$context = stream_context_create();

$flags = STREAM_CLIENT_CONNECT;
$socket = stream_socket_client($config['host'] . ':' . $config['port'],
    $errno,
    $errstr,
    30,
    $flags,
    $context);

if (! $socket) {
    echo 'Unable to Connect to ' . $config['host'] . ':' . $config['port'] . '. Error #' . $errno . ': ' . $errstr . "\n";
} else {
    echo 'Connection successful!' . "\n";
}

if (is_resource($socket))
{
    fclose($socket);
}
