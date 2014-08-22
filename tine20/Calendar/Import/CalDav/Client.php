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
    
    protected $component = 'VEVENT';
    protected $skipComonent = 'VTODO';
    protected $modelName = 'Calendar_Model_Event';
    protected $appName = 'Calendar';
    protected $webdavFrontend = 'Calendar_Frontend_WebDAV_Event';
    
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
        
        $flavor = 'Calendar_Import_CalDav_Decorator_' . $flavor;
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
                    in_array($this->component, $response['{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set']->getValue())) {
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
    
    /**
     * findContainerForCalendar
     * 
     * @param unknown $calendarUri
     * @param unknown $displayname
     * @param unknown $defaultCalendarsName
     * @param unknown $type
     * @param string $application_id
     */
    protected function findContainerForCalendar($calendarUri, 
            $displayname, $defaultCalendarsName, $type = Tinebase_Model_Container::TYPE_PERSONAL,
            $application_id = null)
    {
        if (! $application_id) {
            $application_id = Tinebase_Application::getInstance()->getApplicationByName($this->appName)->getId();
        }
        
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
                'value' => $this->modelName
            ),
        ));
        $existingCalendar = Tinebase_Container::getInstance()->search($filter)->getFirstRecord();
        if ($existingCalendar) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                . ' Found existing container ' . $existingCalendar->name . ' (id: ' . $existingCalendar->getId() . ')');
            return $existingCalendar;
        }
        
        $counter = '';
        
        if ($defaultCalendarsName == $displayname) {
            $existingCalendar = Tinebase_Container::getInstance()->getDefaultContainer($this->modelName);
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
                $existingCalendar = Tinebase_Container::getInstance()->getContainerByName($this->appName, $displayname . $counter, $type, Tinebase_Core::getUser());
                
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
                'model'             => $this->modelName,
                'uuid'              => $uuid
            ));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                    . ' Adding container: ' . $newContainer->name . ' for model ' . $this->modelName);
            
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
        
        Tinebase_Core::getApplicationInstance($this->appName, $this->modelName)->sendNotifications(false);
        Tinebase_Core::getApplicationInstance($this->appName, $this->modelName)->useNotes(false);
        Sabre\VObject\Component\VCalendar::$propertyMap['ATTACH'] = '\\Calendar_Import_CalDav_SabreAttachProperty';
        
        $this->decorator->initCalendarImport();
        
        $defaultCalendarsName = $this->_getDefaultCalendarsName();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
            . ' Calendar uris to import: ' . print_r(array_keys($this->calendars), true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
            . ' Calendars to import: ' . print_r($this->calendars, true));
        
        foreach ($this->calendars as $calUri => $cal) {
            $container = $this->findContainerForCalendar($calUri, $cal['displayname'], $defaultCalendarsName);
            
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
     */
    public function updateAllCalendarData($onlyCurrentUserOrganizer = false)
    {
        if (count($this->calendarICSs) < 1 && ! $this->findAllCalendarICSs()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' no calendars found for: ' . $this->userName);
            return false;
        }
        
        $newICSs = array();
        $newEventCount = 0;
        $updateEventCount = 0;
        $deleteEventCount = 0;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Looking for updates in ' . count($this->calendarICSs). ' calendars ...');
        
        foreach ($this->calendarICSs as $calUri => $calICSs) {
            $updateResult = $this->updateCalendar($calUri, $calICSs);
            if (count($updateResult['ics']) > 0) {
                $newICSs[$calUri] = $updateResult['ics'];
            }
            
            if (! empty($updateResult['todelete'])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Deleting ' . count($updateResult['todelete']) . ' ' . $this->modelName . ' in calendar '  . $calUri);
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                        . ' ' . print_r($updateResult['todelete'], true));
                
                $recordBackend->delete($updateResult['todelete']);
            }
            
            $newEventCount += $updateResult['toadd'];
            $updateEventCount += $updateResult['toupdate'];
            $deleteEventCount += count($updateResult['todelete']);
        }
        
        if (($count = count($newICSs)) > 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' ' 
                    . $count . ' calendar(s) changed for: ' . $this->userName
                    . ' (' . $newEventCount . '/' . $updateEventCount . '/' . $deleteEventCount . ' records add/update/delete): '
                    . print_r(array_keys($newICSs), true));
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' 
                    . 'Events changed: ' . print_r($newICSs, true));
            
            $this->calendarICSs = $newICSs;
            $this->importAllCalendarData($onlyCurrentUserOrganizer, /* $update = */ true);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' no changes found for: ' . $this->userName);
        }
    }
    
    protected function updateCalendar($calUri, $calICSs)
    {
        $updateResult = array(
            'ics'       => array(),
            'toupdate'  => 0,
            'toadd'     => 0,
            'todelete'  => array(), // of record ids
        );
        
        $serverEtags = $this->_fetchServerEtags($calUri, $calICSs);
        
        // get current tine20 id/etags of records
        $recordBackend = Tinebase_Core::getApplicationInstance($this->appName, $this->modelName)->getBackend();
        $defaultCalendarsName = $this->_getDefaultCalendarsName();
        $container = $this->findContainerForCalendar($calUri, $this->calendars[$calUri]['displayname'], $defaultCalendarsName);
        $containerEtags = $recordBackend->getEtagsForContainerId($container->getId());
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                . ' Got ' . count($serverEtags) . ' server etags for container ' . $container->name);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . ' server etags: ' . print_r($serverEtags, true));
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
                . ' tine20 etags: ' . print_r($containerEtags, true));
        
        // handle add/updates
        foreach ($serverEtags as $ics => $data) {
            if (isset($containerEtags[$data['id']])) {
                $tine20Etag = $containerEtags[$data['id']];
        
                // remove from $containerEtags list to be able to tell deletes
                unset($containerEtags[$data['id']]);
        
                if ($tine20Etag == $data['etag']) {
                    continue; // same
                } else if (empty($tine20Etag)) {
                    // event has been added in tine -> don't overwrite/delete
                    continue;
                } else {
                    $eventExists = true; // different
                }
            } else {
                try {
                    // might be a delegated event from another container/organizer
                    $recordBackend->checkETag($data['id'], $data['etag']);
                    continue; // ignore update here
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $eventExists = false; // new record;
                }
            }
        
            if (! isset($this->existingRecordIds[$calUri])) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Found changed event(s) for container ' . $container->name);
        
                $this->existingRecordIds[$calUri] = array();
            }
            $updateResult['ics'][] = $ics;
            if ($eventExists) {
                $this->existingRecordIds[$calUri][] = $data['id'];
                $updateResult['toupdate']++;
            } else {
                $updateResult['toadd']++;
            }
        }
        
        // handle deletes
        foreach ($containerEtags as $id => $etag) {
            if (! empty($etag)) {
                // record has been deleted on server
                $updateResult['todelete'][] = $id;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' '
                        . ' Record has been added in tine: ' . $id);
            }
        }
        
        return $updateResult;
    }
    
    protected function _fetchServerEtags($calUri, $calICSs)
    {
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

        return $etags;
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
        
        Tinebase_Core::getApplicationInstance($this->appName, $this->modelName)->sendNotifications(false);
        Tinebase_Core::getApplicationInstance($this->appName, $this->modelName)->useNotes(false);
        Sabre\VObject\Component\VCalendar::$propertyMap['ATTACH'] = '\\Calendar_Import_CalDav_SabreAttachProperty';
        
        $this->decorator->initCalendarImport();
        
        $application_id = Tinebase_Application::getInstance()->getApplicationByName($this->appName)->getId();
        $type = Tinebase_Model_Container::TYPE_PERSONAL;
        $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer($this->modelName);
        
        $defaultCalendarsName = $this->_getDefaultCalendarsName();
        
        foreach ($this->calendarICSs as $calUri => $calICSs) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                . ' Processing calendar ' . print_r($this->calendars[$calUri], true));
            
            $container = $this->findContainerForCalendar($calUri, $this->calendars[$calUri]['displayname'], $defaultCalendarsName,
                    $type, $application_id);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__
                    . ' User container: ' . print_r($container->toArray(), true));
            
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
                    if (! isset($value['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {
                        continue;
                    }
                    
                    $data = $value['{urn:ietf:params:xml:ns:caldav}calendar-data'];
                    
                    if (strpos($data, 'BEGIN:' . $this->skipComonent) !== false) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO))
                            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Skipping ' . $this->skipComonent);
                        continue;
                    }
                    
                    $name = explode('/', $key);
                    $name = end($name);
                    $id = $this->_getEventIdFromName($name);
                    try {
                        if ($update && in_array($id, $this->existingRecordIds[$calUri])) {
                            $webdavFrontend = new $this->webdavFrontend($container, $id);
                            // @todo move this to separate fn
                            if ($onlyCurrentUserOrganizer && $this->modelName === 'Calendar_Model_Event') {
                                // assert current user is organizer
                                if ($webdavFrontend->getRecord()->organizer && $webdavFrontend->getRecord()->organizer == Tinebase_Core::getUser()->contact_id) {
                                    $webdavFrontend->put($data);
                                } else {
                                    continue;
                                }
                            } else {
                                $webdavFrontend->put($data);
                            }
                            
                        } else {
                            $webdavFrontend = call_user_func_array(array($this->webdavFrontend, 'create'), array(
                                $container,
                                $name,
                                $data,
                                $onlyCurrentUserOrganizer
                            ));
                        }
                        
                        if ($webdavFrontend) {
                            $etags[$webdavFrontend->getRecord()->getId()] = $value['{DAV:}getetag'];
                        }
                    } catch (Exception $e) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not create event from data: ' . $data);
                        Tinebase_Exception::log($e);
                    }
                }
                
                $this->_setEtags($etags);
            } while($start < $max);
        }
        return true;
    }
    
    protected function _setEtags($etags)
    {
        $recordBackend = Tinebase_Core::getApplicationInstance($this->appName, $this->modelName)->getBackend();
        $recordBackend->setETags($etags);
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
        if (count($result['{DAV:}group-member-set']->getPrincipals()) > 0 && isset($result['{DAV:}displayname'])) {
            $groupDescription = $result['{DAV:}displayname'];
            try {
                $group = Tinebase_Group::getInstance()->getGroupByPropertyFromSqlBackend('description',$groupDescription);
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . ' ' . __LINE__
                        . ' Found matching group ' . $group->name . ' (' . $group->description .') for principal ' . $principal);
                Tinebase_Core::getCache()->save($group, $cacheId, array(), /* 1 week */ 24*3600*7);
            } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                        . ' Group not found: ' . $groupDescription . ' ' . print_r($result, true));
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
                    $grants[$user[$i]] = $this->_getAllGrants();
                    break;
                case '{urn:ietf:params:xml:ns:caldav}read-free-busy':
                    $grants[$user[$i]] = $this->_getFreeBusyGrants();
                    break;
                case '{DAV:}read':
                    $grants[$user[$i]] = $this->_getReadGrants();
                    break;
                case '{DAV:}write':
                    $grants[$user[$i]] = $this->_getWriteGrants();
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
    
    protected function _getAllGrants()
    {
        return array(
            Tinebase_Model_Grants::GRANT_READ => true,
            Tinebase_Model_Grants::GRANT_ADD=> true,
            Tinebase_Model_Grants::GRANT_EDIT=> true,
            Tinebase_Model_Grants::GRANT_DELETE=> true,
            Tinebase_Model_Grants::GRANT_EXPORT=> true,
            Tinebase_Model_Grants::GRANT_SYNC=> true,
            Tinebase_Model_Grants::GRANT_ADMIN=> true,
            Tinebase_Model_Grants::GRANT_FREEBUSY=> true,
            Tinebase_Model_Grants::GRANT_PRIVATE=> true,
        );
    }

    protected function _getFreeBusyGrants()
    {
        return array(
            Tinebase_Model_Grants::GRANT_FREEBUSY=> true,
        );
    }

    protected function _getReadGrants()
    {
        return array(
            Tinebase_Model_Grants::GRANT_READ=> true,
            Tinebase_Model_Grants::GRANT_EXPORT=> true,
            Tinebase_Model_Grants::GRANT_SYNC=> true,
            Tinebase_Model_Grants::GRANT_FREEBUSY=> true,
        );
    }

    protected function _getWriteGrants()
    {
        return array(
            Tinebase_Model_Grants::GRANT_READ=> true,
            Tinebase_Model_Grants::GRANT_ADD=> true,
            Tinebase_Model_Grants::GRANT_EDIT=> true,
            Tinebase_Model_Grants::GRANT_DELETE=> true,
        );
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
