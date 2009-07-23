<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Cli.php 5117 2008-10-27 11:00:26Z p.schuele@metaways.de $
 * 
 */

/**
 * Cli Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Setup_Server_Cli extends Setup_Server_Abstract
{
    /**
     * handler for command line scripts
     * 
     * @return boolean
     */
    public function handle()
    {        
        try {
            $opts = new Zend_Console_Getopt(
            array(
                'help|h'                => 'Display this help Message',
                'verbose|v'             => 'Output messages',
                'config|c=w'            => 'Path to config.inc.php file',
            
                'install-s'             => 'Install applications [All] or comma separated list',              
                'update-s'              => 'Update applications [All] or comma separated list',             
                'uninstall-s'           => 'Uninstall application [All] or comma separated list',
                'list-s'                => 'List installed applications',
                'import_accounts'       => 'Import user and groups from ldap',
                #'username'             => 'Username [required]',              
                #'password'             => 'Password [required]',              
            ));
            $opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
        	echo "Invalid usage: {$e->getMessage()}\n\n";
            echo $e->getUsageMessage();
            exit;
        }

        if (count($opts->toArray()) === 0 || $opts->h || (empty($opts->install) && empty($opts->update) && empty($opts->uninstall) && empty($opts->list) && empty($opts->import_accounts))) {
            echo $opts->getUsageMessage();
            exit;
        }

        $this->_initFramework();

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Is cli request. method: ' . (isset($opts->mode) ? $opts->mode : 'EMPTY'));
        
        $setupServer = new Setup_Frontend_Cli();
        #$setupServer->authenticate($opts->username, $opts->password);
        return $setupServer->handle($opts);        
    }
}
