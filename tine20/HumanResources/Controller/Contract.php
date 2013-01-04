<?php
/**
 * Contract controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Contract controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Contract extends Tinebase_Controller_Record_Abstract
{
    /**
     * true if sales is installed
     * 
     * @var boolean
     */
    protected $_useSales = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Contract();
        $this->_modelName = 'HumanResources_Model_Contract';
        $this->_purgeRecords = FALSE;
        $this->_useSales = Tinebase_Application::getInstance()->isInstalled('Sales', TRUE);
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_Contract
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_Contract
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Controller_Contract();
        }

        return self::$_instance;
    }

    protected function _setNotes($_updatedRecord, $_record, $_systemNoteType = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, $_currentMods = NULL) {
    }

    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_checkDates($_record);
        $this->_containerToId($_record);
    }

    /**
     * checks the start_date and end_date
     * @param Tinebase_Record_Interface $_record
     */
    protected function _checkDates(Tinebase_Record_Interface $_record)
    {
        if ($_record->start_date instanceof Tinebase_DateTime && $_record->end_date instanceof Tinebase_DateTime && (int) $_record->end_date->getTimestamp() != 0) {
            if ($_record->end_date->isEarlier($_record->start_date)) {
                throw new Tinebase_Exception_Record_Validation('The start date of the contract must be before the end date');
            }
        }
    }
    /**
     * resolves the container array to the corresponding id
     * @param Tinebase_Record_Interface $_record
     */
    protected function _containerToId(Tinebase_Record_Interface $_record) {
        if (is_array($_record->feast_calendar_id)) {
            $_record->feast_calendar_id = $_record->feast_calendar_id['id'];
        }
        if (is_array($_record->workingtime_id)) {
            $_record->workingtime_id = $_record->workingtime_id['id'];
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
        $this->_checkDates($_record);
        $this->_containerToId($_record);

        if (empty($_record->feast_calendar_id)) $_record->feast_calendar_id = null;

        $paging = new Tinebase_Model_Pagination(array('sort' => 'start_date', 'dir' => 'DESC', 'limit' => 1, 'start' => 0));
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id)));
        $lastRecord = $this->search($filter, $paging)->getFirstRecord();
        if ($lastRecord && empty($lastRecord->end_date) && $_record->start_date) {
            $date = clone $_record->start_date;
            $lastRecord->end_date = $date->subDay(1);
            $this->update($lastRecord, false);
        }
    }
    /**
     * returns the active contract for the given employee and date or now, when no date is given
     * @param string $_employeeId
     * @param Tinebase_DateTime $_firstDayDate
     * @throws Tinebase_Exception_InvalidArgument
     * @throws HumanResources_Exception_NoCurrentContract
     * @throws Tinebase_Exception_Duplicate
     */
    public function getValidContract($_employeeId, $_firstDayDate = NULL)
    {
        if (!$_employeeId) {
            throw new Tinebase_Exception_InvalidArgument('You have to set an account id at least');
        }
        $_firstDayDate = $_firstDayDate ? new Tinebase_DateTime($_firstDayDate) : new Tinebase_DateTime();
        
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $_firstDayDate)));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
        $endDate = new Tinebase_Model_Filter_FilterGroup(array(), 'OR');
        $endDate->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'end_date', 'operator' => 'after', 'value' =>  $_firstDayDate)));
        $filter->addFilterGroup($endDate);
        
        $contracts = $this->search($filter);
        
        if ($contracts->count() < 1) {
            $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
            $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_employeeId)));
            $contracts = $this->search($filter);
            if ($contracts->count() > 0) {
                $e = new HumanResources_Exception_NoCurrentContract();
                $e->addRecord($contracts->getFirstRecord());
                throw $e;
            } else {
                throw new HumanResources_Exception_NoContract();
            }
        } else if ($contracts->count() > 1) {
            throw new Tinebase_Exception_Duplicate('There are more than one valid contracts for this employee!');
        }

        return $contracts->getFirstRecord();
    }
    
    public function getContractsByEmployeeId($employeeId)
    {
        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $pagination = new Tinebase_Model_Pagination(array('sort' => 'start_date'));
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)));
        $recs = $this->search($filter, $pagination);
        
        return $recs;
    }
}
