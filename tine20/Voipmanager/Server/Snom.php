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
class Voipmanager_Server_Snom implements Tinebase_Server_Interface
{
    /**
     * the request
     *
     * @var \Zend\Http\Request
     */
    protected $_request = NULL;
    
    /**
     * the request body
     * 
     * @var stream|string
     */
    protected $_body;
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        Tinebase_Session::setSessionOptions(array(
            'use_cookies'      => 0,
            'use_only_cookies' => 0
        ));
        
        Tinebase_Core::initFramework();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ .' is snom xml request. method: ' . $this->getRequestMethod());
        
        $server = new Tinebase_Http_Server();
        $server->setClass('Voipmanager_Frontend_Snom', 'Voipmanager');
        $server->setClass('Phone_Frontend_Snom', 'Phone');
        
        $server->handle($_REQUEST);
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        return (isset($_REQUEST['method'])) ? $_REQUEST['method'] : NULL;
    }
}
