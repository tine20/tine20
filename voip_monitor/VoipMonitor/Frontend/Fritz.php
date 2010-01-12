<?php
/**
 * VoIP Monitor Deamon
 * 
 * @package     VoipMonitor
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * abstract class for VoipMonitor frontends
 * 
 * @package     VoipMonitor
 */
class VoipMonitor_Frontend_Fritz extends VoipMonitor_Frontend_Abstract
{
    protected $_frontendDefaultSocket = 'tcp://fritz.box:1012';
    
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/Frontend/VoipMonitor_Frontend_Abstract#handleEvents()
     */
    public function handleEvents()
    {
        $activeCallMap = array();
        
        while (!feof($this->_stream)) {
            $message = fgets($this->_stream);
            
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
    }
    
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/Frontend/VoipMonitor_Frontend_Abstract#login($_username, $_password)
     */
    public function login($_username, $_password)
    {
    }
}