 <?php
/**
 * Tine 2.0
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * cli server
 *
 * This class handles all requests from cli scripts
 *
 * @package     Tinebase
 */
class Tinebase_Frontend_Cli
{
    /**
     * the internal name of the application
     *
     * @var string
     */
    protected $_applicationName = 'Tinebase';

    /**
     * authentication
     *
     * @param string $_username
     * @param string $_password
     */
    public function authenticate($_username, $_password)
    {
        $authResult = Tinebase_Auth::getInstance()->authenticate($_username, $_password);
        
        if ($authResult->isValid()) {
            $accountsController = Tinebase_User::getInstance();
            try {
                $account = $accountsController->getFullUserByLoginName($authResult->getIdentity());
            } catch (Tinebase_Exception_NotFound $e) {
                echo 'account ' . $authResult->getIdentity() . ' not found in account storage'."\n";
                exit();
            }
            
            Tinebase_Core::set('currentAccount', $account);

            $ipAddress = '127.0.0.1';
            $account->setLoginTime($ipAddress);
                        
            Tinebase_AccessLog::getInstance()->addLoginEntry(
                'cli call', // session id not available
                $authResult->getIdentity(),
                $ipAddress,
                $authResult->getCode(),
                Tinebase_Core::getUser()
            ); 
        } else {
            echo "Wrong username and/or password.\n";
            exit();            
        }
    }
    
    /**
     * handle request (call -ApplicationName-_Cli.-MethodName- or -ApplicationName-_Cli.getHelp)
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean success
     */
    public function handle($_opts)
    {
        list($application, $method) = explode('.', $_opts->method);
        $class = $application . '_Frontend_Cli';
        
        if (@class_exists($class)) {
            $object = new $class;
            if ($_opts->info) {
                $result = $object->getHelp();
            } else {
                $result = call_user_func(array($object, $method), $_opts);
            }
        } else {
            echo "Class $class does not exist.\n";
            $result = FALSE;
        }
        
        return $result;
    }

    /**
     * trigger async events (for example via cronjob)
     *
     * @param Zend_Console_Getopt $_opts
     * @return boolean success
     */
    public function triggerAsyncEvents($_opts)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Triggering async events from CLI.');
        
        try {
            $cronuser = Tinebase_User::getInstance()->getFullUserByLoginName($_opts->username);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // get user for cronjob from config / set default admin group
            $cronuserId = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::CRONUSERID)->value;
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Setting user with id ' . $cronuserId . ' as cronuser.');
            $cronuser = Tinebase_User::getInstance()->getFullUserById($cronuserId);
        }
        Tinebase_Core::set(Tinebase_Core::USER, $cronuser);
        
        $scheduler = Tinebase_Core::getScheduler();
        $scheduler->run();
        
        return TRUE;
    }
    
    /**
     * clear table as defined in arguments
     * can clear the following tables:
     * - credential_cache
     * 
     * @param $_opts
     * @return boolean success
     */
    public function clearTable(Zend_Console_Getopt $_opts)
    {
        $tables = $_opts->getRemainingArgs();
        if (empty($tables)) {
            echo "No table given.\n";
            return FALSE;
        }
        
        // check if admin for tinebase
        if (! Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::ADMIN)) {
            echo "No permission.\n";
            return FALSE;
        }
        
        foreach ($tables as $table) {
            switch ($table) {
                case 'credential_cache':
                    if (Setup_Controller::getInstance()->isInstalled('Felamimail')) {
                        // delete only records that are not related to email accounts
                        Tinebase_Core::getDb()->query(
                            'delete ' . SQL_TABLE_PREFIX . 'credential_cache FROM `' . SQL_TABLE_PREFIX . 'credential_cache`' .
                            ' LEFT JOIN ' . SQL_TABLE_PREFIX . 'felamimail_account ON ' . SQL_TABLE_PREFIX . 'credential_cache.id = ' . 
                                SQL_TABLE_PREFIX . 'felamimail_account.credentials_id' .
                            ' WHERE ' . SQL_TABLE_PREFIX . 'felamimail_account.credentials_id IS NULL');
                        break;
                    } else {
                        // fallthrough
                    }
                case 'access_log':
                    Tinebase_Core::getDb()->query('TRUNCATE ' . SQL_TABLE_PREFIX . $table);
                    break;
                default:
                    echo 'Table ' . $table . " not supported or argument missing.\n";
            }
            echo "Cleared table $table.\n";
        }
        
        return TRUE;
    }
}
