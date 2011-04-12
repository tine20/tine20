#!/usr/bin/env php
<?php
/**
 * Fritzbox Monitor Deamon 
 *
 * @package     Voipmanager
 * @subpackage  Fritz
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo query db for params: host / password
 * @todo enable monitor on the fly
 * @todo connect to tine instalation for reporting ;-)
 */

if (php_sapi_name() != 'cli') {
    die('not allowed!');
}

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

/**
 * path to tine 2.0 checkout
 */
$tine20path = dirname(dirname(dirname(__FILE__)));

/**
 * options
 */
try {
    $opts = new Zend_Console_Getopt(
    array(
        'help|h'                => 'Display this help Message',
        'verbose|v'             => 'Output messages',
        'host=s'                => 'hostname/ip of Fritzbox (defaults to fritz.box)',              
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
   fwrite(STDOUT, $e->getUsageMessage());
   exit(1);
}

if ($opts->h) {
    fwrite(STDOUT, $opts->getUsageMessage());
    exit;
}

$fritzbox = $opts->host ? $opts->host : 'fritz.box';

if ($opts->v) fwrite(STDOUT, "Trying to connect to Fritzbox: '$fritzbox'\n");
$fp = @fsockopen("tcp://$fritzbox", 1012, $errno, $errstr, 5);

if (!$fp) {
    fwrite(STDERR, "Could not open connection to Fritzbox '$fritzbox': $errstr (error $errno)\n");
    exit(1);
} 

if ($opts->v) fwrite(STDOUT, "Successfully connected to Fritzbox: '$fritzbox'\n");
stream_set_timeout($fp, 60);

/**
 * maintains a map callId => status
 */
$activeCallMap = array();

while (! feof($fp)) {
    $message = fgets($fp);
    
    /**
     * keep alive
     */
    if (! $message) {
        //if ($opts->v) fwrite(STDOUT, "KEEP ALIVE\n");
    
    /**
     * incoming call
     */
    } elseif (preg_match("/;RING/", $message)) {
        list($time, $action, $callId, $remote, $local, $line) = explode(';', $message);
        if ($opts->v) fwrite(STDOUT, "RING: time: '$time', callId: '$callId', local: '$local', remote: '$remote', line: '$line' \n");
        
        $activeCallMap[$callId] = array(
            'status' => 'RING',
            'time'   => $time,
        );
        
    /**
     * outgoing call
     */
    } elseif (preg_match("/;CALL/", $message)) {
        list($time, $action, $callId, $duration, $local, $remote, $line) = explode(';', $message);
        if ($opts->v) fwrite(STDOUT, "CALL: time: '$time', callId: '$callId', local: '$local', remote: '$remote', line: '$line' \n");
        
        $activeCallMap[$callId] = array(
            'status' => 'CALL',
            'time'   => $time,
        );
        
    /**
     * connect / successfull call
     */
    } elseif (preg_match("/;CONNECT/", $message)) {
        list($time, $action, $callId, $duration, $remote) = explode(';', $message);
        if ($opts->v) fwrite(STDOUT, "CONNECT: time: '$time', callId: '$callId', duration: '$duration', remote: '$remote' \n");
        
        // check if call is active
        if (isset($activeCallMap[$callId])) {
            $activeCallMap[$callId]['status'] = "CONNECT";
        } elseif ($opts->v) {
            fwrite(STDOUT, "SKIPPING CONNECT: no call with callId: '$callId' registered. \n");
        }
    
    /**
     * disconect / hangup
     */
    } elseif (preg_match("/;DISCONNECT/", $message)) {
        list($time, $action, $callId, $duration) = explode(';', $message);
        if ($opts->v) fwrite(STDOUT, "DISCONNECT: time: '$time', callId: '$callId', duration: '$duration' \n");
        
        // check if call is active 
        if (isset($activeCallMap[$callId])) {
            // do something ;-)
            unset($activeCallMap[$callId]);
        } elseif ($opts->v) {
            fwrite(STDOUT, "SKIPPING DISCONNECT: no call with callId: '$callId' registered. \n");
        }
        
    } else {
        if ($opts->v) fwrite(STDOUT, "UNKNOWN: '$message' \n");
    }
}

// as the demon normally gets killed, we have an exceptional state if we come here
$info = stream_get_meta_data($fp);
fclose($fp);
fwrite(STDERR, "Some error occurred\n");
fwrite(STDERR, print_r($info, true));
exit(1);
