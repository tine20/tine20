<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Cli Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Setup_Server_Cli implements Tinebase_Server_Interface
{
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Laminas\Http\Request $request = null, $body = null)
    {
        try {
            $opts = new Zend_Console_Getopt(
            array(
                'help|h'                    => 'Display this help Message',
                'verbose|v'                 => 'Output messages',
                'config|c=s'                => 'Path to config.inc.php file',
                'setconfig'                 => 'Update config. To specify the key and value, append \' -- configkey="your_key" configValue="your config value"\'
                         Examples:
                           setup.php --setconfig -- configkey=sample1 configvalue=value11
                           setup.php --setconfig -- configkey=sample2 configvalue=arrayKey1:Value1,arrayKey2:value2
                           setup.php --setconfig -- configkey=calendarsample3 configvalue=value11 app=Calendar
                          ',
                'getconfig'                 => 'Get Config value for a specify the key \' -- configkey="your_key" app=Calendar\'',
                'check_requirements'        => 'Check if all requirements are met to install and run tine20',
                'create_admin'              => 'Create new admin user (or reactivate if already exists)',
                'clear_cache'               => 'Clears all caches',
                'clear_cache_dir'           => 'Clears cache directories', 
                'install-s'                 => 'Install applications [all if nothing installed yet] or comma separated list (use "all" as parameter to install all available apps);'
                    . ' To specify the login name and login password of the admin user that is created during installation, append \' -- adminLoginName="admin" adminPassword="password"\''
                    . ' To add imap or smtp settings, append (for example) \' -- imap="host:mail.example.org,port:143,dbmail_host:localhost" smtp="ssl:tls"\'',
                'update-s'                  => 'Update applications [All] or comma separated list - supports verbose mode (-v), strict=1 + skipQueueCheck=1 and rerun=UserManual_Setup_Update_15::update001,... flags',
                'update_needed'             => 'returns "Update required" and return code 1 if update is required',
                'uninstall-s'               => 'Uninstall application [All] or comma separated list',
                    'removemailaccounts'    => 'Only usable with uninstall. Removes all mail accounts belonging to this installation',
                'install_dump'              => 'Install Tine from a backup
                         Examples:
                           setup.php --install_dump -- db=1 files=1 backupDir=/backup/tine20 keepTinebaseID=1
                           setup.php --install_dump -- db=1 files=1 backupUrl=https://username:password@example.org/mydump',
                'list-s'                    => 'List installed applications',
                'sync_accounts_from_ldap'   => 'Import user and groups from ldap',
                    'dbmailldap'            => 'Only usable with sync_accounts_from_ldap. Fetches dbmail email user data from LDAP.',
                    'onlyusers'             => 'Only usable with sync_accounts_from_ldap. Fetches only users and no groups from LDAP.',
                    'syncdeletedusers'      => 'Only usable with sync_accounts_from_ldap. Removes users from Tine 2.0 DB that no longer exist in LDAP',
                    'syncaccountstatus'     => 'Only usable with sync_accounts_from_ldap. Synchronizes current account status from LDAP',
                    'syncontactphoto'       => 'Only usable with sync_accounts_from_ldap. Always syncs contact photo from ldap',
                'sync_passwords_from_ldap'  => 'Synchronize user passwords from ldap',
                'egw14import'               => 'Import user and groups from egw14
                         Examples: 
                          setup.php --egw14import /path/to/config.ini',
                'reset_demodata'            => 'reinstall applications and install Demodata (Needs Admin user)',
                'updateAllImportExportDefinitions' => 'update ImportExport definitions for all applications',
                'updateAllAccountsWithAccountEmail' => 'create/update email users with current account
                         Examples:
                           setup.php --updateAllAccountsWithAccountEmail -- fromInstance=master.mytine20.com',
                'backup'                    => 'backup config and data
                         Examples:
                           setup.php --backup -- config=1 db=1 files=1 emailusers=1 backupDir=/backup/tine20 noTimestamp=1 novalidate=1',
                'restore'                   => 'restore config and data
                         Examples:
                           setup.php --restore -- config=1 db=1 files=1 backupDir=/backup/tine20',
                'compare'                   => 'compare schemas with another database
                        Examples:
                           setup.php --compare -- otherdb=tine20other',
                'mysql'                   => 'run mysql client
                        Examples:
                           setup.php --mysql -- platform=docker',
                'setpassword'               => 'set system user password
                        Examples:
                           setup.php --setpassword -- username=myusername password=myrandompw',
                'pgsqlMigration'            => 'migrate from pgsql to mysql
                        Examples:
                            setup.php --pgsqlMigration -- mysqlConfigFile=/path/to/config/file',
                'upgradeMysql564'           => 'update database to use features of MySQL 5.6.4+
                        Examples:
                            setup.php --upgradeMysql564',
                'migrateUtf8mb4'            => 'update database to use MySQL utf8mb4
                        Examples:
                            setup.php --migrateUtf8mb4',
                'maintenance_mode'          => 'set systems maintenance mode state
                        Examples:
                           setup.php --maintenance_mode -- mode=[on|off] apps=[OnlyOfficeIntegrator,Felamimail,etc.] flags=[skipApps,onlyApps,allowAdminLogin]',
                'config_from_env'           => 'generates config from environment variables like TINE20__<application>_<propertiy>',
                'is_installed'           => 'Checks if tine20 is installed, otherwise returns 1.',
                'add_auth_token'        => 'Add a new token to table tine20_auth_token
                        Examples:
                            setup.php --add_auth_token -- user=admin id=longlongid auth_token=longlongtoken valid_until=2023-02-18 channels=broadcasthub,test,test2',
            ));
            $opts->parse();
        } catch (Zend_Console_Getopt_Exception $e) {
            echo "Invalid usage: {$e->getMessage()}\n\n";
            echo $e->getUsageMessage();
            exit;
        }

        if (count($opts->toArray()) === 0 || $opts->h || 
            (empty($opts->install) && 
            empty($opts->install_dump) &&
            empty($opts->update) &&
            empty($opts->update_needed) &&
            empty($opts->uninstall) &&
            empty($opts->list) && 
            empty($opts->sync_accounts_from_ldap) && 
            empty($opts->sync_passwords_from_ldap) && 
            empty($opts->egw14import) && 
            empty($opts->check_requirements) && 
            empty($opts->reset_demodata) &&
            empty($opts->updateAllImportExportDefinitions) &&
            empty($opts->updateAllAccountsWithAccountEmail) &&
            empty($opts->create_admin) &&
            empty($opts->clear_cache) &&
            empty($opts->clear_cache_dir) &&
            empty($opts->setconfig) &&
            empty($opts->backup) &&
            empty($opts->restore) &&
            empty($opts->compare) &&
            empty($opts->mysql) &&
            empty($opts->setpassword) &&
            empty($opts->getconfig) &&
            empty($opts->upgradeMysql564) &&
            empty($opts->migrateUtf8mb4) &&
            empty($opts->pgsqlMigration) &&
            empty($opts->maintenance_mode) &&
            empty($opts->config_from_env) &&
            empty($opts->is_installed) &&
            empty($opts->add_auth_token)))
        {
            echo $opts->getUsageMessage();
            if ($opts->config) {
                echo "Using config: " . $opts->config . "\n";
            }
            exit;
        }

        if ($opts->config) {
            // add path to config.inc.php to include path
            $path = strstr($opts->config, 'config.inc.php') !== false ? dirname($opts->config) : $opts->config;
            set_include_path($path . PATH_SEPARATOR . get_include_path());
        }

        try {
            Setup_Core::initFramework();
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            echo $e->getMessage() . "\n";
            exit(1);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Is cli request. method: ' . $this->getRequestMethod());

        $setupServer = new Setup_Frontend_Cli();
        try {
            $result = $setupServer->handle($opts);
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            echo $e->getMessage() . "\n";
            $result = 1;
        }

        exit($result);
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        $opts = Tinebase_Core::get('opts');
        return (isset($opts->mode)) ? $opts->mode : NULL;
    }
}
