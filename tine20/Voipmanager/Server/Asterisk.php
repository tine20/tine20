<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * Asterisk Server class with handle() function
 * 
 * @package     Voipmanager
 * @subpackage  Server
 */
class Voipmanager_Server_Asterisk extends Tinebase_Server_Abstract
{
    /**
     * handler for command line scripts
     * 
     * @return boolean
     */
    public function handle()
    {        
        $this->_initFramework();
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' is Asterisk curl request. method: ' . (isset($_REQUEST['method']) ? $_REQUEST['method'] : 'EMPTY'));
        
        if(Tinebase_Controller::getInstance()->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']) === true) {
            $server = new Tinebase_Http_Server();
            $server->setClass('Voipmanager_Frontend_Asterisk_SipPeers', 'Voipmanager_SipPeers');
            
            // set method to a usefull value
            list($class) = explode('.', $_REQUEST['method']);
            $method = ucfirst(substr($_REQUEST['action'],1));
            $_REQUEST['method'] = $class . '.handle' . $method;
            
            $server->handle($_REQUEST);
        } else {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' auth failed ');
        }
    }
}
