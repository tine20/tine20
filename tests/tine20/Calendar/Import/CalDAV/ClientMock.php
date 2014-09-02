<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar_Import_CalDAV_ClientMock
 * 
 * @package     Calendar
 * @subpackage  Import
 */
class Calendar_Import_CalDAV_ClientMock extends Calendar_Import_CalDav_Client
{
    /**
     * needs to be overwritten because of the added flavor (osxical)
     * 
     * @var string
     */
    const findAllCalendarsRequest = '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:resourcetype/>
    <d:acl/>
    <d:displayname/>
    <x:supported-calendar-component-set xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  <osxical:calendar-color xmlns:osxical="http://apple.com/ns/ical/"/></d:prop>
</d:propfind>
';
    
    protected $_currentUserPrincipalResponse = array(
        '{DAV:}current-user-principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/'
    );
    
    protected $_calendarHomeSetResponse =  array (
        '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0',
    );
    
    protected $_calendarICSResponse = array (
      '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/' => 
      array (
      ),
      '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/20E3E0E4-762D-42D6-A563-206161A9F1CA.ics' => 
      array (
      ),
      '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/4971F93F-8657-412B-841A-A0FD9139CD61.ics' => 
      array (
      ),
      '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/88F077A1-6F5B-4C6C-8D73-94C1F0127492.ics' => 
      array (
      ),
    );
    
    protected $_serverEvents = array();
    
    public function __construct(array $a, $flavor)
    {
        parent::__construct($a, $flavor);
        
        $this->_serverEvents = array(
            '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/20E3E0E4-762D-42D6-A563-206161A9F1CA.ics' =>
                array (
                    self::calendarDataKey => 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 5.0.3//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
DTSTART:19810329T020000
TZNAME:CEST
TZOFFSETTO:+0200
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
DTSTART:19961027T030000
TZNAME:CET
TZOFFSETTO:+0100
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20140602T131852Z
UID:20E3E0E4-762D-42D6-A563-206161A9F1CA
DTEND;TZID=Europe/Berlin:20140604T171500
TRANSP:OPAQUE
SUMMARY:TEST06
DTSTART;TZID=Europe/Berlin:20140604T161500
DTSTAMP:20140602T131935Z
SEQUENCE:3
END:VEVENT
END:VCALENDAR',
                    '{DAV:}getetag' => '"bcc36c611f0b60bfee64b4d42e44aa1d"',
                ),
                '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/4971F93F-8657-412B-841A-A0FD9139CD61.ics' =>
                array (
                    self::calendarDataKey => 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 5.0.3//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
DTSTART:19810329T020000
TZNAME:CEST
TZOFFSETTO:+0200
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
DTSTART:19961027T030000
TZNAME:CET
TZOFFSETTO:+0100
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20140602T131707Z
UID:4971F93F-8657-412B-841A-A0FD9139CD61
DTEND;TZID=Europe/Berlin:20140604T153000
TRANSP:OPAQUE
SUMMARY:TEST05
DTSTART;TZID=Europe/Berlin:20140604T143000
DTSTAMP:20140602T131725Z
SEQUENCE:3
END:VEVENT
END:VCALENDAR',
                    '{DAV:}getetag' => '"8b89914690ad7290fa9a2dc1da490489"',
                ),
                '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/88F077A1-6F5B-4C6C-8D73-94C1F0127492.ics' =>
                array (
                    self::calendarDataKey => 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 5.0.1//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
DTSTART:19810329T020000
TZNAME:CEST
TZOFFSETTO:+0200
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
DTSTART:19961027T030000
TZNAME:CET
TZOFFSETTO:+0100
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20111207T143455Z
UID:88F077A1-6F5B-4C6C-8D73-94C1F0127492
DTEND;TZID=Europe/Berlin:20111207T170000
TRANSP:OPAQUE
SUMMARY:test
DTSTART;TZID=Europe/Berlin:20111207T160000
DTSTAMP:20111207T143502Z
SEQUENCE:2
END:VEVENT
END:VCALENDAR
',
                    '{DAV:}getetag' => '"0b3621a20e9045d8679075db57e881dd"',
                ),
        );
    }
        
