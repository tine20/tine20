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
        
        $event = new Tinebase_Event_Async_Minutely();
        Tinebase_Event::fireEvent($event);
        
        return TRUE;
    }

    /**
     * fix encoding of imported records
     *
     * @param Zend_Console_Getopt $_opts
     */
    public function fixEncoding($_opts)
    {
        // fix encoding of timesheets/timeaccounts/contracts
        
        // get timesheets
        $tsFilter = new Timetracker_Model_TimesheetFilter(array(
            array(
                'field' => 'start_date', 
                'operator' => 'before', 
                'value' => '2009-01-01'
            ),
            array(
                'field' => 'description', 
                'operator' => 'not', 
                'value' => 'not set (imported)'
            ),
        ));
        $timesheetBackend = new Timetracker_Backend_Timesheet(); 
        $ids = $timesheetBackend->search($tsFilter, NULL, TRUE);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found ' . count($ids) . ' timesheets.');
        echo 'Found ' . count($ids) . ' timesheets. Updating ';
        foreach ($ids as $id) {
            $record = $timesheetBackend->get($id);
            // update description
            $record->description = utf8_decode($record->description);
            echo '.';
            $timesheetBackend->update($record);
        }
        echo " done\n";
        
        // update timeaccounts
        $filter = new Timetracker_Model_TimeaccountFilter(array(
            array(
                'field' => 'created_by', 
                'operator' => 'equals', 
                'value' => '3168'
            ),
            //array(
            //    'field' => 'number', 
            //    'operator' => 'equals', 
            //    'value' => 'Ab-42703'
            //),
            array(
                'field' => 'showClosed', 
                'operator' => 'equals', 
                'value' => TRUE
            ),
        ));
        $backend = new Timetracker_Backend_Timeaccount(); 
        $ids = $backend->search($filter, NULL, TRUE);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found ' . count($ids) . ' timeaccounts.');
        echo 'Found ' . count($ids) . ' timeaccounts. Updating ';
        foreach ($ids as $id) {
            $record = $backend->get($id);

            // update title & description
            if (empty($record->last_modified_by) && $record->creation_time->getIso() < '2009-01-01') {
                $this->_update($record, array('title', 'description'), $backend);
            }
        }
        echo " done\n";
        
        // update contracts
        /*
        $filter = new Erp_Model_ContractFilter(array(
            //array(
            //    'field' => 'query', 
            //    'operator' => 'contains', 
            //    'value' => 'Markus'
            //),
        ));
        $backend = new Erp_Backend_Contract(); 
        $ids = $backend->search($filter, NULL, TRUE);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Found ' . count($ids) . ' contracts.');
        echo 'Found ' . count($ids) . ' contracts. Updating ';
        foreach ($ids as $id) {
            $record = $backend->get($id);
            
            // update title & description
            if (empty($record->last_modified_by)) {
                $this->_update($record, array('title', 'description'), $backend);
            }
        }
        echo " done\n";
        */
    }
    
    /**
     * decode and update record
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_fields
     * @param Tinebase_Backend_Sql_Abstract $_backend
     */
    protected function _update($_record, $_fields, $_backend)
    {
        foreach ($_fields as $field) {
            $newValue = utf8_decode($_record->$field);
            //echo mb_detect_encoding($newValue);
            $_record->$field = $newValue;
        }

        echo '.';
        $_backend->update($_record);
    }
}
