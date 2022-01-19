<?php
/**
 * CostCenter controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * CostCenter controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_CostCenter extends Tinebase_Controller_Record_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_CostCenter();
        $this->_modelName = 'HumanResources_Model_CostCenter';
        $this->_purgeRecords = FALSE;
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * check rights
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        if (! $this->_doRightChecks) {
            return;
        }

        if (self::ACTION_GET !== $_action && !$this->checkRight(HumanResources_Acl_Rights::ADMIN, FALSE)) {
            throw new Tinebase_Exception_AccessDenied('You are not allowed to ' . $_action . ' free time type.');
        }
        parent::_checkRight($_action);
    }
    
    /**
     * make id from array
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _flatCostCenter($_record)
    {
        if (is_array($_record->cost_center_id)) {
            $_record->cost_center_id = $_record->cost_center_id['id'];
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
        $this->_flatCostCenter($_record);
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
        $this->_flatCostCenter($_record);
    }
    
    /**
     * returns the current or by date valid costcenter for an employee
     *
     * @param HumanResources_Model_Employee|string $employeeId
     * @param Tinebase_DateTime $date
     * @param boolean $getSalesCostCenter if true, this returns the sales costcenter, not the mm-table like hr costcenter
     * 
     * @return HumanResources_Model_CostCenter|Sales_Model_CostCenter
     */
    public function getValidCostCenter($employeeId, $date = NULL, $getSalesCostCenter = FALSE)
    {
        if (! $employeeId) {
            throw new Tinebase_Exception_InvalidArgument('You have to set an employee at least');
        }
        if (! is_string($employeeId)) {
            $employeeId = $employeeId->getId();
        }
        $date = $date ? new Tinebase_DateTime($date) : new Tinebase_DateTime();
        
        $filter = new HumanResources_Model_CostCenterFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $employeeId)));
        $filter->addFilter(new Tinebase_Model_Filter_Date(array('field' => 'start_date', 'operator' => 'before', 'value' => $date)));
        
        $result = $this->search($filter);
        
        if ($result->count()) {
            $result->sort('start_date', 'ASC');
            $cc = $result->getFirstRecord();
            
            if ($getSalesCostCenter) {
                return Sales_Controller_CostCenter::getInstance()->get($cc->cost_center_id);
            } else {
                return $cc;
            }
            
        } else {
            return NULL;
        }
    }
}
