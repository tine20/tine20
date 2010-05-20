<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 */

/**
 * class to import calendars/events from egw14
 * 
 * @todo find out organizer tz
 * 
 * @package     Calendar
 * @subpackage  Setup
 */
class Calendar_Setup_Import_Egw14 extends Tinebase_Setup_Import_Egw14_Abstract {
    
    /**
     * @var Zend_Date
     */
    protected $_migrationStartTime = NULL;
    
    /**
     * in class calendar/container cache
     * 
     * @var array
     */
    protected $_personalCalendarCache = array();
    
    /**
     * in class calendar/container cache
     * 
     * @var array
     */
    protected $_privateCalendarCache = array();
    
    /**
     * maps egw attender status to tine attender status
     * 
     * @var array
     */
    protected $_attenderStatusMap = array(
        'U' => Calendar_Model_Attender::STATUS_NEEDSACTION,
        'A' => Calendar_Model_Attender::STATUS_ACCEPTED,
        'R' => Calendar_Model_Attender::STATUS_DECLINED,
        'T' => Calendar_Model_Attender::STATUS_TENTATIVE
    );
    
    /**
     * maps egw/mcal recur freqs to tine/ical requr freqs
     * 
     * @var array
     */
    protected $_rruleFreqMap = array(
        1 => Calendar_Model_Rrule::FREQ_DAILY,
        2 => Calendar_Model_Rrule::FREQ_WEEKLY,
        3 => Calendar_Model_Rrule::FREQ_MONTHLY,
        4 => Calendar_Model_Rrule::FREQ_MONTHLY,
        5 => Calendar_Model_Rrule::FREQ_YEARLY,
    );
    
    /**
     * maps egw/mcal recur wdays to tine/ical recur wdays
     * 
     * @var array (bitmap)
     */
    protected $_rruleWdayMap = array(
         1 => Calendar_Model_Rrule::WDAY_SUNDAY,
         2 => Calendar_Model_Rrule::WDAY_MONDAY,
         4 => Calendar_Model_Rrule::WDAY_TUESDAY,
         8 => Calendar_Model_Rrule::WDAY_WEDNESDAY,
        16 => Calendar_Model_Rrule::WDAY_THURSDAY,
        32 => Calendar_Model_Rrule::WDAY_FRIDAY,
        64 => Calendar_Model_Rrule::WDAY_SATURDAY,
    );
    
    /**
     * constructs a calendar import for egw14 data
     * 
     * @param Zend_Db_Adapter_Abstract  $_egwDb
     * @param Zend_Config               $_config
     * @param Zend_Log                  $_log
     */
    public function __construct($_egwDb, $_config, $_log)
    {
        parent::__construct($_egwDb, $_config, $_log);
        
        $this->_migrationStartTime = Zend_Date::now();
        $this->_calEventBackend = new Calendar_Backend_Sql();
        
        /*
        $tineDb = Tinebase_Core::getDb();
        Tinebase_TransactionManager::getInstance()->startTransaction($tineDb);
        */
        
        $estimate = $this->_getEgwEventsCount();
        $this->_log->info("found {$estimate} events for migration");
        
        $pageSize = 100;
        $numPages = ceil($estimate/$pageSize);
        
        for ($page=1; $page <= $numPages; $page++) {
            $this->_log->info("starting migration page {$page} of {$numPages}");
            
            // NOTE: recur events with lots of exceptions might consume LOTS of time!
            Tinebase_Core::setExecutionLifeTime($pageSize*10);
            
            $eventPage = $this->_getRawEgwEventPage($page, $pageSize);
            $this->_migrateEventPage($eventPage);
        }
    }
    
