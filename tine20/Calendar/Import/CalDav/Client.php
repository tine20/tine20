<?php
//./tine20.php --username unittest --method Calendar.importCalDav url="https://osx-testfarm-mavericks-server.hh.metaways.de:8443" caldavuserfile=caldavuserfile.csv

/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar_Import_CalDAV
 * 
 * @package     Calendar
 * @subpackage  Import
 */
class Calendar_Import_CalDav_Client extends Tinebase_Import_CalDav_Client
{
    protected $calendars = array();
    protected $calendarICSs = array();
    protected $existingRecordIds = array();
    protected $maxBulkRequest = 20;
    protected $mapToDefaultContainer = 'calendar';
    protected $decorator = null;
    
    const findAllCalendarsRequest =
'<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <d:resourcetype />
    <d:acl />
    <d:displayname />
    <x:supported-calendar-component-set xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  </d:prop>
</d:propfind>';
    
    const findAllCalendarICSsRequest = 
'<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:">
  <d:prop>
    <x:calendar-data xmlns:x="urn:ietf:params:xml:ns:caldav"/>
  </d:prop>
</d:propfind>';
    
    const getAllCalendarDataRequest =
'<?xml version="1.0"?>
<b:calendar-multiget xmlns:a="DAV:" xmlns:b="urn:ietf:params:xml:ns:caldav">
  <a:prop>
    <b:calendar-data />
    <a:getetag />
  </a:prop>
';
    
    const getEventETagsRequest =
'<?xml version="1.0"?>
<b:calendar-multiget xmlns:a="DAV:" xmlns:b="urn:ietf:params:xml:ns:caldav">
  <a:prop>
    <a:getetag />
  </a:prop>
';
    
    public function __construct(array $a, $flavor)
    {
        parent::__construct($a);
        
        $flavor = 'Calendar_Import_CalDav_Decorator_'.$flavor;
        $this->decorator = new $flavor($this);
    }
    