    protected function _findAllCalendarsResponse()
    {
        return array (
          '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/' => 
               array(
                  '{DAV:}resourcetype' => new Sabre\DAV\Property\ResourceType(array('{DAV:}collection')),
                  '{DAV:}acl' => new Sabre\DAVACL\Property\Acl(array(
                    array (
                      'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/',
                      'protected' => true,
                      'privilege' => '{DAV:}all',
                    ),
                    array (
                      'principal' => '{DAV:}authenticated',
                      'protected' => false,
                      'privilege' => '{urn:ietf:params:xml:ns:caldav}read-free-busy',
                    ),
                    array (
                      'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-read/',
                      'protected' => true,
                      'privilege' => '{DAV:}read',
                    ),
                    array (
                      'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-read/',
                      'protected' => true,
                      'privilege' => '{DAV:}read-current-user-privilege-set',
                    ),
                    array (
                      'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-write/',
                      'protected' => true,
                      'privilege' => '{DAV:}read',
                    ),
                    array (
                      'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-write/',
                      'protected' => true,
                      'privilege' => '{DAV:}read-current-user-privilege-set',
                    ),
                    array (
                      'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-write/',
                      'protected' => true,
                      'privilege' => '{DAV:}write',
                    ),
                )),
                '{DAV:}displayname' => 'User1 Test',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new
                    Sabre\CalDAV\Property\SupportedCalendarComponentSet(array(
                        0 => 'VEVENT',
                        1 => 'VTODO',
                        2 => 'VTIMEZONE',
                        3 => 'VJOURNAL',
                        4 => 'VFREEBUSY',
                    )),
               ),
            '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/' =>
            array (
                '{DAV:}resourcetype' =>
                new Sabre\DAV\Property\ResourceType(array(
                    0 => '{DAV:}collection',
                    1 => '{urn:ietf:params:xml:ns:caldav}calendar',
                )),
                '{DAV:}acl' =>
                new Sabre\DAVACL\Property\Acl(array(
                    array (
                        'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/',
                        'protected' => true,
                        'privilege' => '{DAV:}all',
                    ),
                    array (
                        'principal' => '{DAV:}authenticated',
                        'protected' => false,
                        'privilege' => '{urn:ietf:params:xml:ns:caldav}read-free-busy',
                    ),
                    array (
                        'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-read/',
                        'protected' => true,
                        'privilege' => '{DAV:}read',
                    ),
                    array (
                        'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-read/',
                        'protected' => true,
                        'privilege' => '{DAV:}read-current-user-privilege-set',
                    ),
                    array (
                        'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-write/',
                        'protected' => true,
                        'privilege' => '{DAV:}read',
                    ),
                    array (
                        'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-write/',
                        'protected' => true,
                        'privilege' => '{DAV:}read-current-user-privilege-set',
                    ),
                    array (
                        'principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar-proxy-write/',
                        'protected' => true,
                        'privilege' => '{DAV:}write',
                    ),
                )),
                '{DAV:}displayname' => 'calendar',
                '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' =>
                new Sabre\CalDAV\Property\SupportedCalendarComponentSet(array(
                    0 => 'VEVENT',
                    1 => 'VTODO',
                    2 => 'VTIMEZONE',
                    3 => 'VJOURNAL',
                    4 => 'VFREEBUSY',
                )),
                '{http://apple.com/ns/ical/}calendar-color' => '#711A76FF',
            ),
            );
    }
    
    protected function _resolvePrincipalResponse()
    {
        return array (
          '{DAV:}group-member-set' => 
          new Tinebase_Import_CalDav_GroupMemberSet(array(
          )),
          '{DAV:}displayname' => 'calendar-proxy-write',
        );
    }
    