    /**
     * migrates given event page
     * 
     * @param array $_eventPage
     * @return void
     */
    public function _migrateEventPage($_eventPage)
    {
            foreach ($_eventPage as $egwEventData) {
            try {
                $event = $this->_getTineEventRecord($egwEventData);
                $event->attendee = $this->_getEventAttendee($egwEventData);
                
                if ($event->rrule) {
                    $exceptions = $this->_getRecurExceptions($egwEventData['cal_id']);
                    $exceptions->merge($this->_getRecurImplicitExceptions($egwEventData));
                    
                    foreach ($exceptions as $exception) {
                        $exception['exdate'] = NULL;
                        $exception['rrule'] = NULL;
                        
                        $exception->uid = $event->uid;
                        $exception->setRecurId();
                        
                        $exdateKey = array_search($exception->dtstart, $event->exdate);
                        if ($exdateKey !== FALSE) {
                            $this->_log->debug("removing persistent exception at {$exception->dtstart} from exdate of {$event->getId()}");
                            unset($event->exdate[$exdateKey]);
                        }
                        
                        if (count($exception->alarms == 0) && count($event->alarms > 0)) {
                            $exception->alarms = clone $event->alarms;
                        }
                        
                        $this->_saveTineEvent($exception);
                    }
                    
                    $nextOccurrence = Calendar_Model_Rrule::computeNextOccurrence($event, $exceptions, $this->_migrationStartTime);
                    $event->alarms->setTime($nextOccurrence->dtstart);
                }
                
                // save baseevent 
                $this->_saveTineEvent($event);
                

            } catch (Exception $e) {
                $this->_log->err('could not migrate event "' . $egwEventData['cal_id'] . '" cause: ' . $e->getMessage());
            }
            
        }
    }
    
    protected function _getTineEventRecord($_egwEventData)
    {
        // basic datas
        $tineEventData = array(
            'id'                => $_egwEventData['cal_id'],
            'uid'               => substr($_egwEventData['cal_uid'], 0, 40),
            'creation_time'     => $_egwEventData['cal_modified'],
            'created_by'        => $this->mapAccountIdEgw2Tine($_egwEventData['cal_modifier']),
            // 'tags'
            'dtstart'           => $_egwEventData['cal_start'],
            'dtend'             => $_egwEventData['cal_end'],
            'is_all_day_event'  => ($_egwEventData['cal_end'] - $_egwEventData['cal_start']) % 86400 == 86399,
            'summary'           => $_egwEventData['cal_title'],
            'description'       => $_egwEventData['cal_description'],
            'location'          => $_egwEventData['cal_location'],
            //'organizer'         => $_egwEventData['cal_owner'], NOTE we would need contact id here
            'transp'            => $_egwEventData['cal_non_blocking'] ? Calendar_Model_Event::TRANSP_TRANSP : Calendar_Model_Event::TRANSP_OPAQUE,
            'priority'          => $this->getPriority($_egwEventData['cal_priority']),
            // 'class'
        );
        
        // TODO: figure out users tz
        $tineEventData['originator_tz'] = 'Europe/Berlin';
        
        // find calendar
        $tineEventData['container_id'] = $_egwEventData['cal_public'] ? 
            $this->_getPersonalCalendar($this->mapAccountIdEgw2Tine($_egwEventData['cal_owner']))->getId() :
            $this->_getPrivateCalendar($this->mapAccountIdEgw2Tine($_egwEventData['cal_owner']))->getId();

        // handle recuring
        if ($_egwEventData['rrule']) {
            $tineEventData['rrule'] = $this->_convertRrule($_egwEventData);
            $tineEventData['exdate'] = $_egwEventData['rrule']['recur_exception'];
        }
        
        // handle alarms
        $tineEventData['alarms'] = $this->_convertAlarms($_egwEventData);
        
        // finally create event record
        date_default_timezone_set($this->_config->egwServerTimezone);
        $tineEvent = new Calendar_Model_Event($tineEventData, FALSE, Zend_Date::TIMESTAMP);
        
        $tineEvent->dateConversionFormat = Calendar_Model_Event::ISO8601LONG;
        date_default_timezone_set('UTC');
        
        return $tineEvent;
    }
    
    /**
     * saves an event to tine db
     * 
     * @param Calendar_Model_Event $_event
     * @return void
     */
    protected function _saveTineEvent($_event)
    {
        $savedEvent = $this->_calEventBackend->create($_event);
        $_event->attendee->cal_event_id = $savedEvent->getId();
        foreach ($_event->attendee as $attender) {
            $this->_calEventBackend->createAttendee($attender);
        }
        
        // handle alarms
        foreach ($_event->alarms as $alarm) {
            $alarm->record_id = $savedEvent->getId();
            $alarm->model     = 'Calendar_Model_Event';
            $alarm->options   = Zend_Json::encode(array(
                'minutes_before' => $alarm->minutes_before,
                'recurid'        => $_event->recurid
            ));
            
            // NOTE: alarm_time for recur base events is already set at this point
            if(! $alarm->alarm_time instanceof Zend_Date) {
                $alarm->setTime($_event->dtstart);
            }
            
            if ($alarm->alarm_time->isEarlier($this->_migrationStartTime)) {
                $this->_log->debug('skipping alarm for event ' . $_event->getId() . ' at ' . $alarm->alarm_time . ' as it is in the past');
                continue;
            }
            
            // save alarm
            Tinebase_Alarm::getInstance()->create($alarm);
        }
    }
    
