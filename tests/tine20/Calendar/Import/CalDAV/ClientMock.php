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
    
    protected $_currentUserPrincipalResponse = array(
        '{DAV:}current-user-principal' => '/principals/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/'
    );
    
    protected $_calendarHomeSetResponse =  array (
        '{urn:ietf:params:xml:ns:caldav}calendar-home-set' => '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0',
    );
    
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
            
            /*
                '/calendars/__uids__/0AA03A3B-F7B6-459A-AB3E-4726E53637D0/inbox/' =>
                array (
                    '{DAV:}resourcetype' => new
                    Sabre\DAV\Property\ResourceType(array(
                         '{DAV:}collection',
                        '{urn:ietf:params:xml:ns:caldav}schedule-inbox',
                    )),
                    '{DAV:}acl' => new Sabre\DAVACL\Property\Acl(
                        array (
                            array (
                                'principal' => '{DAV:}authenticated',
                                'protected' => false,
                                'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-deliver',
                            ),
                            array (
                                'principal' => '{DAV:}authenticated',
                                'protected' => false,
                                'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule',
                            ),
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
                        '{DAV:}displayname' => 'inbox',
                        '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' =>
                        new Sabre\CalDAV\Property\SupportedCalendarComponentSet(array(
                            'components' =>
                            array (
                                0 => 'VEVENT',
                                1 => 'VTODO',
                                2 => 'VTIMEZONE',
                                3 => 'VJOURNAL',
                                4 => 'VFREEBUSY',
                            ),
                        )),
                        ),
                        */
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
            
        } else if ($body == '<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:resourcetype/>
    <d:acl/>
    <d:displayname/>
    <x:supported-calendar-component-set xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  <osxical:calendar-color xmlns:osxical="http://apple.com/ns/ical/"/></d:prop>
</d:propfind>
'/* self::findAllCalendarsRequest */) {
            return $this->_findAllCalendarsResponse();
            
        } else if ($body == self::resolvePrincipalRequest) {
            return $this->_resolvePrincipalResponse();
            
        } else if ($body == self::findAllCalendarICSsRequest) {
            throw new Tinebase_Exception_NotImplemented('findAllCalendarICSsRequest to be implemented');
            
        } else if ($body == self::getAllCalendarDataRequest) {
            throw new Tinebase_Exception_NotImplemented('getAllCalendarDataRequest to be implemented');
            
        } else {
            throw new Tinebase_Exception_InvalidArgument('request not supported by mock');
        }
    }
}
