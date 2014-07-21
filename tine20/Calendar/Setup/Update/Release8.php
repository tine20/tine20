<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */
class Calendar_Setup_Update_Release8 extends Setup_Update_Abstract
{
    /**
     * update to 8.1
     * - move ack & snooze time from attendee to alarm
     */
    public function update_0()
    {
        // find all events with ack or snooze times set
        $eventIds = $this->_db->query(
            "SELECT DISTINCT " . $this->_db->quoteIdentifier('cal_event_id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_attendee") .
                " WHERE " . $this->_db->quoteIdentifier("alarm_ack_time") . " IS NOT NULL OR ". $this->_db->quoteIdentifier("alarm_snooze_time") . " IS NOT NULL"
            )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        $attendeeBE = new Calendar_Backend_Sql_Attendee();
        $alarmBE = Tinebase_Alarm::getInstance();
        
        foreach ($eventIds as $eventId) {
            $eventId = $eventId['cal_event_id'];
            
            $attendeeFilter = new Tinebase_Model_Filter_FilterGroup();
            $attendeeFilter->addFilter(new Tinebase_Model_Filter_Text('cal_event_id', 'equals', $eventId));
            $attendees = $attendeeBE->search($attendeeFilter);
            
            $alarms = $alarmBE->search(new Tinebase_Model_AlarmFilter(array(
                array('field' => 'model',     'operator' => 'equals', 'value' =>'Calendar_Model_Event'),
                array('field' => 'record_id', 'operator' => 'equals', 'value' => $eventId)
            )));
            
            foreach ($alarms as $alarm) {
                foreach ($attendees as $attendee) {
                    if ($attendee->alarm_ack_time instanceof Tinebase_DateTime) {
                        $alarm->setOption("acknowledged-{$attendee->user_id}", $attendee->alarm_ack_time->format(Tinebase_Record_Abstract::ISO8601LONG));
                    }
                    if ($attendee->alarm_snooze_time instanceof Tinebase_DateTime) {
                        $alarm->setOption("snoozed-{$attendee->user_id}", $attendee->alarm_snooze_time->format(Tinebase_Record_Abstract::ISO8601LONG));
                    }
                    
                }
                $alarmBE->update($alarm);
            }
        }
        
        // delte ack & snooze from attendee
        $this->_backend->dropCol('cal_attendee', 'alarm_ack_time');
        $this->_backend->dropCol('cal_attendee', 'alarm_snooze_time');
        
        $this->setTableVersion('cal_attendee', 5);
        $this->setApplicationVersion('Calendar', '8.1');
    }
    
    /**
     * update to 8.2
     * 
     */
    public function update_1()
    {
        $eventBE = new Tinebase_Backend_Sql(array(
                'modelName'    => 'Calendar_Model_Event',
                'tableName'    => 'cal_events',
                'modlogActive' => false
        ));
        // find all events without organizer
        $eventIds = $this->_db->query(
                "SELECT " . $this->_db->quoteIdentifier('id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                " WHERE " . $this->_db->quoteIdentifier("organizer") . " IS NULL OR " .
                            $this->_db->quoteIdentifier("organizer") . " = ''"
        )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach ($eventIds as $eventId) {
            $event = $eventBE->get($eventId['id']);
            $event->organizer = (string) Tinebase_User::getInstance()->getFullUserById($event->created_by)->contact_id;
            
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $eventId),
            );
            
            try {
                $this->_db->update(SQL_TABLE_PREFIX . "cal_events", $event->toArray(), $where);
            } catch (Tinebase_Exception_Record_Validation $terv) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Could not fix invalid event record: ' . print_r($event->toArray(), true));
                Tinebase_Exception::log($terv);
            }
        }
        
        // find all events CONFIDENTIAL class
        $eventIds = $this->_db->query(
                "SELECT " . $this->_db->quoteIdentifier('id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                " WHERE " . $this->_db->quoteIdentifier("class") . " = 'CONFIDENTIAL'"
        )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        foreach ($eventIds as $eventId) {
            $event = $eventBE->get($eventId['id']);
            $class = Calendar_Model_Event::CLASS_PRIVATE;
            $event->class = (string) $class;
            
            $where  = array(
                $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $eventId),
            );
            
            try {
                $this->_db->update(SQL_TABLE_PREFIX . "cal_events", $event->toArray(), $where);
            } catch (Tinebase_Exception_Record_Validation $terv) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Could not fix invalid event record: ' . print_r($event->toArray(), true));
                Tinebase_Exception::log($terv);
            }
        }
         $this->setApplicationVersion('Calendar', '8.2');
    }
    
    /**
     * update to 8.3
     * - normalize all rrules
     */
    public function update_2()
    {
        // find all events with rrule
        $eventIds = $this->_db->query(
                "SELECT " . $this->_db->quoteIdentifier('id') .
                " FROM " . $this->_db->quoteIdentifier(SQL_TABLE_PREFIX . "cal_events") .
                " WHERE " . $this->_db->quoteIdentifier("rrule") . " IS NOT NULL"
        )->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // NOTE: we need a generic sql BE to circumvent calendar specific acl issues
        $eventBE = new Tinebase_Backend_Sql(array(
                'modelName'    => 'Calendar_Model_Event',
                'tableName'    => 'cal_events',
                'modlogActive' => false
        ));
        
        foreach ($eventIds as $eventId) {
            $event = $eventBE->get($eventId['id']);
            $oldRruleString = (string) $event->rrule;
            $rrule = Calendar_Model_Rrule::getRruleFromString($oldRruleString);
            $rrule->normalize($event);
            
            if ($oldRruleString != (string) $rrule) {
                $event->rrule = (string) $rrule;
                try {
                    $eventBE->update($event);
                } catch (Tinebase_Exception_Record_Validation $terv) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . ' Could not normalize rrule for invalid event record: ' . print_r($event->toArray(), true));
                    Tinebase_Exception::log($terv);
                }
            }
        }
        
        $this->setApplicationVersion('Calendar', '8.3');
    }
}