    /**
     * converts egw alarms into tine alarms
     * 
     * @param  array $_egwEventData
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Alarm
     */
    protected function _convertAlarms($_egwEventData)
    {
        $alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        $select = $this->_egwDb->select()
            ->from(array('alarms' => 'egw_async'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('async_id') . ' LIKE ?', "cal:{$_egwEventData['cal_id']}:%"));
        
        $egwAlarms = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        
        if (count($egwAlarms) == 0) {
            return $alarms;
        }
        
        foreach ($egwAlarms as $egwAlarm) {
            $egwAlarmData = unserialize($egwAlarm['async_data']);
            
            $alarms->addRecord(new Tinebase_Model_Alarm(array(
                'minutes_before' => $egwAlarmData['offset']/60
            ), TRUE));
        }
        
        // at the moment tine only handles one alarm
        if (count($alarms) > 1) {
            $this->_log->warn('only one alarm of event ' . $_egwEventData['cal_id'] . ' got migrated');
            
            $alarms->sort('minutes_before', 'ASC');
            $alarm = $alarms->getFirstRecord();
            
            $alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm', array($alarm));
        }
        
        return $alarms;
    }
    
    /**
     * converts egw rrule into tine/iCal rrule
     * 
     * @param  array $_egwEventData
     * @return Calendar_Model_Rrule
     */
    protected function _convertRrule($_egwEventData)
    {
        $egwRrule = $_egwEventData['rrule'];
        
        $rrule = new Calendar_Model_Rrule(array());
        
        if (! array_key_exists($egwRrule['recur_type'], $this->_rruleFreqMap)) {
            throw new Exception('unsupported rrule freq');
        }
        
        $rrule->freq        = $this->_rruleFreqMap[$egwRrule['recur_type']];
        $rrule->interval    = $egwRrule['recur_interval'];
        $rrule->until       = $this->convertDate($egwRrule['recur_enddate']);
        
        // weekly/monthly by wday
        if ($egwRrule['recur_type'] == 2 || $egwRrule['recur_type'] == 3) {
            $wdays = array();
            foreach($this->_rruleWdayMap as $egwBit => $iCalString) {
                if ($egwRrule['recur_data'] & $egwBit) {
                    $wdays[] = $iCalString;
                }
            }
            
            $rrule->byday = implode(',', $wdays);
        }
        
        // monthly byday/yearly bymonthday
        if ($egwRrule['recur_type'] == 4 || $egwRrule['recur_type'] == 5) {
            $dtstart = $this->convertDate($_egwEventData['cal_start']);
            $dateArray = Calendar_Model_Rrule::date2array($dtstart);
            
            $rrule->bymonthday = $dateArray['day'];
            
            if ($egwRrule['recur_type'] == 5) {
                $rrule->bymonth    = $dateArray['month'];
            }
        }
        
        return $rrule;
    }
    
