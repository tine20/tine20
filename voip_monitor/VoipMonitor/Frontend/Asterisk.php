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
class VoipMonitor_Frontend_Asterisk extends VoipMonitor_Frontend_Abstract
{
    protected $_port = 5038;
  
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/Frontend/VoipMonitor_Frontend_Abstract#login($_username, $_password)
     */
    public function login($_username, $_password)
    {
        $command  = "Action: Login\r\n";
        $command .= "Username: $_username\r\n";
        $command .= "Secret: $_password\r\n";
        $command .= "Events: on\r\n\r\n";
        //echo $command;
        fwrite($this->_stream, $command);
    }
  
    /**
     * (non-PHPdoc)
     * @see VoipMonitor/Frontend/VoipMonitor_Frontend_Abstract#handleEvents()
     */
    public function handleEvents()
    {
        $event = array();
        
        while (!feof($this->_stream)) {
            $line = fgets($this->_stream);
            if($line === false) {
                throw new UnexpectedValueException('Failed to read from stream!');
            }
            //echo $line;
            
            if($line != "\r\n") {
                list($key, $value) = explode(': ', $line, 2);
                $event[trim($key)] = trim($value);
            } else {
                if(array_key_exists('Event', $event)) {
                    switch($event['Event']) {
                        case 'PeerStatus':
                            $this->_processPeerStatus($event);
                            break;
                            
                        default:
                            echo "Unknow Event: {$event['Event']}" . PHP_EOL;
                    }
                }
                
                // reset event array
                $event = array();
                
                continue;
            }
        }
    }
  
    protected function _processPeerStatus($_event)
    {
        if($_event['Peer'] == 'SIP/mw-521') {
            var_dump($_event);
        }
    }
  
}

