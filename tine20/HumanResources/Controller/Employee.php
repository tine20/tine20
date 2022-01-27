<?php
/**
 * Employee controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Employee controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Employee extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;
    use HumanResources_Controller_CheckFilterACLEmployeeTrait;

    /**
     * duplicate check fields / if this is NULL -> no duplicate check
     *
     * @var array
     */
    protected $_duplicateCheckFields = array(array('account_id'), array('number'));
    protected $_resolveCustomFields = TRUE;

    protected $_getMultipleGrant = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA, HumanResources_Model_DivisionGrants::READ_BASIC_EMPLOYEE_DATA];
    protected $_requiredFilterACLget = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA, HumanResources_Model_DivisionGrants::READ_BASIC_EMPLOYEE_DATA];
    protected $_requiredFilterACLupdate  = [HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA];
    protected $_requiredFilterACLsync  = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA, HumanResources_Model_DivisionGrants::READ_BASIC_EMPLOYEE_DATA];
    protected $_requiredFilterACLexport  = [HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA, HumanResources_Model_DivisionGrants::READ_BASIC_EMPLOYEE_DATA];
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Employee();
        $this->_modelName = 'HumanResources_Model_Employee';
        $this->_purgeRecords = false;
        $this->_doContainerACLChecks = true;
        $this->_traitDelegateAclField = 'division_id';
    }

    protected function _getCheckFilterACLTraitFilter()
    {
        return new Tinebase_Model_Filter_Text('account_id', 'equals', Tinebase_Core::getUser()->getId());
    }

    protected function _checkGrant($_record, $_action, $_throw = TRUE, $_errorMessage = 'No Permission.', $_oldRecord = NULL)
    {
        if (!$this->_doContainerACLChecks) {
            return true;
        }

        // if we have manage_employee right, we have all grants
        if (Tinebase_Core::getUser()->hasRight(HumanResources_Config::APP_NAME, HumanResources_Acl_Rights::MANAGE_EMPLOYEE)) {
            return true;
        }

        switch ($_action) {
            case self::ACTION_GET:
                if ($_record->getIdFromProperty('account_id') === Tinebase_Core::getUser()->getId()) {
                    if (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::READ_OWN_DATA, false, $_errorMessage, $_oldRecord)) {
                        return true;
                    }
                }
                if (parent::_checkGrant($_record, HumanResources_Model_DivisionGrants::READ_EMPLOYEE_DATA, false, $_errorMessage, $_oldRecord)) {
                    return true;
                }
                $_action = HumanResources_Model_DivisionGrants::READ_BASIC_EMPLOYEE_DATA;
                break;
            case HumanResources_Model_DivisionGrants::READ_OWN_DATA:
                if ($_record->getIdFromProperty('account_id') !== Tinebase_Core::getUser()->getId()) {
                    if ($_throw) {
                        throw new Tinebase_Exception_AccessDenied($_errorMessage . ' read own data');
                    }
                    return false;
                }
                break;
            case self::ACTION_CREATE:
            case self::ACTION_UPDATE:
            case self::ACTION_DELETE:
                $_action = HumanResources_Model_DivisionGrants::UPDATE_EMPLOYEE_DATA;
                break;
        }
        return parent::_checkGrant($_record, $_action, $_throw, $_errorMessage, $_oldRecord);
    }

    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     * 
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $firstDate = $_createdRecord->employment_begin instanceof Tinebase_DateTime ? $_createdRecord->employment_begin : Tinebase_DateTime::now();
        $lastDate = $_createdRecord->employment_end instanceof Tinebase_DateTime ? $_createdRecord->employment_end : Tinebase_DateTime::now()->addYear(1);
        
        $firstYear = (int) $firstDate->format('Y');
        $lastYear  = (int) $lastDate->format('Y');
        $accountController = HumanResources_Controller_Account::getInstance();
        
        while ($firstYear <= $lastYear) {
            $account = new HumanResources_Model_Account(array(
                'employee_id' => $_createdRecord->getId(),
                'year'        => $firstYear
            ));
            $accountController->create($account);
            $firstYear++;
        }
    }
    
    /**
     * transforms arrays to their ids
     * 
     * @param unknown $_record
     */
    protected function _recordArraysToId($_record)
    {
        if (is_array($_record->account_id)) {
            $_record->account_id = $_record->account_id['accountId'];
        }
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_checkContractsOverlap($_record);
        $this->_recordArraysToId($_record);
    }

    /**
     * checks on save or update if contracts overlap
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _checkContractsOverlap($record)
    {
        if ($record->contracts) {
            // get intervals
            $intervals = array();
            foreach ($record->contracts as $contract) {
                if (is_array($contract)) {
                    $contract['employee_id'] = $record->getId();
                    $contract = new HumanResources_Model_Contract($contract);
                }
                if ($contract->start_date) {
                    $intervals[] = array(
                        'start' => $contract->start_date,
                        'stop' => $contract->end_date ? $contract->end_date : NULL,
                        'record' => $contract
                    );
                }
            }
            
            if (! count($intervals)) {
                return;
            }
            
            // sort by start date
            uasort($intervals, function($a, $b) {
                // throw exception here if start dates are the same
                if ($a['start'] == $b['start']) {
                    throw new HumanResources_Exception_ContractOverlap();
                }
                return ($a['start'] < $b['start']) ? -1 : 1;
            });
            
            // compare them sorted
            $lastInterval = array_shift($intervals);
            foreach ($intervals as $interval) {
                // check overlapping
                if ($interval['stop'] == NULL ? FALSE
                    : ($lastInterval['stop'] == $interval['stop'] ? TRUE
                    : ($lastInterval['stop'] > $interval['start'] ? TRUE
                    : FALSE))) {
                        throw new HumanResources_Exception_ContractOverlap();
                }
                $lastInterval = $interval;
            }
        }
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * 
     * @todo $_record->contracts should be a Tinebase_Record_RecordSet
     * @todo use getMigration()
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_duplicateCheck($_record);
        $this->_checkContractsOverlap($_record);
        $this->_recordArraysToId($_record);
    }
    
    /**
     * delete linked objects (notes, relations, ...) of record
     *
     * @param Tinebase_Record_Interface $_record
     */
    protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
    {
        // use textfilter for employee_id 
        $eFilter = new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->getId()));
        
        // delete accounts
        $filter = new HumanResources_Model_AccountFilter(array());
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->getId())));
        HumanResources_Controller_Account::getInstance()->deleteByFilter($filter);
        
        parent::_deleteLinkedObjects($_record);
    }

    /**
     * returns the highest employee number of all employees
     * 
     * @return integer
     */
    public function getLastEmployeeNumber()
    {
        $filter = new HumanResources_Model_EmployeeFilter();
        $pagination = new Tinebase_Model_Pagination(array("sort" => "number","dir" => "DESC"));
        
        if ($employee = $this->search($filter, $pagination)->getFirstRecord()) {
            return (int) $employee->number;
        } else {
            return 0;
        }
    }
    
    /**
     * returns employees currently belonging to the given cost center (id)
     * 
     * @param string|Sales_Model_CostCenter $costCenterId
     * @return Tinebase_Record_RecordSet|NULL
     */
    public function getEmployeesBySalesCostCenter($costCenterId) {
        $costCenterId = is_string($costCenterId) ? $costCenterId : $costCenterId->getId();

        $ccController = HumanResources_Controller_CostCenter::getInstance();
        $now = Tinebase_DateTime::now();
        
        $filter = new HumanResources_Model_CostCenterFilter();
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'cost_center_id', 'operator' => 'equals', 'value' => $costCenterId)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $now)));
        
        $hrccs = $ccController->search($filter);
        
        if ($hrccs->count()) {
            $filter = new HumanResources_Model_EmployeeFilter(array(
                array('field' => 'id', 'operator' => 'in', 'value' => $hrccs->employee_id)
            ), 'AND');
            
            $filteredEmployees = new Tinebase_Record_RecordSet('HumanResources_Model_Employee');
            
            $employees = $this->search($filter);
            
            foreach($employees as $employee) {
                $subCC = $hrccs->filter('employee_id', $employee->getId());
                if ($subCC->sort('start_date', 'DESC')->getFirstRecord()->start_date <= $now) {
                    $filteredEmployees->addRecord($employee);
                }
            }
            
            return $filteredEmployees;
            
        } else {
            return NULL;
        }
    }
    
    /**
     * transfers user accounts to employee records
     * 
     * @param boolean $_deletePrivateInfo should private information be removed from contacts
     */
    public function transferUserAccounts($_deletePrivateInfo = FALSE, $_feastCalendarId = NULL, $_workingTimeModelId = NULL, $_vacationDays = NULL, $cliCall = FALSE)
    {
        $lastNumber = $this->getLastEmployeeNumber();
        
        // get all active accounts
        $filter = new Addressbook_Model_ContactFilter(array(
                array('field' => 'type', 'operator' => 'equals', 'value' => 'user'),
                array('field' => 'showDisabled', 'operator' => 'equals', 'value' => 1),
            )
        );
        
        $accounts = Addressbook_Controller_Contact::getInstance()->search($filter);
        $nextNumber = $lastNumber + 1;
        
        $countCreated = 0;
        
        foreach ($accounts as $account) {
            $filter = new HumanResources_Model_EmployeeFilter(array(array(
                'field' => 'account_id', 'operator' => 'equals', 'value' => $account->account_id
            )), 'AND');
            
            // if not already exists
            if (($lastNumber == 0) || ($this->search($filter)->count() === 0)) {
                
                $employee = new HumanResources_Model_Employee(array(
                    'number'              => $nextNumber,
                    'account_id'          => $account->account_id,
                    'countryname'         => $account->adr_two_countryname,
                    'locality'            => $account->adr_two_locality,
                    'postalcode'          => $account->adr_two_postalcode,
                    'region'              => $account->adr_two_region,
                    'street'              => $account->adr_two_street,
                    'street2'             => $account->adr_two_street2,
                    'email'               => $account->email_home,
                    'tel_home'            => $account->tel_home,
                    'tel_cell'            => $account->tel_cell_private,
                    'n_given'             => $account->n_given,
                    'n_family'            => $account->n_family,
                    'n_fn'                => $account->n_fn,
                    'bday'                => $account->bday,
                    'bank_account_holder' => $account->n_fn,
                    'employment_begin'    => Tinebase_DateTime::now()->subYear(1)
                ));
                
                if ($_feastCalendarId && $_workingTimeModelId && $_vacationDays) {
                    $contract = $this->createContractDataForEmployee(array(
                        'feastCalendarId' => $_feastCalendarId,
                        'workingTimeModelId' => $_workingTimeModelId,
                        'vacationDays' => $_vacationDays,
                    ), $cliCall);
                    $employee->contracts = array($contract);
                }
                
                $countCreated++;
                
                if ($cliCall) {
                    echo 'Creating Employee "'. $account->n_fn . '"' . chr(10);
                }
                
                $this->create($employee);
                $nextNumber++;
                
            } else {
                if ($cliCall) {
                    echo 'Employee "'. $account->n_fn . '" already exists. Skipping...' . chr(10);
                }
            }
            
            if ($_deletePrivateInfo) {
                $account->adr_two_countryname = NULL;
                $account->adr_two_locality = NULL;
                $account->adr_two_postalcode = NULL;
                $account->adr_two_region = NULL;
                $account->adr_two_street = NULL;
                $account->adr_two_street2 = NULL;
                $account->email_home = NULL;
                $account->tel_home = NULL;
                $account->tel_cell_private = NULL;
                // do not remove contact image
                $account->jpegphoto = NULL;
                
                if ($cliCall) {
                    echo 'Removing private information of employee "'. $account->n_fn .'"' . chr(10);
                }
                Addressbook_Controller_Contact::getInstance()->update($account);
            }
        }
        if ($cliCall) {
            echo 'Created ' . $countCreated . ' employees.' . chr(10);
            echo 'Transfer OK' . chr(10);
        }
    }
    
    /**
     * create contract data
     * 
     * @param array $contractData
     * @param boolean $cliCall
     * @return array
     */
    public function createContractDataForEmployee($contractData = array(), $cliCall = FALSE)
    {
        if (isset($contractData['feastCalendarId'])) {
            try {
                $feastCalendar = Tinebase_Container::getInstance()->get($contractData['feastCalendarId']);
            } catch (Exception $e) {
                if ($cliCall) {
                    die('The Calendar with the id ' . $contractData['feastCalendarId'] . ' could not be found!' . chr(10));
                } else {
                    throw $e;
                }
            }
            if ($cliCall) {
                echo 'Found Calendar ' . $feastCalendar->name . chr(10);
            }
        } else {
            $feastCalendar = NULL;
        }
        
        if (isset($contractData['workingTimeModelId'])) {
            try {
                $workingTimeModel = HumanResources_Controller_WorkingTimeScheme::getInstance()->get($contractData['workingTimeModelId']);
            } catch (Exception $e) {
                if ($cliCall) {
                    die('The Working Time Schema with the id ' . $contractData['workingTimeModelId'] . ' could not be found!' . chr(10));
                } else {
                    throw $e;
                }
            }
            if ($cliCall) {
                echo 'Found Working Time Schema "' . $workingTimeModel->title . '"' . chr(10);
            }
        } else {
            $workingTimeModel = NULL;
        }
        
        return array(
            'feast_calendar_id'  => $feastCalendar ? $feastCalendar->toArray() : NULL,
            'vacation_days'      => isset($contractData['vacationDays']) ? $contractData['vacationDays'] : NULL,
            'workingtime_json'   => $workingTimeModel ? $workingTimeModel->json : '',
            'working_time_scheme'=> $workingTimeModel->getId(),
            'start_date'         => $contractData['startDate']
        );
    }
}
