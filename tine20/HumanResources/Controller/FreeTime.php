<?php
/**
 * FreeTime controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $freeDays = new Tinebase_Record_RecordSet('HumanResources_Model_FreeDay');
        $fc = HumanResources_Controller_FreeDay::getInstance();

        foreach($_record->freedays as $freedayArray) {
            $freeday = new HumanResources_Model_FreeDay($freedayArray);
            if($freeday->id) {
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

        if($_record->freedays && !empty($_record->freedays)) {
            foreach($_record->freedays as $freedayArray) {
                $this->_freedaysToCreate->addRecord(new HumanResources_Model_FreeDay($freedayArray));
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
        foreach($this->_freedaysToCreate as $freeDay) {
            $freeDay->freetime_id = $_createdRecord->getId();
            HumanResources_Controller_FreeDay::getInstance()->create($freeDay);
        }
        $_createdRecord->freedays = $this->_freedaysToCreate;
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
    
    protected function _setNotes($_updatedRecord, $_record, $_systemNoteType = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, $_currentMods = NULL) {
    }


}
