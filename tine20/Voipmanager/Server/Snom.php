<?php
/**
 * Tine 2.0
 * 
 * @package     Voipmanager
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * snom Server class with handle() function
 * 
 * @package     Voipmanager
 * @subpackage  Server
 */
class Voipmanager_Server_Snom
{
    /**
     * handler for command line scripts
     * 
     * @return boolean
     */
    public function handle()
    {        
        if (isset($_REQUEST['TINE20SESSID'])) {
            Zend_Session::setId($_REQUEST['TINE20SESSID']);
        }
        
        Tinebase_Core::initFramework();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' is snom xml request. method: ' . (isset($_REQUEST['method']) ? $_REQUEST['method'] : 'EMPTY'));
        
        $server = new Tinebase_Http_Server();
        $server->setClass('Voipmanager_Frontend_Snom', 'Voipmanager');
        $server->setClass('Phone_Frontend_Snom', 'Phone');
        
        $server->handle($_REQUEST);
    }
}
