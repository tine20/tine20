<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

class Calendar_Import_Ical
{
    protected $_importContainerId = 363; // Ferien SH
    
    protected $_defaultTimezoneId;
    
    protected $_eventPropertyMap = array(
        'summary'               => 'SUMMARY',
        'description'           => 'DESCRIPTION',
        'class'                 => 'CLASS',
        'transp'                => 'TRANSP',
        'seq'                   => 'SEQUENCE',
        'uid'                   => 'UID',
        'dtstart'               => 'DTSTART',
        'dtend'                 => 'DTEND',
        'rrule'                 => 'RRULE',
//        '' => 'DTSTAMP',
        'creation_time'         => 'CREATED',
        'last_modified_time'    => 'LAST-MODIFIED',
    );
    
    public function __construct($_config = array())
    {
        
    }
    
    /**
     * imports given file into configured calendar
     * 
     * @param string    $_file
     */
    public function importFile($_file)
    {
        $filepath = realpath(dirname($_file));
        $parser = new qCal_Parser(array(
            'searchpath' => $filepath,
        ));
        
        $ical = $parser->parseFile(basename($_file));
        
        $events = $this->_getEvents($ical);
//        print_r($events->toArray());
        
        // set container
        $events->container_id = $this->_importContainerId;
        
        $cc = Calendar_Controller_Event::getInstance();
        
        // search uid's and remove already existing -> only in import cal?
        $existingEvents = $cc->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_importContainerId),
            array('field' => 'uid', 'operator' => 'in', 'value' => array_unique($events->uid)),
        )), NULL);
        
        foreach($existingEvents as $existingEvent) {
            $events->removeRecord($events->find('uid', $existingEvent->uid));
        }
        
        // insert one by one in a single transaction
        $tid = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        foreach($events as $event) {
            $cc->create($event, FALSE);
        }
        Tinebase_TransactionManager::getInstance()->commitTransaction($tid);
    }
    
    /**
     * converts VEVENT to an Calendar_Model_Event
     * 
     * @param   qCal_Component $vevent
     * @return  Calendar_Model_Event
     */
    protected function _getEvent(qCal_Component $vevent)
    {
        $eventData = array();
        
        // timezone
        if ($vevent->hasComponent('VTIMEZONE')) {
            $tz = array_value(0, $vevent->getComponent('VTIMEZONE'));
            $eventData['originator_tz'] = array_value(0, $tz->getProperty('TZID'))->getValue();
        } else {
            $eventData['originator_tz'] = $this->_defaultTimezoneId;
        }
        
        foreach($this->_eventPropertyMap as $tineName => $icalName) {
            if ($vevent->hasProperty($icalName)) {
                $icalValue = array_value(0, $vevent->getProperty($icalName));
                
                switch ($icalValue->getType()) {
                    case 'DATE':
                        $value = new Tinebase_DateTime($icalValue->getValue() . 'T000000', $eventData['originator_tz']);
                        
                        // events with dtstart given as date are allday events!
                        if ($tineName == 'dtstart') {
                            $eventData['is_all_day_event'] = true;
                        }
                        
                        if ($tineName == 'dtend') {
                            $value = $value->addSecond(-1);
                        } 
                        break;
                    case 'DATE-TIME':
                        $value = new Tinebase_DateTime($icalValue->getValue(), $eventData['originator_tz']);
                    case 'TEXT':
                        $value = str_replace(array('\\,', '\\n'), array(',', "\n"), $icalValue->getValue());
                        break;
                    default:
                        $value = $icalValue->getValue();
                        break;
                }
                $eventData[$tineName] = $value;
            }
        }
        
        $event = new Calendar_Model_Event($eventData);
        $event->setTimezone('UTC');
                        
        return $event;
    }
    
    /**
     * convert a VCALENDAR into a Tinebase_Record_RecordSet of Calendar_Model_Event
     * 
     * @param   qCal_Component_Vcalendar $component
     * @return  Tinebase_Record_RecordSet of Calendar_Model_Event
     */
    protected function _getEvents(qCal_Component_Vcalendar $component)
    {
        $events = new Tinebase_Record_RecordSet('Calendar_Model_Event');
        
        // do we have a generic timezone?
        if ($component->hasComponent('VTIMEZONE')) {
            $tz = array_value(0, $component->getComponent('VTIMEZONE'));
            $this->_defaultTimezoneId = array_value(0, $tz->getProperty('TZID'))->getValue();
        } else {
            // @TODO take the users default timezone
        }
        
        foreach ($component->getChildren() as $children) {
            if (is_array($children)) {
                foreach ($children as $child) {
                    if ($child->getName() === 'VEVENT') {
                        $events->addRecord($this->_getEvent($child));
                    }
                }
            } else {
                if ($children->getName() === 'VEVENT') {
                    $events->addRecord($this->_getEvent($children));
                }
            }
        }
        
        return $events;
    }
}