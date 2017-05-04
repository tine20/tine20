<?php
/**
 * calendar export generic trait
 *
 * @package     Calendar
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2014-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * generic calendar export trait
 *
 * @package     Calendar
 * @subpackage  Export
 *
 * @property Tinebase_Controller_Record_Abstract    $_controller
 * @property Tinebase_Model_Filter_FilterGroup      $_filter
 * @property boolen                                 $_getRelations
 *
 * TODO rename trait to hint functionality? GenericTrait is not a good name ;)
 */
trait Calendar_Export_GenericTrait
{
    /**
     * @var Tinebase_DateTime
     */
    protected $_from = NULL;

    /**
     * @var Tinebase_DateTime
     */
    protected $_until = NULL;

    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function init(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        $periodFilter = $_filter->getFilter('period', false, true);

        if ($periodFilter) {
            $this->_from = $periodFilter->getFrom();
            $this->_until = $periodFilter->getUntil();
        }

        parent::__construct($_filter, $_controller, $_additionalOptions);
    }

    /**
     * export records
     */
    protected function _exportRecords()
    {
        // to support rrule & sorting we can't do pagination in calendar exports
        $records = $this->_controller->search($this->_filter, NULL, $this->_getRelations, false, 'export');
        Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($records, $this->_filter);

        $this->_sortRecords($records);

        $this->_resolveRecords($records);
        foreach($records as $idx => $record) {
            $this->processRecord($record, $idx);
        }

        $result = array();

        $this->_onAfterExportRecords($result);
    }

    protected function _sortRecords($records)
    {
        $records->sort(function($r1, $r2) {
            return $r1->dtstart > $r2->dtstart;
        });
    }

    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        parent::_resolveRecords($_records);

        Calendar_Model_Attender::resolveAttendee($_records->attendee, false, $_records);

        foreach($_records as $record) {
            $attendee = $record->attendee->getName();
            $record->attendee = implode('& ', $attendee);

            $organizer = $record->resolveOrganizer();
            if ($organizer) {
                $record->organizer = $organizer->n_fileas;
            }
        }
    }
}
