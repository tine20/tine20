<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert single event (repeating with exceptions) to/from VCalendar
 *
 * @package     Tasks
 * @subpackage  Convert
 * 
 * @todo find a way to generalize VCARD/VEVENT/VTODO parsing
 */
class Tasks_Convert_Task_VCalendar_Abstract extends Tinebase_Convert_VCalendar_Abstract implements Tinebase_Convert_Interface
{
    protected $_modelName = 'Tasks_Model_Task';
    
    /**
     * convert Tasks_Model_Task to \Sabre\VObject\Component
     *
     * @param  Tasks_Model_Task  $_record
     * @return \Sabre\VObject\Component
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' event ' . print_r($_record->toArray(), true));
        
        $vcalendar = new \Sabre\VObject\Component\VCalendar();
        
        // required vcalendar fields
        $version = Tinebase_Application::getInstance()->getApplicationByName('Tasks')->version;
        
        $vcalendar->PRODID   = "-//tine20.com//Tine 2.0 Tasks V$version//EN";
        $vcalendar->VERSION  = '2.0';
        $vcalendar->CALSCALE = 'GREGORIAN';
        
        // catch exceptions for unknown timezones
        try {
            $vcalendar->add(new Sabre_VObject_Component_VTimezone($_record->originator_tz));
        } catch (Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' timezone exception ' . $e->getMessage());
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ . ' timezone exception ' . $e->getTraceAsString());
        }
        
        $this->_convertTasksModelTask($vcalendar, $_record);
        
        $this->_afterFromTine20Model($vcalendar);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
            __METHOD__ . '::' . __LINE__ . ' card ' . $vcalendar->serialize());
        
        return $vcalendar;
    }
    
    /**
     * convert calendar event to \Sabre\VObject\Component
     * 
     * @param Tasks_Model_Task $_vtodo
     * @param Tasks_Model_Task $mainTask
     * @return \Sabre\VObject\Component
     */
    protected function _convertTasksModelTask(Sabre\VObject\Component\VCalendar $vcalendar, Tasks_Model_Task $task, Tasks_Model_Task $mainTask = null)
    {
        // clone the event and change the timezone
        $task = clone $task;
        if (!empty($task->originator_tz)) {
            $task->setTimezone($task->originator_tz);
        }
        
        $vtodo = $vcalendar->create('VTODO');
        
        $vtodo->add('CREATED', $task->creation_time->getClone()->setTimezone('UTC'));
        
        $lastModifiedDateTime = $task->last_modified_time ? $task->last_modified_time : $task->creation_time;
        $vtodo->add('LAST-MODIFIED', $lastModifiedDateTime->getClone()->setTimezone('UTC'));
        
        $vtodo->add('DTSTAMP', Tinebase_DateTime::now());

        $vtodo->add('UID',      $task->uid);
        $vtodo->add('SEQUENCE', !empty($task->seq) ? $task->seq : 1);
        
        if(isset($task->dtstart)){
            $vtodo->add('DTSTART', $task->dtstart);
        }
        
        if(isset($task->due)){
            $vtodo->add('DUE', $task->due);
        }
        
        if(isset($task->completed)){
            $vtodo->add('COMPLETED', $task->completed->getClone()->setTimezone('UTC'));
        }

        switch($task->priority) {
             case Tasks_Model_Priority::LOW:
                 $vtodo->add('PRIORITY', 9);
                 break;
                 
             case Tasks_Model_Priority::NORMAL:
                 $vtodo->add('PRIORITY', 0);
                 break;
                 
             case Tasks_Model_Priority::HIGH:
             case Tasks_Model_Priority::URGENT:
                 $vtodo->add('PRIORITY', 1);
                 break;
        }

        if(!empty($task->percent)){
            $vtodo->add('PERCENT-COMPLETE', $task->percent);
        }

        // task organizer
        if (!empty($task->organizer)) {
            $organizerContact = $task->resolveOrganizer();

            if ($organizerContact instanceof Addressbook_Model_Contact && !empty($organizerContact->email)) {
                $organizer = $vtodo->add(
                    'ORGANIZER', 
                    'mailto:' . $organizerContact->email, 
                    array('CN' => $organizerContact->n_fileas, 'EMAIL' => $organizerContact->email)
                );
            }
        }
        
        $optionalProperties = array(
            'class',
            'description',
            'geo',
            'location',
            #'priority',
            'summary',
            'status',
            'url'
        );
        
        foreach ($optionalProperties as $property) {
            if (!empty($task->$property)) {
                $vtodo->add($property, $task->$property);
            }
        }
        
        // categories
        if (!isset($task->tags)) {
            $task->tags = Tinebase_Tags::getInstance()->getTagsOfRecord($task);
        }
        if(isset($task->tags) && count($task->tags) > 0) {
            $vtodo->add('CATEGORIES', (array) $task->tags->name);
        }
        
        if ($task->alarms) {
            
            // fake X-MOZ-LASTACK
            $vtodo->add('X-MOZ-LASTACK', $task->creation_time->getClone()->setTimezone('UTC'), array('VALUE' => 'DATE-TIME'));
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(
                __METHOD__ . '::' . __LINE__ . ' event ' . print_r($task->alarms,TRUE));
 
            foreach($task->alarms as $alarm) {
                $valarm = $vcalendar->create('VALARM');
                $valarm->add('ACTION', 'DISPLAY');
                $valarm->add('DESCRIPTION', $task->summary);
                
                if (is_numeric($alarm->minutes_before)) {
                    if ($task->dtstart == $alarm->alarm_time) {
                        $periodString = 'PT0S';
                    } else {
                        $interval = $task->due->diff($alarm->alarm_time);
                        $periodString = sprintf('%sP%s%s%s%s',
                            $interval->format('%r'),
                            $interval->format('%d') > 0 ? $interval->format('%dD') : null,
                            ($interval->format('%h') > 0 || $interval->format('%i') > 0) ? 'T' : null,
                            $interval->format('%h') > 0 ? $interval->format('%hH') : null,
                            $interval->format('%i') > 0 ? $interval->format('%iM') : null
                        );
                    }
                    # TRIGGER;VALUE=DURATION:-PT1H15M
                    $trigger = $valarm->add('TRIGGER', $periodString);
                    $trigger['VALUE'] = "DURATION";
                } else {
                    # TRIGGER;VALUE=DATE-TIME:...
                    $trigger = $valarm->add('TRIGGER', $alarm->alarm_time->getClone()->setTimezone('UTC')->format('Ymd\\THis\\Z'));
                    $trigger['VALUE'] = "DATE-TIME";
                }

                $vtodo->add($valarm);
            }
        }
        
        $vcalendar->add($vtodo);
    }
    
