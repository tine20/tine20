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
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Controller_Employee();
        }
        
        return self::$_instance;
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
        $elayers = new Tinebase_Record_RecordSet('HumanResources_Model_Elayer');
        $createdElayers = new Tinebase_Record_RecordSet('HumanResources_Model_Elayer');
        
        foreach($_record->elayers as $elayerArray) {
            $elayerArray['workingtime_id'] = $elayerArray['workingtime_id']['id'];
            $elayer = new HumanResources_Model_Elayer($elayerArray);
            $elayer->employee_id = $_createdRecord->getId();
            $elayers->addRecord($elayer);
        }
        $elayers->sort('start_date', 'ASC');
        foreach($elayers->getIterator() as $elayer) {
            $createdElayers->addRecord(HumanResources_Controller_Elayer::getInstance()->create($elayer));
        }
        $_createdRecord->elayers = $createdElayers;
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
        $elayers = new Tinebase_Record_RecordSet('HumanResources_Model_Elayer');
        $ec = HumanResources_Controller_Elayer::getInstance();
        
        foreach($_record->elayers as $elayerArray) {
            $elayerArray['workingtime_id'] = $elayerArray['workingtime_id']['id'];
            $elayer = new HumanResources_Model_Elayer($elayerArray);
            if($elayer->id) {
                $elayers->addRecord($ec->update($elayer));
            } else {
                $elayers->addRecord($ec->create($elayer));
            }
        }

        $filter = new HumanResources_Model_ElayerFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text('employee_id', 'equals', $_record['id']));
        $filter->addFilter(new Tinebase_Model_Filter_Id('id', 'notin', $elayers->id));
        $deleteElayers = HumanResources_Controller_Elayer::getInstance()->search($filter);
        // update first date
        $elayers->sort('start_date', 'DESC');
        $ec->delete($deleteElayers->id);
        $_record->elayers = $elayers->toArray();
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
//         $elayers = new Tinebase_Record_RecordSet('HumanResources_Model_Elayer');
//         $createdElayers = new Tinebase_Record_RecordSet('HumanResources_Model_Elayer');
//         $oldElayers  = new Tinebase_Record_RecordSet('HumanResources_Model_Elayer');
        
//         foreach($_record->elayers as $elayerArray) {
//             $elayerArray['workingtime_id'] = $elayerArray['workingtime_id']['id'];
//             if(strlen($elayerArray['id']) != 40) {
//                 $elayer = new HumanResources_Model_Elayer($elayerArray);
//                 $elayer->employee_id = $_updatedRecord->getId();
//                 $elayers->addRecord($elayer);
//             } else {
//                 $elayer = new HumanResources_Model_Elayer($elayerArray);
//                 $oldElayers->addRecord($elayer);
//             }
//         }
//         $elayers->sort('start_date', 'ASC');
//         foreach($elayers->getIterator() as $elayer) {
//             $createdElayers->addRecord(HumanResources_Controller_Elayer::getInstance()->create($elayer));
//         }
//         $createdElayers->merge($oldElayers);
//         $createdElayers->sort('start_date', 'DESC');
        
//         $_updatedRecord->elayers = $createdElayers;
//     }
    
}
