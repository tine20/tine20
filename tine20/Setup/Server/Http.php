<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * HTTP Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Setup_Server_Http implements Tinebase_Server_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Laminas\Http\Request $request = null, $body = null)
    {
        Tinebase_Session_Abstract::setSessionEnabled('TINE20SETUPSESSID');
            
        if (Tinebase_Session::sessionExists()) {
            Setup_Core::startSetupSession();
        }
        
        Setup_Core::initFramework();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            .' is http request. method: ' . $this->getRequestMethod());

        $server = new Tinebase_Http_Server();
        $server->setClass('Setup_Frontend_Http', 'Setup');
        $server->setClass('Tinebase_Frontend_Http', 'Tinebase'); // needed for fetching translation in DEVELOPMENT mode
        
        if (empty($_REQUEST['method'])) {
            $_REQUEST['method'] = 'Setup.mainScreen';
        }

        $response = $server->handle($_REQUEST);
        if ($response instanceof \Laminas\Diactoros\Response) {
            $emitter = new \Zend\HttpHandlerRunner\Emitter\SapiEmitter();
            $emitter->emit($response);
        }
    }
    
    /**
    * returns request method
    *
    * @return string
    */
    public function getRequestMethod()
    {
        return (isset($_REQUEST['method'])) ? $_REQUEST['method'] : NULL;
    }
}
