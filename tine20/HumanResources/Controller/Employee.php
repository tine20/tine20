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
     * @var Tinebase_Record_RecordSet
     */
    protected $_elayersToCreate = NULL;
    
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_elayersToUpdate = NULL;
    
    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_elayersToDelete = NULL;
    /**
     * 
     * @var HumanResources_Controller_Elayer
     */
    protected $elayerController = NULL;
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
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        die(var_dump($_record->toArray()));
    }
        
    /**
     * inspect creation of one record (after create)
     *
     * @param   Tinebase_Record_Interface $_createdRecord
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectAfterCreate($_createdRecord, Tinebase_Record_Interface $_record)
    {

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
        $this->_elayerController = HumanResources_Controller_Elayer::getInstance();
        $this->_elayersToUpdate= $_oldRecord->elayers;
        
        $filter = new HumanResources_Model_ElayerFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_ForeignId($array()));
        $this->_elayersToDelete = $this->_elayerController->search(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->getId()));
        
        
        die(var_dump($this->_elayersToDelete->toArray()));
        
//         foreach($this->_elayersToDelete as $elayer) {
//             $this->_elayersToUpdate->
        
        foreach($_record->elayers as $elayer) {
            if((!array_key_exists($elayer, 'id')) || (! $elayer['id'])) {
                $this->_elayersToCreate[] = $elayer;
            } else {
                
            }
            
        }
        $_record->elayers = null;
    }
    
    /**
     * inspect update of one record (after update)
     *
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $_record          the update record
     * @return  void
     */
    protected function _inspectAfterUpdate($_updatedRecord, $_record)
    {
        if($this->_elayersToUpdate) {
//             foreach($this->_elayersToUpdate->getIterator() as $_elayer)
        }
        
        if($this->_elayersToCreate) {
            foreach($this->_elayersToCreate as $elayerArray) {
                $elayerArray['employee_id'] = $_record->getId();
                $elayer = new HumanResources_Model_Elayer($elayerArray);
                $this->_elayerController->create($_record, false);
            }
        }
    }
    
}
