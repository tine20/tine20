<?php
/**
 * Employee controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Employee();
        $this->_modelName = 'HumanResources_Model_Employee';
        $this->_purgeRecords = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_Employee
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_Employee
     */
    public static function getInstance()
    {
        if (static::$_instance === NULL) {
            static::$_instance = new HumanResources_Controller_Employee();
        }
        
        return static::$_instance;
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
//     protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
//     {
// //         die(var_dump($_record->toArray()));
//     }
        
    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {
        $contracts = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
        $createdContracts = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
        
        foreach($_record->contracts as $contractArray) {
            $contractArray['workingtime_id'] = $contractArray['workingtime_id']['id'];
            $contract = new HumanResources_Model_Contract($contractArray);
            $contract->employee_id = $_createdRecord->getId();
            $contracts->addRecord($contract);
        }
        $contracts->sort('start_date', 'ASC');
        foreach($contracts->getIterator() as $contract) {
            $createdContracts->addRecord(HumanResources_Controller_Contract::getInstance()->create($contract));
        }
        $_createdRecord->contracts = $createdContracts;
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
        $contracts = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
        $ec = HumanResources_Controller_Contract::getInstance();
        
        foreach($_record->contracts as $contractArray) {
            $contractArray['workingtime_id'] = $contractArray['workingtime_id']['id'];
            $contractArray['employee_id'] = $_oldRecord->getId();
            $contract = new HumanResources_Model_Contract($contractArray);
            if($contract->id) {
                $contracts->addRecord($ec->update($contract));
            } else {
                $contracts->addRecord($ec->create($contract));
            }
        }

        $filter = new HumanResources_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text('employee_id', 'equals', $_record['id']));
        $filter->addFilter(new Tinebase_Model_Filter_Id('id', 'notin', $contracts->id));
        $deleteContracts = HumanResources_Controller_Contract::getInstance()->search($filter);
        // update first date
        $contracts->sort('start_date', 'DESC');
        $ec->delete($deleteContracts->id);
        $_record->contracts = $contracts->toArray();
    }
    
//     /**
//      * inspect update of one record (after update)
//      *
//      * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
//      * @param   Tinebase_Record_Interface $_record          the update record
//      * @return  void
//      */
//     protected function _inspectAfterUpdate($_updatedRecord, $_record)
//     {
//         $contracts = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
//         $createdContracts = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
//         $oldContracts  = new Tinebase_Record_RecordSet('HumanResources_Model_Contract');
        
//         foreach($_record->contracts as $contractArray) {
//             $contractArray['workingtime_id'] = $contractArray['workingtime_id']['id'];
//             if(strlen($contractArray['id']) != 40) {
//                 $contract = new HumanResources_Model_Contract($contractArray);
//                 $contract->employee_id = $_updatedRecord->getId();
//                 $contracts->addRecord($contract);
//             } else {
//                 $contract = new HumanResources_Model_Contract($contractArray);
//                 $oldContracts->addRecord($contract);
//             }
//         }
//         $contracts->sort('start_date', 'ASC');
//         foreach($contracts->getIterator() as $contract) {
//             $createdContracts->addRecord(HumanResources_Controller_Contract::getInstance()->create($contract));
//         }
//         $createdContracts->merge($oldContracts);
//         $createdContracts->sort('start_date', 'DESC');
        
//         $_updatedRecord->contracts = $createdContracts;
//     }
    
}
