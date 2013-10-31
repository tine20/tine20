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

           $freeday = new HumanResources_Model_FreeDay($freedayArray);
           
           if ($freeday->id) {
               $freeDays->addRecord($fc->update($freeday));
           } else {
               $freeDays->addRecord($fc->create($freeday));
           }
       }

       $filter = new HumanResources_Model_FreeDayFilter(array(), 'AND');
       $filter->addFilter(new Tinebase_Model_Filter_Text('freetime_id', 'equals', $_record->getId()));
       $filter->addFilter(new Tinebase_Model_Filter_Id('id', 'notin', $freeDays->id));
       $deleteFreedays = HumanResources_Controller_FreeDay::getInstance()->search($filter);
       
       // update first and last date
       $freeDays->sort('date', 'ASC');
       $_record->firstday_date = $freeDays->getFirstRecord()->date;
       $freeDays->sort('date', 'DESC');
       $_record->lastday_date = $freeDays->getFirstRecord()->date;
       $_record->days_count = $freeDays->count();
       $fc->delete($deleteFreedays->id);
       $_record->freedays = $freeDays->toArray();
       
       if ($_record->type == 'sickness') {
           $this->_handleOverwrittenVacation($_record);
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
           // normalize first-,  last date and days_count
           $this->_freedaysToCreate->sort('date', 'ASC');
           $_record->firstday_date = $this->_freedaysToCreate->getFirstRecord()->date;
           $this->_freedaysToCreate->sort('date', 'DESC');
           $_record->lastday_date = $this->_freedaysToCreate->getFirstRecord()->date;
           $_record->days_count = $this->_freedaysToCreate->count();
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
       
       if ($_record->type == 'sickness') {
           $this->_handleOverwrittenVacation($_createdRecord);
       }
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
   
   /**
    * finds overwritten by sickness days overwritten vacation days. 
    * deletes the overwritten vacation day and the vacation itself if days_count = 0
    *
    * @param Tinebase_Record_Interface $_record
    */
   protected function _handleOverwrittenVacation($_record) {
       
       $fdController = HumanResources_Controller_FreeDay::getInstance();
       
       $changedFreeTimes = array();
       
       foreach($_record->freedays as $freeday) {
           
           $vacationTimeFilter = new HumanResources_Model_FreeTimeFilter(array(
               array('field' => 'type', 'operator' => 'equals', 'value' => 'vacation')
           ));
           $vacationTimeFilter->addFilter(new Tinebase_Model_Filter_Text(
               array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id)
           ));
           
           $vacationTimes = $this->search($vacationTimeFilter);
           
           $filter = new HumanResources_Model_FreeDayFilter(array(
               array('field' => 'date', 'operator' => 'equals', 'value' => $freeday['date'])
           ));
           
           $filter->addFilter(new Tinebase_Model_Filter_Text(
               array('field' => 'freetime_id', 'operator' => 'not', 'value' => $_record->getId())
               ));
           $filter->addFilter(new Tinebase_Model_Filter_Text(
               array('field' => 'freetime_id', 'operator' => 'in', 'value' => $vacationTimes->id)
           ));
           
           $vacationDay = $fdController->search($filter)->getFirstRecord();
           
           if ($vacationDay) {
               $fdController->delete($vacationDay->getId());
               
               $freeTime = $this->get($vacationDay->freetime_id);
               
               if (! isset($changedFreeTimes[$vacationDay->freetime_id])) {
                   $changedFreeTimes[$vacationDay->freetime_id] = $freeTime;
               }
               
               $count = (int) $changedFreeTimes[$vacationDay->freetime_id]->days_count - 1;
               $changedFreeTimes[$vacationDay->freetime_id]->days_count = $count;
           }
       }
       
       foreach($changedFreeTimes as $freeTimeId => $freetime) {
           if ($freetime->days_count == 0) {
               $this->delete($freetime->getId());
           } else {
               $freeTime->days_count = $count;
               $this->update($freetime);
           }
       }
   }
}
