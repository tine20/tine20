<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        replace/remove Tinebase_Cli
 * @todo        finish & use it
 * @todo        add Server Interface?
 */

/**
 * Cli Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Cli
{
    /**
     * handler for command line scripts
     * 
     * @param Zend_Console_Getopt $_opts
     * @return boolean
     */
    public function handleCli($_opts)
    {        
        $this->_initFramework();

        Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ .' Is cli request. method: ' . (isset($_opts->method) ? $_opts->method : 'EMPTY'));
        //Zend_Registry::get('logger')->debug('Cli args: ' . print_r($_opts->getRemainingArgs(), true));

        $tinebaseServer = new Tinebase_Frontend_Cli();
        $tinebaseServer->authenticate($_opts->username, $_opts->password);
        return $tinebaseServer->handle($_opts);        
    }
}
