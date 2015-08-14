<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Mac OS X VCALENDAR to Tine 2.0 Calendar_Model_Event and back again
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_MacOSX extends Calendar_Convert_Event_VCalendar_Abstract
{
    // DAVKit/4.0.3 (732.2); CalendarStore/4.0.4 (997.7); iCal/4.0.4 (1395.7); Mac OS X/10.6.8 (10K549)
    // CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)
    // Mac OS X/10.8 (12A269) CalendarAgent/47 
    // Mac_OS_X/10.9 (13A603) CalendarAgent/174
    // Mac+OS+X/10.10 (14A389) CalendarAgent/315"
    const HEADER_MATCH = '/(?J)((CalendarStore.*Mac OS X\/(?P<version>\S+) )|(^Mac[ _+]OS[ _+]X\/(?P<version>\S+).*CalendarAgent))/';
    
    protected $_supportedFields = array(
        'seq',
        'dtend',
        'transp',
        'class',
        'description',
        #'geo',
        'location',
        'priority',
        'summary',
        'url',
        'alarms',
        #'tags',
        'dtstart',
        'exdate',
        'rrule',
        'recurid',
        'is_all_day_event',
        #'rrule_until',
        'originator_tz',
    );
    
    /**
     * get attendee array for given contact
     * 
     * @param  \Sabre\VObject\Property\ICalendar\CalAddress  $calAddress  the attendee row from the vevent object
     * @return array
     */
    protected function _getAttendee(\Sabre\VObject\Property\ICalendar\CalAddress $calAddress)
    {
        $newAttendee = parent::_getAttendee($calAddress);

        // skip implicit organizer attendee.
        // NOTE: when the organizer edits the event he becomes attendee anyway, see comments in MSEventFacade::update

        // in mavericks iCal adds organiser as attendee without role
        if (version_compare($this->_version, '10.9', '>=') && version_compare($this->_version, '10.10', '<')) {
            if (!isset($calAddress['ROLE'])) {
                return NULL;
            }
        // in yosemite iCal adds organiser with role "chair" but has no roles for other attendee
        } else if (version_compare($this->_version, '10.10', '>=')) {
            if (isset($calAddress['ROLE']) && $calAddress['ROLE'] == 'CHAIR') {
                return NULL;
            }
        }
        
        return $newAttendee;
    }

    /**
     * add event attendee to VEVENT object
     *
     * @param \Sabre\VObject\Component\VEvent $vevent
     * @param Calendar_Model_Event            $event
     */
    protected function _addEventAttendee(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event)
    {
        parent::_addEventAttendee($vevent, $event);

        if (empty($event->attendee)) {
            return;
        }

        // add organizer as CHAIR Attendee if he's no organizer, otherwise yosemite would add an attendee
        // when editing the event again.
        // NOTE: when the organizer edits the event he becomes attendee anyway, see comments in MSEventFacade::update
        if (version_compare($this->_version, '10.10', '>=')) {
            if (!empty($event->organizer)) {
                $organizerContact = $event->resolveOrganizer();

                if ($organizerContact instanceof Addressbook_Model_Contact) {

                    $organizerAttendee = Calendar_Model_Attender::getAttendee($event->attendee, new Calendar_Model_Attender(array(
                        'user_id' => $organizerContact->getId(),
                        'user_type' => Calendar_Model_Attender::USERTYPE_USER
                    )));

                    if (! $organizerAttendee) {
                        $parameters = array(
                            'CN'       => $organizerContact->n_fileas,
                            'CUTYPE'   => 'INDIVIDUAL',
                            'PARTSTAT' => 'ACCEPTED',
                            'ROLE'     => 'CHAIR',
                        );
                        $organizerEmail = $organizerContact->email;
                        if (strpos($organizerEmail, '@') !== false) {
                            $parameters['EMAIL'] = $organizerEmail;
                        }
                        $vevent->add('ATTENDEE', (strpos($organizerEmail, '@') !== false ? 'mailto:' : 'urn:uuid:') . $organizerEmail, $parameters);
                    }
                }
            }
        }

    }
    /**
     * do version specific magic here
     *
     * @param \Sabre\VObject\Component\VCalendar $vcalendar
     * @return \Sabre\VObject\Component\VCalendar | null
     */
    protected function _findMainEvent(\Sabre\VObject\Component\VCalendar $vcalendar)
    {
        $return = parent::_findMainEvent($vcalendar);

        // NOTE 10.7 and 10.10 sometimes write access into calendar property
        if (isset($vcalendar->{'X-CALENDARSERVER-ACCESS'})) {
            foreach ($vcalendar->VEVENT as $vevent) {
                $vevent->{'X-CALENDARSERVER-ACCESS'} = $vcalendar->{'X-CALENDARSERVER-ACCESS'};
            }
        }

        return $return;
    }

    /**
     * parse VEVENT part of VCALENDAR
     *
     * @param  \Sabre\VObject\Component\VEvent  $vevent  the VEVENT to parse
     * @param  Calendar_Model_Event             $event   the Tine 2.0 event to update
     * @param  array                            $options
     */
    protected function _convertVevent(\Sabre\VObject\Component\VEvent $vevent, Calendar_Model_Event $event, $options)
    {
        $return = parent::_convertVevent($vevent, $event, $options);

        // NOTE: 10.7 sometimes uses (internal?) int's
        if (isset($vevent->{'X-CALENDARSERVER-ACCESS'}) && (int) (string) $vevent->{'X-CALENDARSERVER-ACCESS'} > 0) {
            $event->class = (int) (string) $vevent->{'X-CALENDARSERVER-ACCESS'} == 1 ?
                Calendar_Model_Event::CLASS_PUBLIC :
                Calendar_Model_Event::CLASS_PRIVATE;
        }

        return $return;
    }

    /**
     * iCal supports manged attachments
     *
     * @param Calendar_Model_Event          $event
     * @param Tinebase_Record_RecordSet     $attachments
     */
    protected function _manageAttachmentsFromClient($event, $attachments)
    {
        $event->attachments = $attachments;
    }
}
