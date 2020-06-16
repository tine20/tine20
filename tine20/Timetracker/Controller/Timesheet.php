<?php
/**
 * Timesheet controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Timesheet controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Timesheet extends Tinebase_Controller_Record_Abstract
{
    /**
     * should deadline be checked
     * 
     * @var boolean
     */
    protected $_doCheckDeadline = TRUE;

    /**
     * custom acl switch
     *
     * @var boolean
     */
    protected $_doTimesheetContainerACLChecks = TRUE;

    /**
     * check deadline or not
     * 
     * @return boolean
     */
    public function doCheckDeadLine()
    {
        $value = (func_num_args() === 1) ? (bool) func_get_arg(0) : NULL;
        return $this->_setBooleanMemberVar('_doCheckDeadline', $value);
    }
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        
        // config
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_Timesheet();
        $this->_modelName = 'Timetracker_Model_Timesheet';
        $this->_resolveCustomFields = TRUE;
        
        // disable container ACL checks as we don't init the 'Shared Timesheets' grants in the setup
        $this->_doContainerACLChecks = FALSE;
        
        // use modlog and don't completely delete records
        $this->_purgeRecords = FALSE;
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone()
    {
    }
    
    /**
     * field grants for specific timesheet fields
     *
     * @var array
     */
    protected $_fieldGrants = array(
        'is_billable' => array('default' => 1,  'requiredGrant' => Timetracker_Model_TimeaccountGrants::MANAGE_BILLABLE),
        'billed_in'   => array('default' => '', 'requiredGrant' => Tinebase_Model_Grants::GRANT_ADMIN),
        'is_cleared'  => array('default' => 0,  'requiredGrant' => Tinebase_Model_Grants::GRANT_ADMIN),
    );
    
    /**
     * holds the instance of the singleton
     *
     * @var Timetracker_Controller_Timesheet
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timetracker_Controller_Timesheet
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }

    public static function unsetInstance()
    {
        self::$_instance = null;
    }

    /****************************** functions ************************/

    /**
     * get all timesheets for a timeaccount
     *
     * @param string $_timeaccountId
     * @return Tinebase_Record_RecordSet of Timetracker_Model_Timesheet records
     */
    public function getTimesheetsByTimeaccountId($_timeaccountId)
    {
        $filter = new Timetracker_Model_TimesheetFilter(array(
            array('field' => 'timeaccount_id', 'operator' => 'AND', 'value' => array(
                array('field' => 'id', 'operator' => 'equals', 'value' => $_timeaccountId),
            ))
        ));
        
        $records = $this->search($filter);
        
        return $records;
    }
    
    /**
     * find timesheets by the given arguments. the result will be returned in an array
     *
     * @param string $timeaccountId
     * @param Tinebase_DateTime $startDate
     * @param Tinebase_DateTime $endDate
     * @param string $destination
     * @param string $taCostCenter
     * @param string $cacheId
     * @return array
     *
     * @deprecated can be removed?
     */
    public function findTimesheetsByTimeaccountAndPeriod($timeaccountId, $startDate, $endDate, $destination = NULL, $taCostCenter = NULL)
    {
        $filter = new Timetracker_Model_TimesheetFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'timeaccount_id', 'operator' => 'equals', 'value' => $timeaccountId)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $endDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'after', 'value' => $startDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Bool(array('field' => 'is_cleared', 'operator' => 'equals', 'value' => true)));
        
        $timesheets = $this->search($filter);
        
        $matrix = array();
        foreach ($timesheets as $ts) {
            $matrix[] = array(
                'userAccountId' => $ts->account_id,
                'amount' => ($ts->duration / 60),
                'destination' => $destination,
                'taCostCenter' => $taCostCenter
            );
        }
        
        return $matrix;
    }

    /**
     * calculates duration, start and end from given value
     *
     * @param Timetracker_Model_Timesheet $_record
     * @return void
     */
    protected function _calculateTimes(Timetracker_Model_Timesheet $_record)
    {
        $duration = $_record->duration;
        $start = $_record->start_time;
        $end = $_record->end_time;

        // If start and end ist given calculate duration and overwrite default
        if (isset($start) && isset($end)){
            $start = new dateTime($_record->start_date . ' ' . $start);
            $end = new dateTime($_record->start_date . ' ' . $end);
            
            if ($end < $start) {
                $end = $end->modify('+1 days');
            }

            $duration = $end->diff($start);
            $_record->duration = $duration->h * 60 + $duration->i;
        } else if (isset($duration) && isset($start)){
            // If duration and start is set calculate the end
            $start = new dateTime($_record->start_date . ' ' . $start);
            
            $end = $start->modify('+' . $duration . ' minutes');
            $_record->end_time = $end->format('H:i');

        } else if (isset($duration) && isset($end)){
            // If start is not set but duration and end calculate start instead
            $end = new dateTime($_record->start_date . ' ' . $end);

            $start = $end->modify('-' . $duration . ' minutes');
            $_record->start_time = $start->format('H:i');
        }

        if (empty($_record->accounting_time)) {
            $_record->accounting_time = $_record->duration;
        }
    }
    
    /**
     * checks deadline of record
     * 
     * @param Timetracker_Model_Timesheet $_record
     * @param boolean $_throwException
     * @return void
     * @throws Timetracker_Exception_Deadline
     */
    protected function _checkDeadline(Timetracker_Model_Timesheet $_record, $_throwException = TRUE)
    {
        if (! $this->_doCheckDeadline) {
            return;
        }
        
        // get timeaccount
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($_record->timeaccount_id);
        
        if (isset($timeaccount->deadline) && $timeaccount->deadline == Timetracker_Model_Timeaccount::DEADLINE_LASTWEEK) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Check if deadline is exceeded for timeaccount ' . $timeaccount->title);
            
            // it is only on monday allowed to add timesheets for last week
            $date = new Tinebase_DateTime();
            
            $date->setTime(0,0,0);
            $dayOfWeek = $date->get('w');
            
            if ($dayOfWeek >= 2) {
                // only allow to add ts for this week
                $date->sub($dayOfWeek-1, Tinebase_DateTime::MODIFIER_DAY);
            } else {
                // only allow to add ts for last week
                $date->sub($dayOfWeek+6, Tinebase_DateTime::MODIFIER_DAY);
            }
            
            // convert start date to Tinebase_DateTime
            $startDate = new Tinebase_DateTime($_record->start_date);
            if ($date->compare($startDate) >= 0) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Deadline exceeded: ' . $startDate . ' < ' . $date);
                
                if ($this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)
                     || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                        . ' User with admin / manage all rights is allowed to save Timesheet even if it exceeds the deadline.'
                    );
                } else if ($_throwException) {
                    throw new Timetracker_Exception_Deadline();
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Valid date: ' . $startDate . ' >= ' . $date);
            }
        }
    }
    
    /****************************** overwritten functions ************************/    
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_checkDeadline($_record);
        $this->_calculateTimes($_record);
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_checkDeadline($_record);
        $this->_calculateTimes($_record);
    }

    /**
     * set/get checking ACL rights
     *
     * NOTE: as our business logic here needs $this->>_doContainerACLChecks to be turned off
     *       we introduce a new switch to turn off all grants checking here
     *
     * @param  boolean $setTo
     * @return boolean
     */
    public function doContainerACLChecks($setTo = NULL)
    {
        return $this->_setBooleanMemberVar('_doTimesheetContainerACLChecks', $setTo);
    }

    /**
     * check grant for action
     *
     * @param Timetracker_Model_Timeaccount $_record
     * @param string $_action
     * @param boolean $_throw
     * @param string $_errorMessage
     * @param Timetracker_Model_Timesheet $_oldRecord
     * @return boolean
     * @throws Tinebase_Exception_AccessDenied
     * 
     * @todo think about just setting the default values when user 
     *       hasn't the required grant to change the field (instead of throwing exception) 
     */
    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (! $this->_doTimesheetContainerACLChecks) {
            return TRUE;
        }

        $isAdmin = false;
        // users with MANAGE_TIMEACCOUNTS have all grants here
        if ( $this->checkRight(Timetracker_Acl_Rights::MANAGE_TIMEACCOUNTS, FALSE)
            || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Tinebase_Model_Grants::GRANT_ADMIN)) {
            $isAdmin = true;
        }

        // only TA managers are allowed to alter TS of closed TAs, but they have to confirm first that they really want to do it
        if ($_action != 'get') {
            $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->get($_record->timeaccount_id);
            if (! $timeaccount->is_open) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' This Timeaccount is already closed!');

                if ($isAdmin === true) {
                    if (is_array($this->_requestContext) && isset($this->_requestContext['skipClosedCheck']) && $this->_requestContext['skipClosedCheck']) {
                        return true;
                    }
                }

                if ($_throw) {
                    throw new Timetracker_Exception_ClosedTimeaccount();
                }
                return FALSE;
            }
            
            // check if timeaccount->is_billable is false => set default in fieldGrants to 0 and allow only managers to change it
            if (!$timeaccount->is_billable) {
                $this->_fieldGrants['is_billable']['default'] = 0;
                $this->_fieldGrants['is_billable']['requiredGrant'] = Tinebase_Model_Grants::GRANT_ADMIN;
            }
        }

        if ($isAdmin === true) {
            return true;
        }

        
        $hasGrant = FALSE;
        
        switch ($_action) {
            case 'get':
                $hasGrant = (
                    Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, array(
                        Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                        Timetracker_Model_TimeaccountGrants::BOOK_ALL
                    ))
                    || ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN))
                );
                break;
            case 'create':
                $hasGrant = (
                    ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN))
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL)
                );
                
                if ($hasGrant) {
                    foreach ($this->_fieldGrants as $field => $config) {
                        $fieldValue = $_record->$field;
                        if (isset($fieldValue) && $fieldValue != $config['default']) {
                            $hasGrant &= Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, $config['requiredGrant']);
                        }
                    }
                }

                break;
            case 'update':
                $hasGrant = (
                    ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN))
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL)
                );
                
                if ($hasGrant) {
                    foreach ($this->_fieldGrants as $field => $config) {
                        if (isset($_record->$field) && $_record->$field != $_oldRecord->$field) {
                            $hasGrant &= Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, $config['requiredGrant']);
                        }
                    }
                }

                break;
            case 'delete':
                $hasGrant = (
                    ($_record->account_id == Tinebase_Core::getUser()->getId() && Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_OWN))
                    || Timetracker_Controller_Timeaccount::getInstance()->hasGrant($_record->timeaccount_id, Timetracker_Model_TimeaccountGrants::BOOK_ALL)
                );
                break;
        }
        
        if ($_throw && !$hasGrant) {
            throw new Tinebase_Exception_AccessDenied($_errorMessage);
        }
        
        return $hasGrant;
    }
    
    /**
     * Removes timeaccounts where current user has no access to
     * 
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param string $_action get|update
     */
    public function checkFilterACL(Tinebase_Model_Filter_FilterGroup $_filter, $_action = 'get')
    {
        switch ($_action) {
            case 'get':
                $_filter->setRequiredGrants(array(
                    Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                    Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                    Timetracker_Model_TimeaccountGrants::VIEW_ALL,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'update':
                $_filter->setRequiredGrants(array(
                    Timetracker_Model_TimeaccountGrants::BOOK_OWN,
                    Timetracker_Model_TimeaccountGrants::BOOK_ALL,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            case 'export':
                $_filter->setRequiredGrants(array(
                    Tinebase_Model_Grants::GRANT_EXPORT,
                    Tinebase_Model_Grants::GRANT_ADMIN,
                ));
                break;
            default:
                throw new Timetracker_Exception_UnexpectedValue('Unknown action: ' . $_action);
        }
    }
}
