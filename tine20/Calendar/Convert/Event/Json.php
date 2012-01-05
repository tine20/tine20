<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    * converts Tinebase_Record_Abstract to external format
    *
    * @param  Tinebase_Record_Abstract $_record
    * @return mixed
    */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
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
        
        Calendar_Model_Attender::resolveAttendee($_record->attendee);
        self::resolveRrule($_record);
        self::resolveOrganizer($_record);
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
        $events = $_events instanceof Tinebase_Record_RecordSet ? $_events : array($_events);
    
        $organizerIds = array();
        foreach ($events as $event) {
            if ($event->organizer) {
                $organizerIds[] = $event->organizer;
            }
        }
    
        $organizers = Addressbook_Controller_Contact::getInstance()->getMultiple(array_unique($organizerIds), TRUE);
    
        foreach ($events as $event) {
            if ($event->organizer && is_scalar($event->organizer)) {
                $idx = $organizers->getIndexById($event->organizer);
                if ($idx !== FALSE) {
                    $event->organizer = $organizers[$idx];
                }
            }
        }
    }
}
