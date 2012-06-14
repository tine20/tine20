<?php
/**
 * Tine 2.0
 * @package     HumanResources
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *
 * This class handles all Json requests for the HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Frontend
 */
class HumanResources_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var HumanResources_Controller_Employee
     */
    protected $_controller = NULL;
    
    /**
     * user fields (created_by, ...) to resolve in _multipleRecordsToJson and _recordToJson
     *
     * @var array
     */
    protected $_resolveUserFields = array(
        'HumanResources_Model_Employee' => array('created_by', 'last_modified_by', 'account_id', 'supervisor_id')
    );
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'HumanResources';
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchEmployees($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_Employee::getInstance(), 'HumanResources_Model_EmployeeFilter');
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getEmployee($id)
    {
        return $this->_get($id, HumanResources_Controller_Employee::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveEmployee($recordData)
    {
        return $this->_save($recordData, HumanResources_Controller_Employee::getInstance(), 'Employee');
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return string
     */
    public function deleteEmployees($ids)
    {
        return $this->_delete($ids, HumanResources_Controller_Employee::getInstance());
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchWorkingTimes($filter, $paging)
    {
        
        return $this->_search($filter, $paging, HumanResources_Controller_WorkingTime::getInstance(), 'HumanResources_Model_WorkingTimeFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getWorkingTime($id)
    {
        return $this->_get($id, HumanResources_Controller_WorkingTime::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveWorkingTime($recordData)
    {
        return $this->_save($recordData, HumanResources_Controller_WorkingTime::getInstance(), 'WorkingTime');
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return string
     */
    public function deleteWorkingTime($ids)
    {
        return $this->_delete($ids, HumanResources_Controller_WorkingTime::getInstance());
    }


    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchFreeTimes($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_FreeTime::getInstance(), 'HumanResources_Model_FreeTimeFilter');
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getFreeTime($id)
    {
        return $this->_get($id, HumanResources_Controller_FreeTime::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveFreeTime($recordData)
    {    
        return $this->_save($recordData, HumanResources_Controller_FreeTime::getInstance(), 'FreeTime');
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return string
     */
    public function deleteFreeTime($ids)
    {
        return $this->_delete($ids, HumanResources_Controller_FreeTime::getInstance());
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchContracts($filter, $paging)
    {
        return $this->_search($filter, $paging, HumanResources_Controller_Contract::getInstance(), 'HumanResources_Model_ContractFilter');
    }
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        switch (get_class($_record)) {
            case 'HumanResources_Model_Employee':
                $_record['account_id'] = !empty($_record['account_id']) ? Tinebase_User::getInstance()->getFullUserById($_record['account_id'])->toArray() : null;
                $_record['supervisor_id'] = !empty($_record['supervisor_id']) ? Tinebase_User::getInstance()->getFullUserById($_record['supervisor_id'])->toArray() : null;
                if(array_key_exists($_record, 'contracts')) {
                    $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
                    $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record['id'])));
                    $recs = HumanResources_Controller_Contract::getInstance()->search($filter, null);
                    $_record['contracts'] = $this->_multipleRecordsToJson($recs);
                }
                break;
            case 'HumanResources_Model_FreeTime':
                $_record['employee_id'] = !empty($_record['employee_id']) ? HumanResources_Controller_Employee::getInstance()->get($_record['employee_id'])->toArray() : null;
                $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
                $filter->addFilter(new Tinebase_Model_Filter_Text('freetime_id', 'equals', $_record['id']));
                $recs = HumanResources_Controller_FreeDay::getInstance()->search($filter);
                $recs->sort('date', 'ASC');
                $_record['freedays'] = $this->_multipleRecordsToJson($recs);
                break;
            case 'HumanResources_Model_Contract':
                $_record['employee_id'] = !empty($_record['employee_id']) ? HumanResources_Controller_Employee::getInstance()->get($_record['employee_id'])->toArray() : null;
                $_record['workingtime_id'] = HumanResources_Controller_WorkingTime::getInstance()->get($_record['workingtime_id']);
                if(!empty($_record['feast_calendar_id'])) {
                    $_record['feast_calendar_id'] = Tinebase_Container::getInstance()->getContainerById($_record['feast_calendar_id']);
                }
                break;
        }

        return parent::_recordToJson($_record);
    }
// not needed anymore due to task #6600
//     /**
//      * resolves multiple records
//      * @param Tinebase_Record_RecordSet $_records
//      */
//     protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records)
//     {
//         switch ($_records->getRecordClassName()) {
//             case 'HumanResources_Model_FreeTime':
//                 $this->_resolveMultiple($_records, 'employee_id', 'HumanResources_Model_Employee');
//                 break;
//             case 'HumanResources_Model_Contract':
//                 $this->_resolveMultiple($_records, 'employee_id', 'HumanResources_Model_Employee');
//                 $this->_resolveMultiple($_records, 'workingtime_id', 'HumanResources_Model_WorkingTime');
//                 $this->_resolveMultiple($_records, 'feast_calendar_id', 'Calendar_Model_Event');
//                 $this->_resolveMultiple($_records, 'cost_center_id', 'Sales_Model_CostCenter');
//                 break;
//         }
//         return parent::_multipleRecordsToJson($_records);
//     }
    
    /**
     * returns feast days
     * @param string $_employeeId
     * @param DateTime $_firstDayDate
     * @param string $_excludeFreeTimeId
     * @param string $_contractId
     */
    public function getFeastAndFreeDays($_employeeId, $_firstDayDate = NULL, $_excludeFreeTimeId = NULL, $_contractId = NULL)
    {
        if($_contractId) {
            $contract = HumanResources_Controller_Contract::getInstance()->get($_contractId);
        } else {
            $contract = HumanResources_Controller_Contract::getInstance()->getValidContract($_employeeId, $_firstDayDate);
        }
        
        $maxDate = new Tinebase_DateTime();
        $maxDate->addYear(2);
        $minDate = new Tinebase_DateTime();
        $minDate->subYear(1);
        
        $filter = new Calendar_Model_EventFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'container_id', 'operator' => 'equals', 'value' => $contract->feast_calendar_id)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'dtstart', 'operator' => 'before', 'value' => $maxDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'dtstart', 'operator' => 'after', 'value' => $minDate)));
        $dates = Calendar_Controller_Event::getInstance()->search($filter)->dtstart;
        
        $filter = new HumanResources_Model_FreeTimeFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        if ($contract->end_date) {
            $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'firstday_date', 'operator' => 'before', 'value' => $contract->end_date)));
        }

        if($_excludeFreeTimeId) {
            $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'id', 'operator' => 'notin', 'value' => array($_excludeFreeTimeId))));
        }
        $freetimes = HumanResources_Controller_FreeTime::getInstance()->search($filter);
        
        $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Id(array('field' => 'freetime_id', 'operator' => 'in', 'value' => $freetimes->id)));
        $filter->addFilter(new Tinebase_Model_Filter_Int(array('field' => 'duration', 'operator' => 'equals', 'value' => 1)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'date', 'operator' => 'before', 'value' => $maxDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'date', 'operator' => 'after', 'value' => $minDate)));

        $freedays = HumanResources_Controller_FreeDay::getInstance()->search($filter);
        $dates = array_merge($freedays->date, $dates);
        
        $contract->workingtime_id = HumanResources_Controller_WorkingTime::getInstance()->get($contract->workingtime_id);
        
        return array('results' => $dates, 'totalcount' => count($dates), 'contract' => $contract->toArray());
        
    }
}
