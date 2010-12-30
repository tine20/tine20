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
//        'exdate'               => 'exdate', //  array of Tinebase_DateTimeTinebase_DateTime's
//        'rrule'                => 'rrule',
    );
    
    public static function eventToIcal($_event)
    {
        $version = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->version;
        $vcalendar = new qCal_Component_Vcalendar(array(
            'prodid'    => "-//tine20.org//Calendar v$version//EN",
            'calscale'  => 'GREGORIAN',
            'version'   => '2.0',
        ));
        
        // NOTE: we deliver events in originators tz
        $_event->setTimezone($_event->originator_tz);
        $vcalendar->attach(self::getVtimezone($_event->originator_tz));
        
        $vevent = new qCal_Component_Vevent(array(
            'uid'           => $_event->uid,
            'sequence'      => $_event->seq,
            'summary'       => $_event->summary,
            'dtstart'       => new qCal_Property_Dtstart(qCal_DateTime::factory($_event->dtstart->format('Ymd\THis'), $_event->originator_tz), array('TZID' => $_event->originator_tz)),
            'dtend'         => new qCal_Property_Dtend(qCal_DateTime::factory($_event->dtend->format('Ymd\THis'), $_event->originator_tz), array('TZID' => $_event->originator_tz)),
        ));
        
        foreach(self::$_veventMap as $icalProp => $tineField) {
            if (isset($_event[$tineField])) {
                $vevent->addProperty($icalProp, $_event->{$tineField});
            }
        }
        
        if ($_event->rrule) {
            $vevent->addProperty('rrule', preg_replace('/(UNTIL=)(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4T$5$6$7Z', $_event->rrule));
        }
        // rrule (until needs different format)
        // recurid (needs different format?)
        // status
        // organizer
        // alarms
        // attendee
        // exdate
        
        $vcalendar->attach($vevent);
        
        return $vcalendar;
    }
    
    // quick and dirty -> @improveme
    public static function getVtimezone($tzName)
    {
        $tzinfo = ActiveSync_TimezoneConverter::getInstance()
            ->getOffsetsForTimezone($tzName);

        $standardOffset = ((-1 *  $tzinfo['bias']) > 0 ? '+' : '-') .
            str_pad((string) floor(-1 * $tzinfo['bias'] / 60), 2, 0, STR_PAD_LEFT) . 
            str_pad((string) floor(-1 * $tzinfo['bias'] % 60), 2, 0, STR_PAD_LEFT);
            
        $daylightOffset = ((-1 *  ($tzinfo['bias'] + $tzinfo['daylightBias'])) > 0 ? '+' : '-') .
            str_pad((string) floor(-1 * ($tzinfo['bias'] + $tzinfo['daylightBias'])/ 60), 2, 0, STR_PAD_LEFT) . 
            str_pad((string) floor(-1 * ($tzinfo['bias'] + $tzinfo['daylightBias']) % 60), 2, 0, STR_PAD_LEFT);
                
        return new qCal_Component_Vtimezone(array(
            'tzid'  => $tzName
        ), array(
            new qCal_Component_Daylight(array(
                'tzoffsetfrom'  => $standardOffset,
                'tzoffsetto'    => $daylightOffset,
                'rrule'         => "FREQ=YEARLY;BYMONTH={$tzinfo['daylightMonth']};BYDAY=" . ($tzinfo['daylightDay'] < 5 ? $tzinfo['daylightDay'] : '-1') . array_search($tzinfo['daylightDayOfWeek'], Calendar_Model_Rrule::$WEEKDAY_DIGIT_MAP),
            )),
            new qCal_Component_Standard(array(
                'tzoffsetfrom'  => $daylightOffset,
                'tzoffsetto'    => $standardOffset,
                'rrule'         => "FREQ=YEARLY;BYMONTH={$tzinfo['standardMonth']};BYDAY=" . ($tzinfo['daylightDay'] < 5 ? $tzinfo['daylightDay'] : '-1') . array_search($tzinfo['standardDayOfWeek'], Calendar_Model_Rrule::$WEEKDAY_DIGIT_MAP),
            )),
        ));
    }
}