    protected function _getEventAttendee($_egwEventData)
    {
        $tineAttendee = new Tinebase_Record_RecordSet('Calendar_Model_Attender');
        foreach ($_egwEventData['attendee'] as $idx => $egwAttender) {
            try {
                $tineAttenderArray = array(
                    'quantity'          => $egwAttender['cal_quantity'],
                    'role'              => Calendar_Model_Attender::ROLE_REQUIRED,
                    'status'            => array_key_exists($egwAttender['cal_status'], $this->_attenderStatusMap) ? 
                                               $this->_attenderStatusMap[$egwAttender['cal_status']] : 
                                               Calendar_Model_Attender::STATUS_NEEDSACTION,
                    'status_authkey'    => Calendar_Model_Attender::generateUID(),
                );
                
                switch($egwAttender['cal_user_type']) {
                    case 'u':
                        // user and group
                        if ($egwAttender['cal_user_id'] > 0) {
                            $tineAttenderArray['user_type'] = Calendar_Model_Attender::USERTYPE_USER;
                            $tineAttenderArray['user_id']   = Tinebase_User::getInstance()->getUserById($this->mapAccountIdEgw2Tine($egwAttender['cal_user_id']))->contact_id;
                            
                            $tineAttenderArray['displaycontainer_id'] = $_egwEventData['cal_public'] ? 
                                $this->_getPersonalCalendar($this->mapAccountIdEgw2Tine($egwAttender['cal_user_id']))->getId() :
                                $this->_getPrivateCalendar($this->mapAccountIdEgw2Tine($egwAttender['cal_user_id']))->getId();
                        
                        } else {
                            $tineAttenderArray['user_type'] = Calendar_Model_Attender::USERTYPE_GROUP;
                            $tineAttenderArray['user_id']   = $this->mapAccountIdEgw2Tine($egwAttender['cal_user_id']);
                        }
                        break;
                    case 'c':
                        // try to find contact in tine (NOTE: id is useless, as contacts get new ids during migration)
                        $contact_id = $this->_getContactIdByEmail($egwAttender['email'], $this->mapAccountIdEgw2Tine($_egwEventData['cal_owner']));
                        if (! $contact_id) {
                            continue 2;
                        }
                        
                        $tineAttenderArray['user_type'] = Calendar_Model_Attender::USERTYPE_USER;
                        $tineAttenderArray['user_id']   = $contact_id;
                        break;
                        
                    case 'r':
                        $resource_id = $this->_getResourceId($egwAttender['cal_user_id']);
                        if (! $resource_id) {
                            continue 2;
                        }
                        
                        $tineAttenderArray['user_type'] = Calendar_Model_Attender::USERTYPE_RESOURCE;
                        $tineAttenderArray['user_id']   = $resource_id;
                        break;
                        
                    default: 
                        throw new Exception("unsupported attender type: {$egwAttender['cal_user_type']}");
                        break;
                }
                
                $tineAttendee->addRecord(new Calendar_Model_Attender($tineAttenderArray));
            } catch (Exception $e) {
                $this->_log->warn('skipping attender for event "' . $_egwEventData['cal_id'] . '"cause: ' . $e->getMessage());
                // skip attender
            }
        }

        // resolve groupmembers
        Calendar_Model_Attender::resolveGroupMembers($tineAttendee);
        $groupMembers = $tineAttendee->filter('user_type', Calendar_Model_Attender::USERTYPE_GROUPMEMBER);
        foreach ($groupMembers as $groupMember) {
            $groupMember->status_authkey = Calendar_Model_Attender::generateUID();
            
            $contact = Addressbook_Controller_Contact::getInstance()->get($groupMember->user_id);
            $groupMember->displaycontainer_id = $_egwEventData['cal_public'] ? 
                $this->_getPersonalCalendar($contact->account_id)->getId() :
                $this->_getPrivateCalendar($contact->account_id)->getId();
           
        }
        
        return $tineAttendee;
    }
    
    /**
     * gets contact id of given email address
     * 
     * NOTE: if we find more than one contact, we could spend hours of smart guessing which one is the right one...
     *       but we don't do so yet
     *       
     * @param  string $_email
     * @param  string $_organizer
     * @return string
     */
    protected function _getContactIdByEmail($_email, $_organizer)
    {
        if (! $_email) {
            // contact not resolveable
            $this->_log->warn('no mail for contact given, contact not resolveable');
            return NULL;
        }
        
        $tineDb = Tinebase_Core::getDb();
        $select = $tineDb->select()
            ->from(array('contacts' => $tineDb->table_prefix . 'addressbook'))
            ->join(array('container' => $tineDb->table_prefix . 'container'), 
                $tineDb->quoteIdentifier('contacts.container_id') . ' = ' . $tineDb->quoteIdentifier('container.id'))
            /*->join(array('container_acl' => $tineDb->table_prefix . 'container_acl'), 
                $tineDb->quoteIdentifier('addressbook.container_id') . ' = ' . $tineDb->quoteIdentifier('container.id'))
            */
            ->where($tineDb->quoteInto($tineDb->quoteIdentifier('contacts.email') . ' LIKE ?', $_email));
            //->where($tineDb->quoteInto($tineDb->quoteIdentifier('container.type') . ' = ?', Tinebase_Model_Container::TYPE_SHARED));
        
        $contacts = $tineDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);

