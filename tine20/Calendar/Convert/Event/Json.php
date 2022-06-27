<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_Json extends Tinebase_Convert_Json
{
    /**
    * converts Tinebase_Record_Interface to external format
    *
    * @param  Tinebase_Record_Interface $_record
    * @return mixed
    */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        self::resolveRelatedData($_record);
        return parent::fromTine20Model($_record);
    }
    
    /**
     * resolve related event data: attendee, rrule and organizer
     * 
     * @param Calendar_Model_Event $_record
     */
    static public function resolveRelatedData($_record)
    {
        if (! $_record instanceof Calendar_Model_Event) {
            return;
        }
        
        Calendar_Model_Attender::resolveAttendee($_record->attendee, TRUE, $_record);
        self::resolveRrule($_record);
        self::resolvePoll($_record);
        self::resolveOrganizer($_record);
        self::resolveLocationRecord($_record);
        self::resolveGrantsOfExternalOrganizers($_record);
    }
    
    /**
    * resolves rrule of given event(s)
    *
    * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
    */
    static public function resolveRrule($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet ? $_events : array($_events);
    
        foreach ($events as $event) {
            if ($event->rrule) {
                $event->rrule = Calendar_Model_Rrule::getRruleFromString($event->rrule);

                if ($event->rrule_constraints instanceof Calendar_Model_EventFilter) {
                    $event->rrule_constraints = $event->rrule_constraints->toArray(true);
                }
            }
        }
    }

    /**
     * resolves poll of given event(s)
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    static public function resolvePoll($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet ?
            $_events :
            new Tinebase_Record_RecordSet(Calendar_Model_Event::class, array($_events));

        $pollIds = array_unique($events->poll_id);

        // read access to event means access to poll
        $protectedUsage = Calendar_Controller_Poll::getInstance()->assertPublicUsage();
        $polls = Calendar_Controller_Poll::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(Calendar_Model_Poll::class,[
                ['field' => 'id', 'operator' => 'in', 'value' => $pollIds],
                ['field' => 'is_deleted', 'operator' => 'equals', 'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET],
            ])
        );
        $protectedUsage();

        foreach ($events as $event) {
            if ($event->poll_id) {
                $event->poll_id = $polls->getById($event->poll_id);
            }
        }
    }
    
    /**
    * resolves organizer of given event
    *
    * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
    */
    static public function resolveOrganizer($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet
            ? $_events : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_events));
        
        self::resolveMultipleIdFields($events, array(
            'Addressbook_Model_Contact' => array(
                'options' => array('ignoreAcl' => TRUE),
                'fields'  => array('organizer'),
            )
        ));
    }

    /**
     * resolves organizer of given event
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    static public function resolveLocationRecord($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet
            ? $_events : new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_events));

        self::resolveMultipleIdFields($events, array(
            'Addressbook_Model_Contact' => array(
                'options' => array('ignoreAcl' => TRUE),
                'fields'  => array('location_record'),
            )
        ));
    }
    
    /**
     * resolves grants of external organizers events
     * NOTE: disable editGrant when organizer is external
     *
     * @param Tinebase_Record_RecordSet|Calendar_Model_Event $_events
     */
    static public function resolveGrantsOfExternalOrganizers($_events)
    {
        $events = $_events instanceof Tinebase_Record_RecordSet || is_array($_events) ? $_events : array($_events);
    
        foreach ($events as &$event) {
            if ($event->organizer && $event->organizer instanceof Tinebase_Record_Interface
                && (!$event->organizer->has('account_id') || !$event->organizer->account_id)
                && $event->{Tinebase_Model_Grants::GRANT_EDIT} 
            ) {
                $event->{Tinebase_Model_Grants::GRANT_EDIT} = FALSE;
                $event->{Tinebase_Model_Grants::GRANT_READ} = TRUE;
            }
        }
    
    }
    
    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param Tinebase_Record_RecordSet         $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination         $_pagination
     *
     * @return mixed
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records = NULL, $_filter = NULL, $_pagination = NULL)
    {
        if (count($_records) == 0) {
            return array();
        }

        if (null !== $_filter) {
            $rruleFilter = $_filter->getFilter('rrule', false, true);
            if ($rruleFilter && in_array($rruleFilter->getOperator(), ['in', 'notin'])) {
                foreach($_records as $record) {
                    $_records->merge(Calendar_Controller_Event::getInstance()->getRecurExceptions($record));
                }
            }
        }

        Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records);
        Tinebase_Tags::getInstance()->getMultipleTagsOfRecords($_records);
        if (Tinebase_Core::isFilesystemAvailable()) {
            Tinebase_FileSystem_RecordAttachments::getInstance()->getMultipleAttachmentsOfRecords($_records);
        }

        Calendar_Model_Attender::resolveAttendee($_records->attendee, TRUE, $_records);
        Calendar_Convert_Event_Json::resolveRrule($_records);
        Calendar_Convert_Event_Json::resolvePoll($_records);
        Calendar_Convert_Event_Json::resolveLocationRecord($_records);
        Calendar_Controller_Event::getInstance()->getAlarms($_records);
        
        Calendar_Convert_Event_Json::resolveGrantsOfExternalOrganizers($_records);
        Calendar_Model_Rrule::mergeAndRemoveNonMatchingRecurrences($_records, $_filter);

        $_records->sortByPagination($_pagination);
        
        Tinebase_Frontend_Json_Abstract::resolveContainersAndTags($_records, null, array('container_id'));

        $_records->setTimezone(Tinebase_Core::getUserTimezone());
        $_records->setConvertDates(true);

        return $_records->toArray();
    }
}
