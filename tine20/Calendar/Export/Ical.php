<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * @package     Calendar
 */
class Calendar_Export_Ical
{
    public static $_veventMap = array(
        'transp'               => 'transp',
        'class'                => 'class',
        'description'          => 'description',
        'geo'                  => 'geo',
        'location'             => 'location',
//        'organizer'            => 'organizer',
        'priority'             => 'priority',
//        'status_id'            => 'status',
        'summary'              => 'summary',
        'url'                  => 'url',
//        'recurid'              => 'recurid',
//        'exdate'               => array('allowEmpty' => true         ), //  array of Tinebase_DateTimeTinebase_DateTime's
//        'rrule'                => 'rrule',
    );
    
    public static function eventToIcal($_event)
    {
        $vcalendar = new qCal_Component_Vcalendar(array(
            'prodid'    => '-//tine20.org//Calendar v3.9//EN',
            'calscale'  => 'GREGORIAN',
//            'version'   => '2.0',
        ));
        
//        $_event->setTimezone($_event->originator_tz);
        $vevent = new qCal_Component_Vevent(array(
            'uid'           => $_event->uid,
            'sequence'      => $_event->seq,
            'summary'       => $_event->summary,
            'dtstart'       => $_event->dtstart->format('Ymd\THis'),
            'dtend'         => $_event->dtend->format('Ymd\THis'),
        ));
        
        foreach(self::$_veventMap as $icalProp => $tineField) {
            if (isset($_event[$tineField])) {
                $vevent->addProperty($icalProp, $_event->{$tineField});
            }
        }
        
//        // timezone
//        $timezone = new qCal_Component_Vtimezone(array(
//            'tzid'  => 'Europe/Berlin'
//        ), array(
//            new qCal_Component_Daylight(array(
//                'tzoffsetfrom'  => '+0100',
//                'tzoffsetto'    => '+0200',
//                'tzname'        => 'GMT+02:00',
//                'dtstart'       => '19810329T020000',
//                'rrule'         => 'FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU',
//            )),
//            new qCal_Component_Standard(array(
//                'tzoffsetfrom'  => '+0200',
//                'tzoffsetto'    => '+0100',
//                'tzname'        => 'GMT+01:00',
//                'dtstart'       => '19961027T030000',
//                'rrule'         => 'FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU',
//            )),
//        ));
//        $vcalendar->attach($timezone);
        
        // rrule (until needs different format)
        // recurid (needs different format?)
        // status
        // organizer
        // alarms
        // attendee
        // exdate
        
        $vcalendar->attach($vevent);
        
        return $vcalendar;
        
//$todo = new qCal_Component_Vtodo(array(
//    'class' => 'private',
//    'dtstart' => '20090909',
//    'description' => 'Eat some bacon!!',
//    'summary' => 'Eat bacon',
//    'priority' => 1,
//));
//    $todo->attach(new qCal_Component_Valarm(array(
//        'action' => 'audio',
//        'trigger' => '20090423',
//        'attach' => 'http://www.example.com/foo.wav',
//    )));
//$calendar->attach($todo);
//
//        $ical = new qCal_Component_Vcalendar();
        
    }
}