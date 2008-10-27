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
 */

/**
 * Cli Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Cli extends Tinebase_Server_Abstract
{
    /**
     * handler for command line scripts
     * 
     * @return boolean
     */
    public function handle()
    {        
        $this->_initFramework();
        
        $opts = Tinebase_Core::get('opts');

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Is cli request. method: ' . (isset($opts->method) ? $opts->method : 'EMPTY'));
        //Zend_Registry::get('logger')->debug('Cli args: ' . print_r($opts->getRemainingArgs(), true));

        $tinebaseServer = new Tinebase_Frontend_Cli();
        $tinebaseServer->authenticate($opts->username, $opts->password);
        return $tinebaseServer->handle($opts);        
    }
}