    /**
     * calendar data response
     * 
     * @return string
     */
    protected function _calendarDataResponse()
    {
        return $this->_serverEvents;
    }
    
    protected function _calendarEtagResponse()
    {
        $etags = array();
        foreach($this->_serverEvents as $ics => $data) {
            $etags[$ics] = array(
                '{DAV:}getetag' => $data['{DAV:}getetag']
            );
        }
        return $etags;
    }
    
    /**
     * update 1 event, delete one event, add one event
     */
    public function updateServerEvents()
    {
        // update
        $ics = '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/88F077A1-6F5B-4C6C-8D73-94C1F0127492.ics';
        
        $this->_serverEvents[$ics]['{DAV:}getetag'] =
            '"aa3621a20e9045d8679075db57e881dd"';
        
        $this->_serverEvents[$ics][self::calendarDataKey] = str_replace(array(
            'SEQUENCE:2',
           'SUMMARY:test'
        ), array(
            'SEQUENCE:3',
            'SUMMARY:test update'
        ), $this->_serverEvents[$ics][self::calendarDataKey]);
        
        // delete
        unset($this->_serverEvents['/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/4971F93F-8657-412B-841A-A0FD9139CD61.ics']);
        
        // add
        $this->_serverEvents['/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/calendar/3331F93F-8657-412B-841A-A0FD9139CD61.ics'] =
        array (
            self::calendarDataKey => 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Apple Inc.//iCal 5.0.3//EN
CALSCALE:GREGORIAN
BEGIN:VTIMEZONE
TZID:Europe/Berlin
BEGIN:DAYLIGHT
TZOFFSETFROM:+0100
RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU
DTSTART:19810329T020000
TZNAME:CEST
TZOFFSETTO:+0200
END:DAYLIGHT
BEGIN:STANDARD
TZOFFSETFROM:+0200
RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU
DTSTART:19961027T030000
TZNAME:CET
TZOFFSETTO:+0100
END:STANDARD
END:VTIMEZONE
BEGIN:VEVENT
CREATED:20140602T131707Z
UID:3331F93F-8657-412B-841A-A0FD9139CD61
DTEND;TZID=Europe/Berlin:20140804T153000
TRANSP:OPAQUE
SUMMARY:new event
DTSTART;TZID=Europe/Berlin:20140804T143000
DTSTAMP:20140602T131725Z
SEQUENCE:1
END:VEVENT
END:VCALENDAR',
            '{DAV:}getetag' => '"-1030341843%40citrixonlinecom"',
        );
    }
    
    /**
     * perform mocked calDavRequest
     *
     * @param string $method
     * @param string $uri
     * @param strubg $body
     * @param number $depth
     * @param number $tries
     * @param number $sleep
     * @throws Tinebase_Exception
     */
    public function calDavRequest($method, $uri, $body, $depth = 0, $tries = 10, $sleep = 30)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                 . ' Sending ' . $method . ' request for uri ' . $uri . ': ' . $body);
        
        if ($body == self::findCurrentUserPrincipalRequest) {
            return $this->_currentUserPrincipalResponse;
            
        } else if ($body == self::findCalendarHomeSetRequest) {
            return $this->_calendarHomeSetResponse;
            
        } else if ($body == self::findAllCalendarsRequest) {
            return $this->_findAllCalendarsResponse();
            
        } else if ($body == self::resolvePrincipalRequest) {
            return $this->_resolvePrincipalResponse();
            
        } else if ($body == self::findAllCalendarICSsRequest) {
            return $this->_calendarICSResponse;
            
        } else if (preg_match('/<b:calendar-data \/>/', $body)) {
            return $this->_calendarDataResponse();
            
        } else if (preg_match('/<a:getetag \/>/', $body)) {
            return $this->_calendarEtagResponse();
            
        } else {
            throw new Tinebase_Exception_InvalidArgument('request not supported by mock');
        }
    }
}