    /**
     * converts vcalendar to Tasks_Model_Task
     * 
     * @param  mixed                 $_blob   the vcalendar to parse
     * @param  Calendar_Model_Event  $_record  update existing event
     * @param  array                 $options
     * @return Calendar_Model_Event
     */
    public function toTine20Model($_blob, Tinebase_Record_Interface $_record = null, $options = array())
    {
        $vcalendar = self::getVObject($_blob);
        
        // contains the VCALENDAR any VTODOS
        if (!isset($vcalendar->VTODO)) {
            throw new Tinebase_Exception_UnexpectedValue('no vevents found');
        }
        
        // update a provided record or create a new one
        if ($_record instanceof Tasks_Model_Task) {
            $task = $_record;
        } else {
            $task = new Tasks_Model_Task(null);
        }
        
        // bypass filters until end of this funtion
        $task->bypassFilters = true;
        
        // find the main event - the main event has no RECURRENCE-ID
        foreach ($vcalendar->VTODO as $vtodo) {
            if (!isset($vtodo->{"RECURRENCE-ID"})) {
                $this->_convertVtodo($vtodo, $task, $options);
                
                break;
            }
        }

        // if we have found no VTODO component something went wrong, lets stop here
        if (!isset($task)) {
            throw new Tinebase_Exception_UnexpectedValue('no main TODO component found in VCALENDAR');
        }
        
        // enable filters again
        $task->bypassFilters = false;
        
        $task->isValid(true);
        
        return $task;
    }

