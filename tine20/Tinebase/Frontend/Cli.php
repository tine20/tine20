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
class Tinebase_Frontend_Cli extends Tinebase_Frontend_Cli_Abstract
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

            Tinebase_AccessLog::getInstance()->create(new Tinebase_Model_AccessLog(array(
                'sessionid'     => 'cli call',
                'login_name'    => $authResult->getIdentity(),
                'ip'            => $ipAddress,
                'li'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                'lo'            => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG),
                'result'        => $authResult->getCode(),
                'account_id'    => Tinebase_Core::getUser()->getId(),
                'clienttype'    => 'TineCli',
            )));
            
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
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Triggering async events from CLI.');
        
        try {
            $cronuser = Tinebase_User::getInstance()->getFullUserByLoginName($_opts->username);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // get user for cronjob from config / set default admin group
            $cronuserId = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::CRONUSERID)->value;
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' Setting user with id ' . $cronuserId . ' as cronuser.');
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
     * - access_log
     * - async_job
     * 
     * if param data is given (for example: -- date=2010-09-17), all records before this date are deleted (if the table has a date field)
     * 
     * @param $_opts
     * @return boolean success
     */
    public function clearTable(Zend_Console_Getopt $_opts)
    {
        $args = $_opts->getRemainingArgs();

        if (! $this->_checkAdminRight()) {
            return FALSE;
        }

        // check for date in args
        foreach ($args as $idx => $arg) {
            $split = explode('=', $arg);
            if (is_array($split) && $split[0] == 'date') {
                unset($args[$idx]);
                $date = $split[1];
            }
        }

        if (empty($args)) {
            echo "No table given.\n";
            return FALSE;
        }
        
        $db = Tinebase_Core::getDb();
        foreach ($args as $table) {
            switch ($table) {
                case 'access_log':
                    if (isset($date)) {
                        echo "\nRemoving all access log entries before $date ...";
                        $where = array(
                            $db->quoteInto($db->quoteIdentifier('li') . ' < ?', $date)
                        );
                        $db->delete(SQL_TABLE_PREFIX . $table, $where);
                    } else {
                        $db->query('TRUNCATE ' . SQL_TABLE_PREFIX . $table);
                    }
                    break;
                case 'async_job':
                    $db->query(
                        'delete FROM ' . SQL_TABLE_PREFIX . 'async_job' .
                        " WHERE status='success'");
                    break;
                case 'credential_cache':
                    if (Setup_Controller::getInstance()->isInstalled('Felamimail')) {
                        // delete only records that are not related to email accounts
                        $db->query(
                            'delete ' . SQL_TABLE_PREFIX . 'credential_cache FROM `' . SQL_TABLE_PREFIX . 'credential_cache`' .
                            ' LEFT JOIN ' . SQL_TABLE_PREFIX . 'felamimail_account ON ' . SQL_TABLE_PREFIX . 'credential_cache.id = ' . 
                                SQL_TABLE_PREFIX . 'felamimail_account.credentials_id' .
                            ' WHERE ' . SQL_TABLE_PREFIX . 'felamimail_account.credentials_id IS NULL');
                        break;
                    } else {
                        // fallthrough
                    }                    
                default:
                    echo 'Table ' . $table . " not supported or argument missing.\n";
            }
            echo "\nCleared table $table.";
        }
        echo "\n\n";
        
        return TRUE;
    }
    
    /**
     * add new customfield config
     * 
     * needs args like this:
     * application="Addressbook" name="datefield" label="Date" model="Addressbook_Model_Contact" type="datefield"
     * @see Tinebase_Model_CustomField_Config for full list 
     * 
     * @param $_opts
     * @return boolean success
     */
    public function addCustomfield(Zend_Console_Getopt $_opts)
    {
        if (! $this->_checkAdminRight()) {
            return FALSE;
        }
        
        // parse args
        $args = $_opts->getRemainingArgs();
        $data = array();
        foreach ($args as $idx => $arg) {
            list($key, $value) = explode('=', $arg);
            if ($key == 'application') {
                $key = 'application_id';
                $value = Tinebase_Application::getInstance()->getApplicationByName($value)->getId();
            }
            $data[$key] = $value;
        }
        
        $customfieldConfig = new Tinebase_Model_CustomField_Config($data);
        $cf = Tinebase_CustomField::getInstance()->addCustomField($customfieldConfig);

        echo "\nCreated customfield: ";
        print_r($cf->toArray());
        echo "\n";
        
        return TRUE;
    }
}
