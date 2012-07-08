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
     * handler for HTTP api requests
     * @todo session expire handling
     * 
     * @return HTTP
     */
    public function handle()
    {
        Setup_Core::initFramework();
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' is http request. method: ' . $this->getRequestMethod());

        $server = new Tinebase_Http_Server();
        $server->setClass('Setup_Frontend_Http', 'Setup');
        $server->setClass('Tinebase_Frontend_Http', 'Tinebase'); // needed for fetching translation in DEVELOPMENT mode
        
        if (empty($_REQUEST['method'])) {
            $_REQUEST['method'] = 'Setup.mainScreen';
        }
        
        $server->handle($_REQUEST);
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