    /**
     * parse VTODO part of VCALENDAR
     * 
     * @param  \Sabre\VObject\Component\VTodo  $_vevent  the VTODO to parse
     * @param  Tasks_Model_Task     $_vtodo   the Tine 2.0 event to update
     */
    protected function _convertVtodo(\Sabre\VObject\Component\VTodo $_vtodo, Tasks_Model_Task $_task, $options)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' vtodo ' . $_vtodo->serialize());  
        
        $task = $_task;
        
        $task->alarms = new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $task->priority = Tasks_Model_Priority::NORMAL;
        $task->status = 'NEEDS-ACTION';
        
        foreach($_vtodo->children() as $property) {
            switch($property->name) {
                case 'CREATED':
                case 'DTSTAMP':
                    // do nothing
                    break;
                    
                case 'LAST-MODIFIED':
                    $task->last_modified_time = new Tinebase_DateTime($property->getValue());
                    break;
                
                case 'CLASS':
                    if (in_array($property->getValue(), array(Tasks_Model_Task::CLASS_PRIVATE, Tasks_Model_Task::CLASS_PUBLIC))) {
                        $task->class = $property->getValue();
                    } else {
                        $task->class = Tasks_Model_Task::CLASS_PUBLIC;
                    }
                    
                    break;
                    
                case 'COMPLETED':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        //$task->is_all_day_event = true;
                        $dtend = $this->_convertToTinebaseDateTime($property, TRUE);
                        
                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dtend->subSecond(1);
                    } else {
                        //$task->is_all_day_event = false;
                        $dtend = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $task->completed = $dtend;
                    
                    break;
                    
                case 'DUE':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        //$task->is_all_day_event = true;
                        $due = $this->_convertToTinebaseDateTime($property, TRUE);
                    } else {
                        //$task->is_all_day_event = false;
                        $due = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $task->originator_tz = $due->getTimezone()->getName();
                    $task->due = $due;
  
                    break;
                    
                case 'DTSTART':
                    if (isset($property['VALUE']) && strtoupper($property['VALUE']) == 'DATE') {
                        // all day event
                        //$task->is_all_day_event = true;
                        $dtstart = $this->_convertToTinebaseDateTime($property, TRUE);
                        // whole day events ends at 23:59:59 in Tine 2.0 but 00:00 the next day in vcalendar
                        $dtstart->subSecond(1);
                    } else {
                        //$task->is_all_day_event = false;
                        $dtstart = $this->_convertToTinebaseDateTime($property);
                    }
                    
                    $task->originator_tz = $dtstart->getTimezone()->getName();
                    $task->dtstart = $dtstart;
                    
                    break;
                    
                case 'STATUS':
                    $task->status = $property->getValue();
                    
                    break;
                    
                case 'PERCENT-COMPLETE':
                    $task->percent = $property->getValue();
                    
                    break;
                           
                case 'SEQUENCE':
                    if (! isset($options[self::OPTION_USE_SERVER_MODLOG]) || $options[self::OPTION_USE_SERVER_MODLOG] !== true) {
                        $task->seq = $property->getValue();
                    }
                    
                    break;
                    
                case 'PRIORITY':
                    if (is_numeric($property->getValue())) {
                        switch ($property->getValue()) {
                            case '0':
                                $task->priority = Tasks_Model_Priority::NORMAL;
                                
                                break;
                                
                            case '1':
                                $task->priority = Tasks_Model_Priority::HIGH;
                                
                                break;
                                
                            case '9':
                                $task->priority = Tasks_Model_Priority::LOW;
                                
                                break;
                            }
                    } else {
                        $val = $property->getValue();
                        $upperVal = strtoupper($val);
                        if (isset(Tasks_Model_Priority::$upperStringMapping[$upperVal])) {
                            $task->priority = Tasks_Model_Priority::$upperStringMapping[$upperVal];
                        } else {
                            $task->priority = $val;
                        }
                    }
                    
                    break;
                    
                case 'DESCRIPTION':
                case 'LOCATION':
                case 'SUMMARY':
                    $key = strtolower($property->name);
                    //$task->$key = empty($property->getValue()) ?  "With aout summary" : $property->getValue();
                    $task->$key = $property->getValue();
                    break;
                    
                case 'ORGANIZER':
                    if (preg_match('/mailto:(?P<email>.*)/i', $property->getValue(), $matches)) {
                        // it's not possible to change the organizer by spec
                        if (empty($task->organizer)) {
                            $user = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountEmailAddress', $matches['email']);
                            $task->organizer = $user ? $user->getId() : Tinebase_Core::getUser()->getId();
                        }
                    }
                    break;
                    
                case 'UID':
                    // it's not possible to change the uid by spec
                    if (empty($task->uid)) {
                        $task->uid = $property->getValue();
                    }
                    break;
                    
                case 'VALARM':
                    $this->_parseAlarm($task, $property, $_vtodo);
                    break;
                    
                case 'CATEGORIES':
                    $tags = Tinebase_Model_Tag::resolveTagNameToTag($property->getParts(), 'Tasks');
                    if (! isset($task->tags)) {
                        $task->tags = $tags;
                    } else {
                        $task->tags->merge($tags);
                    }
                    break;
                    
                case 'X-MOZ-LASTACK':
                    $lastAck = $this->_convertToTinebaseDateTime($property);
                    break;
                    
                case 'X-MOZ-SNOOZE-TIME':
                    $snoozeTime = $this->_convertToTinebaseDateTime($property);
                    break;
                    
                default:
                
                    break;
            }
        }
        
        if (empty($task->percent)) {
            $task->percent = 0;
        }
        
        if (empty($task->class)) {
            $task->class = Tasks_Model_Task::CLASS_PUBLIC;
        }
        
        // convert all datetime fields to UTC
        $task->setTimezone('UTC');
    }
}
