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
                'help|h'                    => 'Display this help Message',
                'verbose|v'                 => 'Output messages',
                'config|c=w'                => 'Path to config.inc.php file',
                'setconfig'                 => 'Update config. To specify the key and value, append \' -- configKey="your_key" configValue="your config value"\'
                         Examples:
                           setup.php --setconfig -- configkey=sample1 configvalue=value11
                           setup.php --setconfig -- configkey=sample2 configvalue=arrayKey1:Value1,arrayKey2:value2',
                'check_requirements'        => 'Check if all requirements are met to install and run tine20',
            
                'install-s'                 => 'Install applications [All] or comma separated list;'
                    . ' To specify the login name and login password of the admin user that is created during installation, append \' -- adminLoginName="admin" adminPassword="password"\''
                    . ' To add imap or smtp settings, append (for example) \' -- imap="host:mail.example.org,port:143,dbmail_host:localhost" smtp="ssl:tls"\'',
                'update-s'                  => 'Update applications [All] or comma separated list',             
                'uninstall-s'               => 'Uninstall application [All] or comma separated list',
                'list-s'                    => 'List installed applications',
                'sync_accounts_from_ldap'   => 'Import user and groups from ldap',
                'egw14import'               => 'Import user and groups from egw14
                         Examples: 
                          setup.php --egw14import egwdbhost egwdbuser egwdbpass egwdbname latin1
                          setup.php --egw14import egwdbhost egwdbuser egwdbpass egwdbname utf8'
                #'username'             => 'Username [required]',              
                #'password'             => 'Password [required]',              
            ));
            $opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
        	echo "Invalid usage: {$e->getMessage()}\n\n";
            echo $e->getUsageMessage();
            exit;
        }

        if (count($opts->toArray()) === 0 || $opts->h || (empty($opts->install) && empty($opts->update) && empty($opts->uninstall) && empty($opts->list) && empty($opts->sync_accounts_from_ldap) && empty($opts->egw14import) && empty($opts->check_requirements) && empty($opts->setconfig))) {
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