        return count($contacts) === 1 ? $contacts[0]['id'] : NULL;
    }
    
    /**
     * gets implicit exceptions due to status settings
     * 
     * @param  array                $_egwEventAttendee
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    protected function _getRecurImplicitExceptions($_egwEventData)
    {
        $implictExceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        if (empty($_egwEventData['attendee'])) {
            return $implictExceptions;
        }
        
        $select = $this->_egwDb->select()
            ->from(array('attendee' => 'egw_cal_user'), 'DISTINCT('. $this->_egwDb->quoteIdentifier('attendee.cal_recur_date') . ')')
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_id') . ' = ?', $_egwEventData['attendee'][0]['cal_id']))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_recur_date') . ' != ?', 0));
        $groupSelect = new Tinebase_Backend_Sql_Filter_GroupSelect($select);
        foreach($_egwEventData['attendee'] as $attender) {
            $groupSelect->orWhere(
                $this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('attendee.cal_user_type') . ' = ?', $attender['cal_user_type']) . ' AND ' .
                $this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('attendee.cal_user_id') . ' = ?', $attender['cal_user_id']) . ' AND ' .
                $this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('attendee.cal_status') . ' NOT LIKE ?', $attender['cal_status'])
            );
        }
        $groupSelect->appendWhere(Zend_Db_Select::SQL_AND);
        
        $egwExceptionDates = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        if (count($egwExceptionDates) > 0) {
            $this->_log->debug('found ' . count($egwExceptionDates) . ' implicit exceptions for event ' . $_egwEventData['attendee'][0]['cal_id']);
            //print_r($_egwEventAttendee);
        }
        
        if (count($egwExceptionDates) > 500) {
            $this->_log->err("egw's horizont for event " . $_egwEventData['attendee'][0]['cal_id'] . " seems to be broken. Status exceptions will not be considered/migrated");
            return $implictExceptions;
        }
        
        //print_r($egwExceptionDates);
        $eventDuration = $_egwEventData['cal_end'] - $_egwEventData['cal_start'];
        foreach ($egwExceptionDates as $exdate) {
            $select = $this->_egwDb->select()
                ->from(array('attendee' => 'egw_cal_user'))
                ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_recur_date') . ' = ?', $exdate['cal_recur_date']));
            $egwExceptionEventAttendee = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
            
            $exEventData = $_egwEventData;
            $exEventData['cal_id']    = Calendar_Model_Event::generateUID();
            $exEventData['cal_start'] = $exdate['cal_recur_date'];
            $exEventData['cal_end']   = $exdate['cal_recur_date'] + $eventDuration;
            $exEventData['attendee']  = $egwExceptionEventAttendee;
            
            $event = $this->_getTineEventRecord($exEventData);
            $event->attendee = $this->_getEventAttendee($exEventData);
            
            $implictExceptions->addRecord($event);
        }
        
        return $implictExceptions;
    }
    
    /**
     * get (persistent) exceptions for given event
     * 
     * NOTE: this does not get the implicit exceptions from stats settings
     * 
     * @param  int $_egwEventId
     * @return Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    protected function _getRecurExceptions($_egwEventId)
    {
        $tineExceptions = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        // get base event data
        $select = $this->_egwDb->select()
            ->from(array('events' => 'egw_cal'))
            ->join(array('dates'  => 'egw_cal_dates'), 'events.cal_id = dates.cal_id')
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_reference') . ' = ?', $_egwEventId));
            
        $egwExceptions = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        if (count($egwExceptions) == 0) {
            return $tineExceptions;
        }
        
        $this->_log->debug('found ' . count($egwExceptions) . ' explict exceptions for event ' . $_egwEventId);
        
        $egwExceptionsIdMap = array();
        foreach ($egwExceptions as $idx => $egwEventData) {
            $egwExceptionsIdMap[$egwEventData['cal_id']] = $idx;
            // preset attendee
            $egwEventData['attendee'] = array();
        }
        
        // collect attendee
        $select = $this->_egwDb->select()
            ->from(array('attendee' => 'egw_cal_user'))
            ->joinLeft(array('contacts' => 'egw_addressbook'), 
                $this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('attendee.cal_user_type') . ' = ?', 'c') . ' AND ' .
                $this->_egwDb->quoteIdentifier('attendee.cal_user_id') . ' = ' . $this->_egwDb->quoteIdentifier('contacts.contact_id'), 
                array('contacts.contact_email AS email'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_recur_date') . ' = ?', 0))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_id') . ' IN (?)', array_keys($egwExceptionsIdMap)));
        
        $egwExceptionsAttendee = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        
        foreach ($egwExceptionsAttendee as $eventAttendee) {
            $idx = $eventPageIdMap[$eventAttendee['cal_id']];
            $eventPage[$idx]['attendee'][] = $eventAttendee;
        }
        unset($egwExceptionsAttendee);
        
        
        foreach ($egwExceptions as $egwExceptionData) {
            $tineEvent = $this->_getTineEventRecord($egwExceptionData);
            $tineEvent->attendee = $this->_getEventAttendee($egwException);
            
            $tineExceptions->addRecord($tineEvent);
        }
        
        return $tineExceptions;
    }
    
    /**
     * gets tine cal recource by egw resource id
     * 
     * @param  int $_egwResourceId
     * @return string
     */
    protected function _getResourceId($_egwResourceId)
    {
        $select = $this->_egwDb->select()
            ->from(array('resources' => 'egw_resources'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('res_id') . ' = ?', $_egwResourceId));
        
        $egwResources = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        
        if (count($egwResources) !== 1) {
            $this->_log->warn('egw resource not found');
            return NULL;
        }
        $egwResource = $egwResources[0];
        
        // find tine resource
        $tineResources = Calendar_Controller_Resource::getInstance()->search(new Calendar_Model_ResourceFilter(array(
            array('field' => 'name', 'operator' => 'equals', 'value' => $egwResource['name'])
        )));
        
        if (count($tineResources) === 0) {
            // migrate on the fly
            $this->_log->info("migrating resource {$egwResource['name']}");
            
            $resource = new Calendar_Model_Resource(array(
                'name'        => $egwResource['name'],
                'description' => $egwResource['short_description'],
                'email'       => preg_replace('/[^A-Za-z0-9.\-]/', '', $egwResource['name'])
            ));
            
            $tineResource = Calendar_Controller_Resource::getInstance()->create($resource);
        } else {
            $tineResource = $tineResources->getFirstRecord();
        }
        
        return $tineResource->getId();
    }
    
    /**
     * gets the personal calendar of given user
     * 
     * @param  string $_userId
     * @return Tinebase_Model_Container
     */
    protected function _getPersonalCalendar($_userId)
    {
        if (! array_key_exists($_userId, $this->_personalCalendarCache)) {
            // get calendar by preference to ensure its the default personal
            $defaultCalendarId = Tinebase_Core::getPreference('Calendar')->getValueForUser(Calendar_Preference::DEFAULTCALENDAR, $_userId, Tinebase_Acl_Rights::ACCOUNT_TYPE_USER);
            $calendar = Tinebase_Container::getInstance()->getContainerById($defaultCalendarId);
            
            // detect if container just got created
            $isNewContainer = false;
            if ($calendar->creation_time instanceof Zend_Date) {
                $isNewContainer = $this->_migrationStartTime->isEarlier($calendar->creation_time);
            }
            
            if (($isNewContainer && $this->_config->setPersonalCalendarGrants) || $this->_config->forcePersonalCalendarGrants) {
                // resolve grants based on user/groupmemberships
                $grants = $this->getGrantsByOwner('Calendar', $_userId);
                Tinebase_Container::getInstance()->setGrants($calendar->getId(), $grants, TRUE);
            }
            
            $this->_personalCalendarCache[$_userId] = $calendar;
        }
        
        return $this->_personalCalendarCache[$_userId];
    }
    
    /**
     * gets a personal container for private events
     * 
     * NOTE: During migration phase, this container is identified by its name
     * 
     * @param  string $_userId
     * @return Tinebase_Model_Container
     */
    protected function _getPrivateCalendar($_userId)
    {
        $privateString = 'private events';
        
        if (! array_key_exists($_userId, $this->_privateCalendarCache)) {
            $personalCalendars = Tinebase_Container::getInstance()->getPersonalContainer($_userId, 'Calendar', $_userId, Tinebase_Model_Grants::GRANT_ADMIN, TRUE);
            $privateCalendar = $personalCalendars->filter('name', $privateString);
            
            if (count($privateCalendar) < 1) {
                $container = new Tinebase_Model_Container(array(
                    'name'           => $privateString,
                    'type'           => Tinebase_Model_Container::TYPE_PERSONAL,
                    'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                    'backend'        => 'sql',
                ));
                
                // NOTE: if no grants are given, container class gives all grants to accountId
                $privateCalendar = Tinebase_Container::getInstance()->addContainer($container, NULL, TRUE, $_userId);
            } else {
                $privateCalendar = $personalCalendars->getFirstRecord();
            }
            
            $this->_privateCalendarCache[$_userId] = $privateCalendar;
        }
        
        return $this->_privateCalendarCache[$_userId];
    }
    
    /**
     * appends filter to egw14 select obj. for raw event retirval
     * 
     * @param Zend_Db_Select $_select
     * @return void
     */
    protected function _appendFilter($_select)
    {
        //$_select
            //->join(array('repeats'  => 'egw_cal_repeats'), 'events.cal_id = repeats.cal_id')
            //->where('events.cal_id < ' . 190)
            //->where('events.cal_id >= ' . 190)
            //->where('events.cal_owner = ' . 3144);
    }
    
    protected function _getEgwEventsCount()
    {
        $select = $this->_egwDb->select()
            ->from(array('events' => 'egw_cal'), 'COUNT(*) AS count')
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_reference') . ' = ?', 0));
            
        $this->_appendFilter($select);
        
        $eventsCount = array_value(0, $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC));
        return $eventsCount['count'];
    }
    
    /**
     * gets a page of raw egw event data
     * 
     * @param  int $pageNumber
     * @param  int $pageSize
     * @return array
     */
    protected function _getRawEgwEventPage($pageNumber, $pageSize)
    {
        // get base event data
        $select = $this->_egwDb->select()
            ->from(array('events' => 'egw_cal'))
            ->join(array('dates'  => 'egw_cal_dates'), 'events.cal_id = dates.cal_id', array('MIN(cal_start) AS cal_start', 'MIN(cal_end) AS cal_end'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_reference') . ' = ?', 0))
            ->group('events.cal_id')
            ->order('events.cal_id ASC')
            ->limitPage($pageNumber, $pageSize);
            
        $this->_appendFilter($select);
        
        $eventPage = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        $eventPageIdMap = array();
        foreach ($eventPage as $idx => $egwEventData) {
            $eventPageIdMap[$egwEventData['cal_id']] = $idx;
            // preset attendee and rrule
            $egwEventData['attendee'] = array();
            $egwEventData['rrule'] = NULL;
        }
        
        // collect attendee
        $select = $this->_egwDb->select()
            ->from(array('attendee' => 'egw_cal_user')/*, array('*', 'COUNT(cal_recur_date) AS status_count')*/)
            //->group(array('cal_id', 'cal_user_type', 'cal_user_id', 'cal_status'))
            ->joinLeft(array('contacts' => 'egw_addressbook'), 
                $this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('attendee.cal_user_type') . ' = ?', 'c') . ' AND ' .
                $this->_egwDb->quoteIdentifier('attendee.cal_user_id') . ' = ' . $this->_egwDb->quoteIdentifier('contacts.contact_id'), 
                array('contacts.contact_email AS email'))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_recur_date') . ' = ?', 0))
            ->where($this->_egwDb->quoteInto($this->_egwDb->quoteIdentifier('cal_id') . ' IN (?)', array_keys($eventPageIdMap)));
        
        $eventPageAttendee = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        
        foreach ($eventPageAttendee as $eventAttendee) {
            $idx = $eventPageIdMap[$eventAttendee['cal_id']];
            $eventPage[$idx]['attendee'][] = $eventAttendee;
        }
        unset($eventPageAttendee);
        
        // collect rrules
        $select = $this->_egwDb->select()
            ->from(array('rrule' => 'egw_cal_repeats'))
            ->where($this->_egwDb->quoteInto('cal_id IN (?)', array_keys($eventPageIdMap)));
        
        $eventPageRrules = $this->_egwDb->fetchAll($select, NULL, Zend_Db::FETCH_ASSOC);
        
        foreach ($eventPageRrules as $eventRrule) {
            $idx = $eventPageIdMap[$eventRrule['cal_id']];
            $eventPage[$idx]['rrule'] = $eventRrule;
        }
        unset($eventPageRrules);
        
        return $eventPage;
    }
}