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
 * 
 * @todo        
 */

/**
 * Calendar_Import_CalDAV
 * 
 * @package     Calendar
 * @subpackage  Import
 * 
 */
class Calendar_Import_CalDav_Client extends Tinebase_Import_CalDav_Client
{
    protected $calendars = array();
    protected $calendarICSs = array();
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
        
        $result = $this->calDavRequest('PROPFIND', $this->calendarHomeSet, $this->decorator->preparefindAllCalendarsRequest(self::findAllCalendarsRequest), 1);
        
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
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' all found calendars are empty');
            return false;
        }
    }
    
    protected function findContainerForCalendar($calendarUri, $displayname, $defaultCalendarsName, $type, $application_id, $modelName)
    {
        $uuid = basename($calendarUri);
        
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
        if($existingCalendar) {
            return $existingCalendar;
        }
        
        $counter = '';
        
        if ($defaultCalendarsName == $displayname) {
            $existingCalendar = Tinebase_Container::getInstance()->getDefaultContainer('Calendar_Model_Event');
            if (! $existingCalendar->uuid) {
                $existingCalendar->uuid = $uuid;
                return $existingCalendar;
            }
            $existingCalendar = null;
            $counter = 1;
        }
        
        try {
            while (true) {
                $existingCalendar = Tinebase_Container::getInstance()->getContainerByName('Calendar', $displayname . $counter, $type, Tinebase_Core::getUser());
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
    
    public function importAllCalendarData($onlyCurrentUserOrganizer = false)
    {
        if (count($this->calendarICSs) < 1 && ! $this->findAllCalendarICSs())
            return false;
        
        Calendar_Controller_Event::getInstance()->sendNotifications(false);
        Sabre\VObject\Component\VCalendar::$propertyMap['ATTACH'] = '\\Calendar_Import_CalDav_SabreAttachProperty';
        
        $this->decorator->initCalendarImport();
        
        $modelName = Tinebase_Core::getApplicationInstance('Calendar')->getDefaultModel();
        $application_id = Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId();
        $type = Tinebase_Model_Container::TYPE_PERSONAL; //Tinebase_Model_Container::TYPE_SHARED;
        $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer('Calendar_Model_Event');
        
        //decide which calendar to use as default calendar
        //if there is a remote default calendar, use that. If not, use the first we find
        $defaultCalendarsName = '';
        foreach ($this->calendarICSs as $calUri => $calICSs) {
            if ($this->mapToDefaultContainer == $this->calendars[$calUri]['displayname']) {
                $container = Tinebase_Container::getInstance()->getDefaultContainer('Calendar_Model_Event');
            } elseif ($defaultsCalendarsName === '') {
                $defaultCalendarsName = $this->calendars[$calUri]['displayname'];
            }
        }
        
        foreach ($this->calendarICSs as $calUri => $calICSs) {
            $container = $this->findContainerForCalendar($calUri, $this->calendars[$calUri]['displayname'], $defaultCalendarsName,
                    $type, $application_id, $modelName);
            
            $this->decorator->setCalendarProperties($container, $this->calendars[$calUri]);
            
            $grants = $this->getCalendarGrants($calUri);
            Tinebase_Container::getInstance()->setGrants($container->getId(), $grants, TRUE, FALSE);
            
            $start = 0;
            $max = count($calICSs);
            do {
                $requestEnd = '';
                for ($i = $start; $i < $max && $i < ($this->maxBulkRequest+$start); ++$i) {
                    $requestEnd .= '  <a:href>' . $calICSs[$i] . "</a:href>\n";
                }
                $start = $i;
                $requestEnd .= '</b:calendar-multiget>';
                $result = $this->calDavRequest('REPORT', $calUri, self::getAllCalendarDataRequest . $requestEnd, 1);
                
                foreach ($result as $key => $value) {
                    if (isset($value['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {
                        $name = explode('/', $key);
                        $name = end($name);
                        try {
                            Calendar_Frontend_WebDAV_Event::create(
                                $container,
                                $name,
                                $value['{urn:ietf:params:xml:ns:caldav}calendar-data'],
                                $onlyCurrentUserOrganizer
                            );
                        } catch(Tinebase_Exception_UnexpectedValue $e) {
                            if ('no vevents found' != $e->getMessage()) {
                                throw $e;
                            }
                        }
                    }
                }
            } while($start < $max);
        }
        return true;
    }
    
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
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' there is an unresolved principal: ' . $principal . ' in group: ' . $ace['principal']);
                    }
                }
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' couldn\'t resolve principal: '.$ace['principal']);
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
        if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' found grants: ' . print_r($grants, true) . ' for calendar: ' . $calUri);
        
        return new Tinebase_Record_RecordSet('Tinebase_Model_Grants', $grants, TRUE);
    }
    
    public function importAllCalendarDataForUsers(array $users)
    {
        if (!$this->findCurrentUserPrincipalForUsers($users))
            return false;
        
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
    
    public function clearCurrentUserCalendarData()
    {
        $this->clearCurrentUserData();
        $this->calendars = array();
        $this->calendarICSs = array();
    }
}