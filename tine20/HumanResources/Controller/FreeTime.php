<?php
/**
 * FreeTime controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * FreeTime controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_FreeTime extends Tinebase_Controller_Record_Abstract
{
    /**
     * record set of freedays to create on create/update
     * @var Tinebase_Record_RecordSet
     */
    protected $_freedaysToCreate = NULL;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_FreeTime();
        $this->_modelName = 'HumanResources_Model_FreeTime';
        $this->_purgeRecords = TRUE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_FreeTime
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_FreeTime
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Controller_FreeTime();
        }

        return self::$_instance;
    }

   protected function _inspectBeforeUpdate($_record, $_oldRecord)
   {
       // if empty, no changes have been made
       // @todo: lookforward: parts - parameter
       if (empty($_record->freedays)) {
           return;
       }
       
       $freeDays = new Tinebase_Record_RecordSet('HumanResources_Model_FreeDay');
       $fc = HumanResources_Controller_FreeDay::getInstance();
       $freetimeId = $_record->getId();
       
       foreach($_record->freedays as $freedayArray) {
           $freedayArray['freetime_id'] = $freetimeId;

           if (is_array($freedayArray['account_id'])) {
               $freedayArray['account_id'] = $freedayArray['account_id']['id'];
           }
           
           $freeday = new HumanResources_Model_FreeDay($freedayArray);
           
           if ($freeday->id) {
               $freeDays->addRecord($fc->update($freeday));
           } else {
               $freeDays->addRecord($fc->create($freeday));
           }
       }

       $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
       $filter->addFilter(new Tinebase_Model_Filter_Text('freetime_id', 'equals', $_record['id']));
       $filter->addFilter(new Tinebase_Model_Filter_Id('id', 'notin', $freeDays->id));
       $deleteFreedays = HumanResources_Controller_FreeDay::getInstance()->search($filter);
       // update first date
       $freeDays->sort('date', 'ASC');
       $_record->firstday_date = $freeDays->getFirstRecord()->date;

       $fc->delete($deleteFreedays->id);
       $_record->freedays = $freeDays->toArray();
   }
   
   /**
    * inspect creation of one record (before create)
    *
    * @param   Tinebase_Record_Interface $_record
    * @return  void
    */
   protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
   {
       $this->_freedaysToCreate = new Tinebase_Record_RecordSet('HumanResources_Model_FreeDay');
       if (is_array($_record->employee_id)) {
           $_record->employee_id = $_record->employee_id['id'];
       }
       
       if ($_record->freedays && ! empty($_record->freedays)) {
           foreach($_record->freedays as $fd) {
               if (! ($fd instanceof HumanResources_Model_FreeDay)) {
                   $fd = new HumanResources_Model_FreeDay($fd);
               }
               $this->_freedaysToCreate->addRecord($fd);
           }
           $this->_freedaysToCreate->sort('date', 'ASC');
           $_record->firstday_date = $this->_freedaysToCreate->getFirstRecord()->date;
       } else {
           $_record->firstday_date = NULL;
       }
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
       $c = HumanResources_Controller_FreeDay::getInstance();
       $this->_freedaysToCreate->freetime_id = $_createdRecord->getId();
       
       $fd = array();
       foreach($this->_freedaysToCreate as $freeDay) {
           $r = $c->create($freeDay);
           $fd[] = $r->toArray();
       }
       $_createdRecord->freedays = $fd;
   }

   /**
    * delete linked objects (notes, relations, ...) of record
    *
    * @param Tinebase_Record_Interface $_record
    */
   protected function _deleteLinkedObjects(Tinebase_Record_Interface $_record)
   {
       $filter = new HumanResources_Model_FreeDayFilter(array(
           ), 'AND');
       $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'freetime_id', 'operator' => 'equals', 'value' => $_record->getId())));
       
       HumanResources_Controller_FreeDay::getInstance()->deleteByFilter($filter);
       parent::_deleteLinkedObjects($_record);
   }
}
