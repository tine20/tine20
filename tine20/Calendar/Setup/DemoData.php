<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Calendar initialization
 *
 * @package     Setup
 */
class Calendar_Setup_DemoData extends Tinebase_Setup_DemoData_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Calendar_Setup_DemoData
     */
    private static $_instance = NULL;

    /**
     * models to work on
     * @var unknown_type
     */
    protected $_models = array('event');

    /**
     * private calendars
     * @var Array
     */
    protected $_calendars = array();

    /**
     * the constructor
     *
     */
    private function __construct()
    {

    }

    /**
     * the singleton pattern
     *
     * @return Calendar_Setup_DemoData
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Calendar_Setup_DemoData;
        }

        return self::$_instance;
    }

    protected function _onCreate() {

        $this->_getDays();

        foreach($this->_personas as $loginName => $persona) {
            $this->_calendars[$loginName] = Tinebase_Container::getInstance()->getContainerById(Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $persona->getId()));
//             $c->name = ($this->en) ? $persona->accountFullName .'\'s personal Calendar' : $persona->accountFullName .'s persönlicher Kalender';
//              = Tinebase_Container::getInstance()->update($c);

            Tinebase_Container::getInstance()->addGrants($this->_calendars[$loginName]->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
            Tinebase_Container::getInstance()->addGrants($this->_calendars[$loginName]->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);

        }
    }

    private function _createSharedCalendar()
    {
        // create shared calendar
        $this->sharedCalendar = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $this->de ? 'Gemeinsamer Kalender' : 'Shared Calendar',
            'type'           => Tinebase_Model_Container::TYPE_SHARED,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00FF00'
        ), true));

        $group = Tinebase_Group::getInstance()->getGroupByName(Tinebase_Group::DEFAULT_USER_GROUP);
        Tinebase_Container::getInstance()->addGrants($this->sharedCalendar->getId(), 'group', $group->getId(), $this->_userGrants, true);
        Tinebase_Container::getInstance()->addGrants($this->sharedCalendar->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);

    }

    private function _getDays() {
        // find out where we are
        $now = new DateTime();
        $weekday = $now->format('w');

        $subdaysLastMonday = 6 + $weekday;    // Monday last Week
        $subdaysLastFriday = 2 + $weekday;    // Friday last Week

        $subdaysMonday = $weekday -1;

        // this week
        $this->monday = new DateTime();
        $this->monday->sub(date_interval_create_from_date_string(($weekday - 1) . ' days'));
        $this->tuesday = new DateTime();
        $this->tuesday->sub(date_interval_create_from_date_string(($weekday - 2) . ' days'));
        $this->wednesday = new DateTime();
        $this->wednesday->sub(date_interval_create_from_date_string(($weekday - 3) . ' days'));
        $this->thursday = new DateTime();
        $this->thursday->sub(date_interval_create_from_date_string(($weekday - 4) . ' days'));
        $this->friday = new DateTime();
        $this->friday->sub(date_interval_create_from_date_string(($weekday - 5) . ' days'));
        $this->saturday = clone $this->friday;
        $this->saturday->add(date_interval_create_from_date_string('1 day'));
        $this->sunday = clone $this->friday;
        $this->sunday->add(date_interval_create_from_date_string('2 days'));

        // last week
        $this->lastMonday = new DateTime();
        $this->lastMonday->sub(date_interval_create_from_date_string($subdaysLastMonday . ' days'));
        $this->lastFriday = new DateTime();
        $this->lastFriday->sub(date_interval_create_from_date_string($subdaysLastFriday . ' days'));
        $this->lastSaturday = clone $this->lastFriday;
        $this->lastSaturday = $this->lastSaturday->add(date_interval_create_from_date_string('1 day'));
        $this->lastSunday = clone $this->lastFriday;
        $this->lastSunday = $this->lastSunday->add(date_interval_create_from_date_string('2 days'));
    }

    protected function createSharedEvents()
    {
        $this->_createSharedCalendar();

        $monday = clone $this->monday;
        $tuesday = clone $this->tuesday;
        $wednesday = clone $this->wednesday;
        $thursday = clone $this->thursday;
        $friday = clone $this->friday;
        $lastMonday = clone $this->lastMonday;
        $lastFriday = clone $this->lastFriday;

        $defaultAttendeeData = array(
            'alarm_ack_time'  => null,
            'alarm_snooze_time'  => null,
            'quantity'  => "1",
            'role'  => "REQ",
            'status'  => "ACCEPTED",
            'transp'  => "OPAQUE",
            'user_type'  => "user"
        );
        $defaultData = array(
            'container_id' => $this->sharedCalendar->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true,

            'attendee' => array(
                array_merge($defaultAttendeeData,
                    array('user_id'  => $this->_personas['pwulf']->toArray())
                ),
                array_merge($defaultAttendeeData,
                    array('user_id'  => $this->_personas['sclever']->toArray())
                ),
                array_merge($defaultAttendeeData,
                    array('user_id'  => $this->_personas['jsmith']->toArray())
                ),
                array_merge($defaultAttendeeData,
                    array('user_id'  => $this->_personas['jmcblack']->toArray())
                ),
                array_merge($defaultAttendeeData,
                    array('user_id'  => $this->_personas['rwright']->toArray())
                ),
            )


        );
        $lastMonday->add(date_interval_create_from_date_string('20 weeks'));
        $rruleUntilMondayMeeting = $lastMonday->format('d-m-Y') . ' 16:00:00';
        
        // shared events data
        $this->sharedEventsData = array(
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Mittagspause' : 'lunchtime',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 12:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 13:00:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Projektleitermeeting' : 'project leader meeting',
                    'description' => $this->de ? 'Treffen aller Projektleiter' : 'meeting of all project leaders',
                    'dtstart'     => $monday->format('d-m-Y') . ' 14:15:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 16:00:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Geschäftsführerbesprechung' : 'CEO Meeting',
                    'description' => $this->de ? 'Treffen aller Geschäftsführer' : 'Meeting of all CEO',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 12:30:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 13:45:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Fortbildungsveranstaltung' : 'Meeting for further education',
                    'description' => $this->de ? 'Wie verhalte ich mich meinen Mitarbeitern gegenüber in Problemsituationen.' : 'How to manage problematic situations with the employees',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:30:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Projektbesprechung Alpha' : 'project meeting alpha',
                    'description' => $this->de ? 'Besprechung des Projekts Alpha' : 'Meeting of the Alpha project',
                    'dtstart'     => $wednesday->format('d-m-Y') . ' 08:30:00',
                    'dtend'       => $wednesday->format('d-m-Y') . ' 09:45:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Projektbesprechung Beta' : 'project meeting beta',
                    'description' => $this->de ? 'Besprechung des Projekts Beta' : 'Meeting of the beta project',
                    'dtstart'     => $wednesday->format('d-m-Y') . ' 10:00:00',
                    'dtend'       => $wednesday->format('d-m-Y') . ' 11:00:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Betriebsausflug' : 'company trip',
                    'description' => $this->de ? 'Fahrt in die Semperoper nach Dresden' : 'Trip to the Semper Opera in Dresden',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 12:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 13:00:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Präsentation Projekt Alpha' : 'Presentation project Alpha',
                    'description' => $this->de ? 'Das Projekt Alpha wird der Firma GammaTecSolutions vorgestellt' : 'presentation of Project Alpha for GammaTecSolutions',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 16:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 17:00:00',
                )),
            array_merge_recursive($defaultData,
                array(
                    'summary'     => $this->de ? 'Montagsmeeting' : 'monday meeting',
                    'description' => $this->de ? 'Wöchentliches Meeting am Montag' : 'weekly meeting on monday',
                    'dtstart'     => $lastMonday->format('d-m-Y') . ' 10:00:00',
                    'dtend'       => $lastMonday->format('d-m-Y') . ' 12:30:00',
                    'rrule' => array(
                        'freq' => 'WEEKLY',
                        'interval' => '1',
                        'count' => 20,
                        'wkst' => 'MO',
                        'byday' => 'MO',
                    ),
                    'rrule_until' => $rruleUntilMondayMeeting
        )),
        array_merge_recursive($defaultData,
            array(
                'summary'     => $this->de ? 'Freitagsmeeting' : 'friday meeting',
                'description' => $this->de ? 'Wöchentliches Meeting am Freitag' : 'weekly meeting on friday',
                'dtstart'     => $lastFriday->format('d-m-Y') . ' 16:00:00',
                'dtend'       => $lastFriday->format('d-m-Y') . ' 17:30:00',
                'rrule' => array(
                    'freq' => 'WEEKLY',
                    'interval' => '1',
                    'count' => 20,
                    'wkst' => 'FR',
                    'byday' => 'FR',
                ),
                'rrule_until' => $lastFriday->add(date_interval_create_from_date_string('20 weeks'))->format('d-m-Y') . ' 16:00:00'
            ))
            );


        // create shared events
        foreach($this->sharedEventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }
    }

    protected function createEventsForPwulf() {

        // Paul Wulf
        $monday = clone $this->monday;
        $tuesday = clone $this->tuesday;
        $wednesday = clone $this->wednesday;
        $thursday = clone $this->thursday;
        $friday = clone $this->friday;
        $saturday = clone $this->saturday;
        $sunday = clone $this->sunday;
        $lastMonday = clone $this->lastMonday;
        $lastFriday = clone $this->lastFriday;
        $lastSaturday = clone $this->lastSaturday;
        $lastSunday = clone $this->lastSunday;

        $cal = $this->_calendars['pwulf'];
        $user = $this->_personas['pwulf'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'alarm_ack_time'  => null,
                'alarm_snooze_time'  => null,
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Lucy\'s Geburtstag' : 'Lucy\'s birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Lucy\'s Geburtstagsfeier' : 'Lucy\'s birthday party',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Wettlauf mit Kevin' : 'Race with Kevin',
                    'description' => $this->de ? 'Treffpunkt ist am oberen Parkplatz' : 'Meet at upper parking lot',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Schwimmen gehen' : 'go swimming',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Schwimmen gehen' : 'go swimming',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 18:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Auto aus der Werkstatt abholen' : 'fetch car from the garage',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 16:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Oper mit Lucy' : 'Got to the Opera with Lucy',
                    'description' => $this->de ? 'Brighton Centre' : 'Brighton Centre',
                    'dtstart'     => $sunday->format('d-m-Y') . ' 20:00:00',
                    'dtend'       => $sunday->format('d-m-Y') . ' 21:30:00',
                )
            ),

        );
        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }

        $cal = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $this->de ? 'Geschäftlich' : 'Business',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00CCFF'
        ), true));

        Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Projektbesprechung Projekt Epsilon mit John' : 'Project Epsilon Meeting with John',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 08:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 09:30:00',
                )
            ),
        );

        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }
    }

    protected function createEventsForJsmith() {

        // John Smith
        $monday = clone $this->monday;
        $tuesday = clone $this->tuesday;
        $wednesday = clone $this->wednesday;
        $thursday = clone $this->thursday;
        $friday = clone $this->friday;
        $saturday = clone $this->saturday;
        $sunday = clone $this->sunday;
        $lastMonday = clone $this->lastMonday;
        $lastFriday = clone $this->lastFriday;
        $lastSaturday = clone $this->lastSaturday;
        $lastSunday = clone $this->lastSunday;

        $cal = $this->_calendars['jsmith'];
        $user = $this->_personas['jsmith'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'alarm_ack_time'  => null,
                'alarm_snooze_time'  => null,
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Catherine\'s Geburtstag' : 'Catherine\'s birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false, "recurid" => null, "minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Elternabend Anne' : 'Talk to Ann\'s teacher',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'I-Phone vom I-Store abholen'  : 'Fetch Iphone from store',
                    'description' => $this->de ? '':'',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Anne vom Sport abholen' : 'Pick up Ann after her sports lesson',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Paul vom Klavierunterricht abholen' : 'Pick up Paul after his piano lesson',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 18:00:00',
                )
            ),

        );
        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }

        $cal = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $this->de ? 'Geschäftlich' : 'Business',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00CCFF'
        ), true));

        Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);

        $defaultEventData['container_id'] = $cal->getId();
        $defaultEventData['attendee'][] = array(
            'alarm_ack_time'  => null,
            'alarm_snooze_time'  => null,
            'quantity'  => "1",
            'role'  => "REQ",
            'status'  => "NEEDS-ACTION",
            'transp'  => "OPAQUE",
            'user_id'  => $this->_personas['jsmith']->toArray(),
            'user_type'  => "user"
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Projektbesprechung Projekt Epsilon mit John' : 'Project Epsilon Meeting with John',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 09:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 10:30:00',
                )
            ),
        );

        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }
    }

    protected function createEventsForRwright() {
        // Roberta Wright
        $monday = clone $this->monday;
        $tuesday = clone $this->tuesday;
        $wednesday = clone $this->wednesday;
        $thursday = clone $this->thursday;
        $friday = clone $this->friday;
        $saturday = clone $this->saturday;
        $sunday = clone $this->sunday;
        $lastMonday = clone $this->lastMonday;
        $lastFriday = clone $this->lastFriday;
        $lastSaturday = clone $this->lastSaturday;
        $lastSunday = clone $this->lastSunday;

        $cal = $this->_calendars['rwright'];
        $user = $this->_personas['rwright'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'alarm_ack_time'  => null,
                'alarm_snooze_time'  => null,
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Joshuas Geburtstag' : 'Joshua\'s Birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'James Geburtstag' : 'James\'s Birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $wednesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Shoppen mit Susan' : 'Go shopping with Susan',

                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Joga Kurs' : 'yoga course',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 16:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 18:00:00',
                    'rrule' => array(
                        "bymonth" => $saturday->format('m'),
                        "bymonthday" => $saturday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Controlling einfach gemacht' : 'Controlling made easy',
                    'description' => $this->de ? 'Fortbildungsveranstaltung' : 'further education',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                )
            )
        );
        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }

        $cal = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $this->de ? 'Geschäftlich' : 'Business',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00CCFF'
        ), true));

        Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Präsentation Quartalszahlen' : 'presentation quarter figures',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 09:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 10:30:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Kostenstellenanalyse' : 'cost put analysis',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 12:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Controller Meeting' : 'Controllers meeting',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 12:00:00',
                )
            ),
        );

        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }

    }

    protected function createEventsForSclever() {
        // Susan Clever
        $monday = clone $this->monday;
        $tuesday = clone $this->tuesday;
        $wednesday = clone $this->wednesday;
        $thursday = clone $this->thursday;
        $friday = clone $this->friday;
        $saturday = clone $this->saturday;
        $sunday = clone $this->sunday;
        $lastMonday = clone $this->lastMonday;
        $lastFriday = clone $this->lastFriday;
        $lastSaturday = clone $this->lastSaturday;
        $lastSunday = clone $this->lastSunday;

        $cal = $this->_calendars['sclever'];
        $user = $this->_personas['sclever'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'alarm_ack_time'  => null,
                'alarm_snooze_time'  => null,
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Elvis\' Geburtstag' : 'Elvis\' birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $lastSaturday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $monday->format('m'),
                        "bymonthday" => $monday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'James Geburtstag' : 'James\'s Birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $wednesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Shoppen mit Roberta' : 'Go shopping with Roberta',

                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Shoppen gehen' : 'go shopping',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Shoppen gehen' : 'go shopping',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Tanzen gehen mit Elvis' : 'Dance with Elvis',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Disco Fever' : 'Disco fever',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 23:00:00',
                )
            ),
        );
        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }

    }

    protected function createEventsForJmcblack() {

        // James McBlack
        $monday = clone $this->monday;
        $tuesday = clone $this->tuesday;
        $wednesday = clone $this->wednesday;
        $thursday = clone $this->thursday;
        $friday = clone $this->friday;
        $saturday = clone $this->saturday;
        $sunday = clone $this->sunday;
        $lastMonday = clone $this->lastMonday;
        $lastFriday = clone $this->lastFriday;
        $lastSaturday = clone $this->lastSaturday;
        $lastSunday = clone $this->lastSunday;

        $cal = $this->_calendars['jmcblack'];
        $user = $this->_personas['jmcblack'];

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            'class' => 'PRIVATE',
            Tinebase_Model_Grants::GRANT_EDIT    => true,
            'attendee' => array(array(
                'alarm_ack_time'  => null,
                'alarm_snooze_time'  => null,
                'quantity'  => "1",
                'role'  => "REQ",
                'status'  => "ACCEPTED",
                'transp'  => "OPAQUE",
                'user_id'  => $user->toArray(),
                'user_type'  => "user"
            ))
        );
        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Catherines Geburtstag' : 'Catherine\'s Birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $tuesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $thursday->format('m'),
                        "bymonthday" => $thursday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Alyssas Geburtstag' : 'Alyssa\'s Birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $friday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $friday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $wednesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $friday->format('m'),
                        "bymonthday" => $friday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Brendas\' Geburtstag' : 'Brenda\'s Birthday',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 00:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 23:59:00',
                    'is_all_day_event' => true,
                    'alarms'      => array(array(
                        'alarm_time' => $tuesday->format('d-m-Y') . " 12:00:00",
                        'minutes_before' => 2880,
                        'model' => "Calendar_Model_Event",
                        'options' => json_encode(array("custom" => false,"recurid" => null,"minutes_before" => "2880")),
                        'sent_message' => "",
                        'sent_status' => "pending"
                    )),
                    'rrule' => array(
                        "bymonth" => $thursday->format('m'),
                        "bymonthday" => $thursday->format('d'),
                        "freq" => "YEARLY",
                        "interval" => "1",
                    ),
                    'rrule_until' => ''
                )),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Automesse in Liverpool' : 'Auto fair in Liverpool',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 19:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 23:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Weinverkostung auf der Burg' : 'Wine tasting at the castle',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $saturday->format('d-m-Y') . ' 15:00:00',
                    'dtend'       => $saturday->format('d-m-Y') . ' 16:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Eigentümerversammlung' : 'Owners\' meeting',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $tuesday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $tuesday->format('d-m-Y') . ' 18:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Datamining Konferenz' : 'Data mining conference',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $thursday->format('d-m-Y') . ' 17:00:00',
                    'dtend'       => $thursday->format('d-m-Y') . ' 18:00:00',
                )
            )
        );
        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }

        $cal = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $this->de ? 'Geschäftlich' : 'Business',
            'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'       => Tinebase_Core::getUser(),
            'backend'        => 'SQL',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'color'          => '#00CCFF'
        ), true));

        Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['sclever']->getId(), $this->_secretaryGrants, true);
        Tinebase_Container::getInstance()->addGrants($cal->getId(), 'user', $this->_personas['rwright']->getId(), $this->_controllerGrants, true);

        $defaultEventData = array(
            'container_id' => $cal->getId(),
            Tinebase_Model_Grants::GRANT_EDIT    => true
        );

        $eventsData = array(
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Projektbesprechung Projekt Gamma mit Herrn Pearson' : 'Project Gamma Meeting with Mr. Pearson',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 09:00:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 10:30:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'MDH Pitch' : 'MDH Pitch',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 12:00:00',
                )
            ),
            array_merge_recursive($defaultEventData,
                array(
                    'summary'     => $this->de ? 'Mitarbeitergespräch mit Jack' : 'employee appraisal with Jack',
                    'description' => $this->de ? '' : '',
                    'dtstart'     => $monday->format('d-m-Y') . ' 10:30:00',
                    'dtend'       => $monday->format('d-m-Y') . ' 12:00:00',
                )
            ),
        );

        foreach($eventsData as $eData) {
            $event = new Calendar_Model_Event($eData);
            Calendar_Controller_Event::getInstance()->create($event, false);
        }
    }
}