    public function findAllCalendars()
    {
        if ('' == $this->calendarHomeSet && ! $this->findCalendarHomeSet())
            return false;
        
        //issue with follow location in curl!?!?
        if ($this->calendarHomeSet[strlen($this->calendarHomeSet)-1] !== '/')
            $this->calendarHomeSet .= '/';
        
        try {
            $result = $this->calDavRequest('PROPFIND', $this->calendarHomeSet, $this->decorator->preparefindAllCalendarsRequest(self::findAllCalendarsRequest), 1);
        } catch (Tinebase_Exception $te) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' request failed');
            Tinebase_Exception::log($te);
            return false;
        }
        
        foreach ($result as $uri => $response) {
            if (isset($response['{DAV:}resourcetype']) && isset($response['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']) && 
                    $response['{DAV:}resourcetype']->is('{urn:ietf:params:xml:ns:caldav}calendar') &&
                    in_array('VEVENT', $response['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue())) {
                $this->calendars[$uri]['acl'] = $response['{DAV:}acl'];
                $this->calendars[$uri]['displayname'] = $response['{DAV:}displayname'];
                $this->decorator->processAdditionalCalendarProperties($this->calendars[$uri], $response);
                $this->resolvePrincipals($this->calendars[$uri]['acl']->getPrivileges());
            }
        }
        
        if (count($this->calendars) > 0) {
            return true;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' couldn\'t find a calendar');
            return false;
        }
    }
    
    public function findAllCalendarICSs()
    {
        if (count($this->calendars) < 1 && ! $this->findAllCalendars())
            return false;
        
        foreach ($this->calendars as $calUri => $calendar) {
            $result = $this->calDavRequest('PROPFIND', $calUri, self::findAllCalendarICSsRequest, 1);
            foreach ($result as $ics => $value) {
                if (strpos($ics, '.ics') !== FALSE)
                    $this->calendarICSs[$calUri][] = $ics;
            }
        }
        
        if (count($this->calendarICSs) > 0) {
            return true;
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' all found calendars are empty');
            return false;
        }
    }
    
    protected function findContainerForCalendar($calendarUri, $displayname, $defaultCalendarsName, $type, $application_id, $modelName)
    {
        // sha1() the whole calendar uri as it is very hard to separate a uuid string from the uri otherwise
        $uuid = sha1($calendarUri);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                . ' $calendarUri = ' . $calendarUri . ' / $displayname = ' . $displayname 
                . ' / $defaultCalendarsName = ' . $defaultCalendarsName . ' / $uuid = ' . $uuid);
        
        $filter = new Tinebase_Model_ContainerFilter(array(
            array(
                'field' => 'uuid', 
                'operator' => 'equals', 
                'value' => $uuid
            ),
            array(
                'field' => 'model', 
                'operator' => 'equals', 
                'value' => 'Calendar_Model_Event'
            ),
        ));
        $existingCalendar = Tinebase_Container::getInstance()->search($filter, null, false, false, 'sync')->getFirstRecord();
        if ($existingCalendar) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                . ' Found existing calendar ' . $existingCalendar->name . ' (id: ' . $existingCalendar->getId() . ')');
            return $existingCalendar;
        }
        
        $counter = '';
        
        if ($defaultCalendarsName == $displayname) {
            $existingCalendar = Tinebase_Container::getInstance()->getDefaultContainer('Calendar_Model_Event');
            if (! $existingCalendar->uuid) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' Found existing calendar with the same name');
                $existingCalendar->uuid = $uuid;
                return $existingCalendar;
            }
            $existingCalendar = null;
            $counter = 1;
        }
        
        try {
            while (true) {
                $existingCalendar = Tinebase_Container::getInstance()->getContainerByName('Calendar', $displayname . $counter, $type, Tinebase_Core::getUser());
                
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                    . ' Got calendar: ' . $existingCalendar->name . ' (id: ' . $existingCalendar->getId() . ')');
                
                if (! $existingCalendar->uuid) {
                    $existingCalendar->uuid = $uuid;
                    return $existingCalendar;
                }
                $counter += 1;
            }
        } catch (Tinebase_Exception_NotFound $e) {
            $newContainer = new Tinebase_Model_Container(array(
                'name'              => $displayname . $counter,
                'type'              => $type,
                'backend'           => 'Sql',
                'application_id'    => $application_id,
                'model'             => $modelName,
                'uuid'              => $uuid
            ));
            return Tinebase_Container::getInstance()->addContainer($newContainer);
        }
    }
    
    public function importAllCalendars()
    {
        if (count($this->calendars) < 1 && ! $this->findAllCalendars()) {
            return false;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__ 
            . ' Importing all calendars for user ' . $this->userName);
        
        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        Calendar_Controller_Event::getInstance()->useNotes(false);
        Sabre\VObject\Component\VCalendar::$propertyMap['ATTACH'] = '\\Calendar_Import_CalDav_SabreAttachProperty';
        
        $this->decorator->initCalendarImport();
        
        $modelName = Tinebase_Core::getApplicationInstance('Calendar')->getDefaultModel();
        $application_id = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId();
        $type = Tinebase_Model_Container::TYPE_PERSONAL;
        
        $defaultCalendarsName = $this->_getDefaultCalendarsName();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
            . ' Calendar uris to import: ' . print_r(array_keys($this->calendars), true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
            . ' Calendars to import: ' . print_r($this->calendars, true));
        
        foreach ($this->calendars as $calUri => $cal) {
            $container = $this->findContainerForCalendar($calUri, $cal['displayname'], $defaultCalendarsName,
                    $type, $application_id, $modelName);
            
            $this->decorator->setCalendarProperties($container, $this->calendars[$calUri]);
            
            $grants = $this->getCalendarGrants($calUri);
            Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, TRUE, FALSE);
        }
    }
    
    /**
     * decide which calendar to use as default calendar
     * if there is a remote default calendar, use that. If not, use the first we find
     * 
     * @return string
     */
    protected function _getDefaultCalendarsName() 
    {
        $defaultCalendarsName = '';
        foreach ($this->calendarICSs as $calUri => $calICSs) {
            if ($this->mapToDefaultContainer == $this->calendars[$calUri]['displayname']) {
                return $this->calendars[$calUri]['displayname'];
            } elseif ($defaultCalendarsName === '') {
                $defaultCalendarsName = $this->calendars[$calUri]['displayname'];
            }
        }
        return $defaultCalendarsName;
    }
    
    /**
     * 
     * @param string $onlyCurrentUserOrganizer
     * @return boolean
     * 
     * @todo check if $onlyCurrentUserOrganizer is needed
     * @todo check deletes
     */
    public function updateAllCalendarData($onlyCurrentUserOrganizer = false)
    {
        if (count($this->calendarICSs) < 1 && ! $this->findAllCalendarICSs()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' no calendars found for: ' . $this->userName);
            return false;
        }
        
        $newICSs = array();
        $calendarEventBackend = Calendar_Controller_Event::getInstance()->getBackend();
        
        foreach ($this->calendarICSs as $calUri => $calICSs) {
            $start = 0;
            $max = count($calICSs);
            $etags = array();
            do {
                $requestEnd = '';
                for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                    $requestEnd .= '  <a:href>' . $calICSs[$i] . "</a:href>\n";
                }
                $start = $i;
                $requestEnd .= '</b:calendar-multiget>';
                $result = $this->calDavRequest('REPORT', $calUri, self::getEventETagsRequest . $requestEnd, 1);
                
                foreach ($result as $key => $value) {
                    if (isset($value['{DAV:}getetag'])) {
                        $name = explode('/', $key);
                        $name = end($name);
                        $id = $this->_getEventIdFromName($name);
                        $etags[$key] = array( 'id' => $id, 'etag' => $value['{DAV:}getetag']);
                    }
                }
            } while($start < $max);
            
            //check etags
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' Got ' . count($etags) . ' etags');
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . ' etags: ' . print_r($etags, true));
            
            // @todo find out deleted events
            foreach ($etags as $ics => $data) {
                try {
                    $etagCheck = $calendarEventBackend->checkETag($data['id'], $data['etag']);
                    if ($etagCheck) {
                        continue; // same
                    } else {
                        $eventExists = true; // different
                    }
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $eventExists = false;
                }
                
                if (!isset($newICSs[$calUri])) {
                    $newICSs[$calUri] = array();
                    $this->existingRecordIds[$calUri] = array();
                }
                $newICSs[$calUri][] = $ics;
                if ($eventExists) {
                    $this->existingRecordIds[$calUri][] = $data['id'];
                }
            }
        }
        
        if (($count = count($newICSs)) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' 
                    . $count . ' calendar(s) changed for: ' . $this->userName);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' 
                    . ' events changed: ' . print_r($newICSs, true));
            $this->calendarICSs = $newICSs;
            $this->importAllCalendarData($onlyCurrentUserOrganizer, /* $update = */ true);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' no changes found for: ' . $this->userName);
        }
    }
    
    protected function _getEventIdFromName($name)
    {
        $id = ($pos = strpos($name, '.')) === false ? $name : substr($name, 0, $pos);
        if (strlen($id) > 40) {
            $id = sha1($id);
        }
        return $id;
    }
    
    public function importAllCalendarData($onlyCurrentUserOrganizer = false, $update = false)
    {
        if (count($this->calendarICSs) < 1 && ! $this->findAllCalendarICSs()) {
            return false;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
            . ' Importing all calendar data for user ' . $this->userName . ' with ics uris: ' . print_r(array_keys($this->calendarICSs), true));
        
        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        Calendar_Controller_Event::getInstance()->useNotes(false);
        Sabre\VObject\Component\VCalendar::$propertyMap['ATTACH'] = '\\Calendar_Import_CalDav_SabreAttachProperty';
        
        $this->decorator->initCalendarImport();
        
        $modelName = Tinebase_Core::getApplicationInstance('Calendar')->getDefaultModel();
        $application_id = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId();
        $type = Tinebase_Model_Container::TYPE_PERSONAL; //Tinebase_Model_Container::TYPE_SHARED;
        $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer('Calendar_Model_Event');
        $calendarEventBackend = Calendar_Controller_Event::getInstance()->getBackend();
        
        $defaultCalendarsName = $this->_getDefaultCalendarsName();
        
        foreach ($this->calendarICSs as $calUri => $calICSs) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' Processing calendar ' . print_r($this->calendars[$calUri], true));
            
            $container = $this->findContainerForCalendar($calUri, $this->calendars[$calUri]['displayname'], $defaultCalendarsName,
                    $type, $application_id, $modelName);
            
            $this->decorator->setCalendarProperties($container, $this->calendars[$calUri]);
            
            // we shouldnt do the grants here as the caldav user file may not contain all users, so setting the grants wont work properly!
            // use importAllCalendars to have the grants set
            //$grants = $this->getCalendarGrants($calUri);
            //Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, TRUE, FALSE);
            
            $start = 0;
            $max = count($calICSs);
            do {
                $etags = array();
                $requestEnd = '';
                for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                    $requestEnd .= '  <a:href>' . $calICSs[$i] . "</a:href>\n";
                }
                $start = $i;
                $requestEnd .= '</b:calendar-multiget>';
                $result = $this->calDavRequest('REPORT', $calUri, self::getAllCalendarDataRequest . $requestEnd, 1);
                
                foreach ($result as $key => $value) {
                    if (isset($value['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {
                        $data = $value['{urn:ietf:params:xml:ns:caldav}calendar-data'];
                        $name = explode('/', $key);
                        $name = end($name);
                        $id = $this->_getEventIdFromName($name);
                        try {
                            if ($update && in_array($id, $this->existingRecordIds[$calUri])) {
                                $event = new Calendar_Frontend_WebDAV_Event($container, $id);
                                if ($onlyCurrentUserOrganizer) {
                                    // assert current user is organizer
                                    if ($event->getRecord()->organizer && $event->getRecord()->organizer == Tinebase_Core::getUser()->contact_id) {
                                        $event->put($data);
                                    } else {
                                        continue;
                                    }
                                } else {
                                    $event->put($data);
                                }
                                
                            } else {
                                $event = Calendar_Frontend_WebDAV_Event::create(
                                    $container,
                                    $name,
                                    $data,
                                    $onlyCurrentUserOrganizer
                                );
                            }
                            
                            if ($event) {
                                $etags[$event->getRecord()->getId()] = $value['{DAV:}getetag'];
                            }
                        } catch (Exception $e) {
                            // don't warn on VTODOs
                            if (strpos($data, 'BEGIN:VTODO') !== false) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Skipping VTODO');
                            } else {
                                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not create event from data: ' . $data);
                                Tinebase_Exception::log($e);
                            }
                        }
                    }
                }
                
                $calendarEventBackend->setETags($etags);
            } while($start < $max);
        }
        return true;
    }
    
    /**
     * get Tine 2.0 group for given principal (by display name)
     * - result is cached for 1 week
     * 
     * @param string $principal
     * @return null|Tinebase_Model_Group
     */
    protected function _getGroupForPrincipal($principal)
    {
        $cacheId = convertCacheId('_getGroupForPrincipal' . $principal);
        if (Tinebase_Core::getCache()->test($cacheId)) {
            $group = Tinebase_Core::getCache()->load($cacheId);
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                    . ' Loading principal group from cache: ' . $group->name);
            return $group;
        }
        
        $group = null;
        
        $result = $this->calDavRequest('PROPFIND', $principal, self::resolvePrincipalRequest);
        if (isset($result['{DAV:}group-member-set']) && isset($result['{DAV:}displayname'])) {
            $groupDescription = $result['{DAV:}displayname'];
            try {
                $group = Tinebase_Group::getInstance()->getGroupByPropertyFromSqlBackend('description',$groupDescription);
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                        . ' Found matching group ' . $group->name . ' (' . $group->description .') for principal ' . $principal);
                Tinebase_Core::getCache()->save($group, $cacheId, array(), /* 1 week */ 24*3600*7);
            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                        . ' Group not found: ' . $groupDescription);
            }
        }
        
        return $group;
    }
    
    /**
     * get grants for cal uri
     * 
     * @param string $calUri
     * @return Tinebase_Record_RecordSet
     */
    public function getCalendarGrants($calUri)
    {
        $grants = array();
        $user = array();
        $type = array();
        $privilege = array();
        foreach ($this->calendars[$calUri]['acl']->getPrivileges() as $ace)
        {
            if ('{DAV:}authenticated' == $ace['principal']) {
                $user[] = 0;
                $type[] = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
                $privilege[] = $ace['privilege'];
            } elseif (isset($this->principals[$ace['principal']])) {
                $user[] = $this->principals[$ace['principal']]->getId();
                $type[] = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
                $privilege[] = $ace['privilege'];
            } elseif (isset($this->principalGroups[$ace['principal']])) {
                foreach($this->principalGroups[$ace['principal']] as $principal) {
                    if ('{DAV:}authenticated' == $principal) {
                        $user[] = 0;
                        $type[] = Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE;
                        $privilege[] = $ace['privilege'];
                    } elseif (isset($this->principals[$principal])) {
                        $user[] = $this->principals[$principal]->getId();
                        $type[] = Tinebase_Acl_Rights::ACCOUNT_TYPE_USER;
                        $privilege[] = $ace['privilege'];
                    } else {
                        $group = $this->_getGroupForPrincipal($principal);
                        if ($group) {
                            $user[] = $group->getId();
                            $type[] = Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP;
                            $privilege[] = $ace['privilege'];
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ 
                                    . ' There is an unresolved principal: ' . $principal . ' in group: ' . $ace['principal']);
                        }
                    }
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Couldn\'t resolve principal: '.$ace['principal']);
            }
        }
        for ($i=0; $i<count($user); ++$i) {
            switch ($privilege[$i]) {
                case '{DAV:}all':
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_READ ] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_ADD] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_EDIT] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_DELETE] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_EXPORT] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_SYNC] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_ADMIN] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_FREEBUSY] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_PRIVATE] = true;
                    break;
                case '{urn:ietf:params:xml:ns:caldav}read-free-busy':
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_FREEBUSY] = true;
                    break;
                case '{DAV:}read':
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_READ] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_EXPORT] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_SYNC] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_FREEBUSY] = true;
                    break;
                case '{DAV:}write':
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_ADD] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_EDIT] = true;
                    $grants[$user[$i]][Tinebase_Model_Grants::GRANT_DELETE] = true;
                    break;
                case '{DAV:}read-current-user-privilege-set':
                    continue;
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' unknown privilege: ' . $privilege[$i]);
                    continue;
            }
            $grants[$user[$i]]['account_id'] = $user[$i];
            $grants[$user[$i]]['account_type'] = $type[$i];
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' found ' . count($grants) . ' grants for calendar: ' . $calUri);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' grants: ' . print_r($grants, true));
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $grants, TRUE);
    }
    
    public function updateAllCalendarDataForUsers(array $users)
    {
        $result = true;
        // first only update/import events where the current user is also the organizer
        foreach ($users as $username => $pwd) {
            $this->clearCurrentUserCalendarData();
            $this->userName = $username;
            $this->password = $pwd;
            if (!$this->updateAllCalendarData(true)) {
                $result = false;
            }
        }
        // then update all events again
        foreach ($users as $username => $pwd) {
            $this->clearCurrentUserCalendarData();
            $this->userName = $username;
            $this->password = $pwd;
            if (!$this->updateAllCalendarData(false)) {
                $result = false;
            }
        }
        return $result;
    }
    
    public function importAllCalendarDataForUsers(array $users)
    {
        $result = true;
        // first only import events where the current user is also the organizer
        foreach ($users as $username => $pwd) {
            $this->clearCurrentUserCalendarData();
            $this->userName = $username;
            $this->password = $pwd;
            if (!$this->importAllCalendarData(true)) {
                $result = false;
            }
        }
        // then import all events again
        foreach ($users as $username => $pwd) {
            $this->clearCurrentUserCalendarData();
            $this->userName = $username;
            $this->password = $pwd;
            if (!$this->importAllCalendarData(false)) {
                $result = false;
            }
        }
        return $result;
    }
    
    public function importAllCalendarsForUsers(array $users)
    {
        if (!$this->findCurrentUserPrincipalForUsers($users)) {
            return false;
        }
        
        $result = true;
        foreach ($users as $username => $pwd) {
            $this->clearCurrentUserCalendarData();
            $this->userName = $username;
            $this->password = $pwd;
            if (!$this->importAllCalendars()) {
                $result = false;
            }
        }
        return $result;
    }
    
    public function clearCurrentUserCalendarData()
    {
        $this->clearCurrentUserData();
        $this->calendars = array();
        $this->calendarICSs = array();
    }
}
