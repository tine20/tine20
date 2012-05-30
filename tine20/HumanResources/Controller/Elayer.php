<?php
/**
 * Elayer controller for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Elayer controller class for HumanResources application
 *
 * @package     HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller_Elayer extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_applicationName = 'HumanResources';
        $this->_backend = new HumanResources_Backend_Elayer();
        $this->_modelName = 'HumanResources_Model_Elayer';
        $this->_purgeRecords = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }

    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller_Elayer
     */
    private static $_instance = NULL;

    /**
     * the singleton pattern
     *
     * @return HumanResources_Controller_Elayer
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Controller_Elayer();
        }

        return self::$_instance;
    }

    protected function _setNotes($_updatedRecord, $_record, $_systemNoteType = Tinebase_Model_Note::SYSTEM_NOTE_NAME_CREATED, $_currentMods = NULL) {
    }

    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        if(empty($_record->feast_calendar_id)) $_record->feast_calendar_id = null; 
        $paging = new Tinebase_Model_Pagination(array('sort' => 'start_date', 'dir' => 'DESC', 'limit' => 1, 'start' => 0));
        $filter = new HumanResources_Model_ElayerFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text(array('field' => 'employee_id', 'operator' => 'equals', 'value' => $_record->employee_id)));
        $lastRecord = $this->search($filter, $paging)->getFirstRecord();
        if($lastRecord && empty($lastRecord->end_date)) {
            $date = clone $_record->start_date;
            $lastRecord->end_date = $date->subDay(1);
            $this->update($lastRecord, false);
        }
    }
}
