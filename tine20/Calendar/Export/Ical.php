<?php
/**
 * Tine 2.0
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * @package     Calendar
 */
class Calendar_Export_Ical
{
    
    public static $cutypeMap = array(
        Calendar_Model_Attender::USERTYPE_USER          => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUPMEMBER   => 'INDIVIDUAL',
        Calendar_Model_Attender::USERTYPE_GROUP         => 'GROUP',
        Calendar_Model_Attender::USERTYPE_RESOURCE      => 'RESOURCE',
    );
    
    public static $veventMap = array(
        'transp'               => 'transp',
        'class'                => 'class',
        'description'          => 'description',
        'geo'                  => 'geo',
        'location'             => 'location',
        'priority'             => 'priority',
        'summary'              => 'summary',
        'url'                  => 'url',
    );
    
    /**
     * @var array already attached timezones
     */
    protected $_attachedTimezones = array();
    
    /**
     * @var qCal_Component_Vcalendar
     */
    protected $_vcalendar;
    
    public function __construct()
    {
        
        // start a new vcalendar
        $version = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->version;
        $this->_vcalendar = new qCal_Component_Vcalendar(array(
            'prodid'    => "-//tine20.org//Calendar v$version//EN",
            'calscale'  => 'GREGORIAN',
            'version'   => '2.0',
        ));
        
    }
    
    public function eventToIcal($_event)
    {
        if ($_event instanceof Tinebase_Record_RecordSet) {
            foreach($_event as $event) {
                $this->eventToIcal($event);
            }
            
            return $this->_vcalendar;
        }
        
        // NOTE: we deliver events in originators tz
        $_event->setTimezone($_event->originator_tz);
        if (! in_array($_event->originator_tz, $this->_attachedTimezones)) {
            $this->_vcalendar->attach(self::getVtimezone($_event->originator_tz));
            $this->_attachedTimezones[] = $_event->originator_tz;
        }
        
        if ($_event->is_all_day_event) {
            $dtstart = new qCal_Property_Dtstart($_event->dtstart->format('Ymd'), array('VALUE' => 'DATE'));
            $dtend = new qCal_Property_Dtend($_event->dtend->format('Ymd'), array('VALUE' => 'DATE'));
        } else {
            $dtstart = new qCal_Property_Dtstart(qCal_DateTime::factory($_event->dtstart->format('Ymd\THis'), $_event->originator_tz), array('TZID' => $_event->originator_tz));
            $dtend = new qCal_Property_Dtend(qCal_DateTime::factory($_event->dtend->format('Ymd\THis'), $_event->originator_tz), array('TZID' => $_event->originator_tz));
        }
        
        $vevent = new qCal_Component_Vevent(array(
            'uid'           => $_event->uid,
            'sequence'      => $_event->seq,
            'summary'       => $_event->summary,
            'dtstart'       => $dtstart,
            'dtend'         => $dtend,
        ));
        
        foreach(self::$veventMap as $icalProp => $tineField) {
            if (isset($_event[$tineField])) {
                $vevent->addProperty($icalProp, $_event->{$tineField});
            }
        }
        
        // rrule
        if ($_event->rrule) {
            $vevent->addProperty('rrule', preg_replace('/(UNTIL=)(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', '$1$2$3$4T$5$6$7Z', $_event->rrule));
            
            if ($exdateArray = $_event->exdate) {
                // use multiple EXDATE for the moment, as apple ical uses them
                foreach($_event->exdate as $exdate) {
                    $exdates = new qCal_Property_Exdate(qCal_DateTime::factory($exdate->format('Ymd\THis'), $_event->originator_tz), array('TZID' => $_event->originator_tz));
                    $vevent->addProperty($exdates);
                }
            
//                $exdates = new qCal_Property_Exdate(qCal_DateTime::factory(array_shift($exdateArray)->format('Ymd\THis'), $_event->originator_tz), array('TZID' => $_event->originator_tz));
//                foreach($exdateArray as $exdate) {
//                    $exdates->addValue(qCal_DateTime::factory($exdate->format('Ymd\THis'), $_event->originator_tz));
//                }
//                
//                $vevent->addProperty($exdates);
            }
        }
        
        // recurid
        if ($_event->isRecurException()) {
            $originalDtStart = $_event->getOriginalDtStart();
            $originalDtStart->setTimezone($_event->originator_tz);
            
            $vevent->addProperty(new qCal_Property_RecurrenceId(qCal_DateTime::factory($originalDtStart->format('Ymd\THis'), $_event->originator_tz), array('TZID' => $_event->originator_tz)));
        }
        
        // organizer
        $organizerId = $_event->organizer instanceof Addressbook_Model_Contact ? array($_event->organizer->getId()) : array($_event->organizer);
        $organizer = Addressbook_Controller_Contact::getInstance()->getMultiple($organizerId, TRUE)->getFirstRecord();
        if ($organizer && $organizerEmail = $organizer->getPreferedEmailAddress()) {
            $vevent->addProperty(new qCal_Property_Organizer("mailto:$organizerEmail", array('CN' => $organizer->n_fileas)));
        }
        
        // attendee
        if ($_event->attendee) {
            Calendar_Model_Attender::resolveAttendee($_event->attendee, FALSE);
            
            foreach($_event->attendee as $attender) {
                $attenderEmail = $attender->getEmail();
                if ($attenderEmail) {
                    $vevent->addProperty(new qCal_Property_Attendee("mailto:$attenderEmail", array(
                        'CN'        => $attender->getName(),
                        'CUTYPE'    => self::$cutypeMap[$attender->user_type],
                        'EMAIL'     => $attenderEmail,
                        'PARTSTAT'  => $attender->status,
                        'ROLE'      => "{$attender->role}-PARTICIPANT",
                        'RSVP'      => 'FALSE'
                    )));
                }
            }
        }
        
        // alarms
        if ($_event->alarms) {
            foreach($_event->alarms as $alarm) {
                $valarm = new qCal_Component_Valarm(array(
                    'ACTION'        => 'DISPLAY',
                    'DESCRIPTION'   =>  $_event->summary,
                ));
                
                // qCal only support DURATION ;-(
                $diffSeconds  = $_event->dtstart->php52compat_diff($alarm->alarm_time);
                $valarm->addProperty(new qCal_Property_Trigger($diffSeconds));
                
//                if (is_numeric($alarm->minutes_before)) {
//                    $valarm->addProperty(new qCal_Property_Trigger("-PT{$alarm->minutes_before}M"));
//                } else {
//                    $valarm->addProperty(new qCal_Property_Trigger(qCal_DateTime::factory($alarm->alarm_time->format('Ymd\THis'), $_event->originator_tz)), array('TZID' => $_event->originator_tz));
//                }
                
                $vevent->attach($valarm);
            }
        }
        
        // @todo status
        
        $this->_vcalendar->attach($vevent);
        
        return $this->_vcalendar;
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
