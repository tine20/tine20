<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Calendar Alarm Controller
 * 
 * @package Calendar
 * @subpackage  Controller
 */
class Calendar_Controller_Alarm
{
    /**
     * enforce acl restrictions to alarm options
     * 
     * @param Calendar_Model_Event $_event
     * @param Calendar_Model_Event $_currentEvent
     * @return bool true if alarms have updates
     */
    public static function enforceACL($_event, $_currentEvent=NULL)
    {
        $alarms        = $_event->alarms instanceof Tinebase_Record_RecordSet ? $_event->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $currentAlarms = $_currentEvent && $_currentEvent->alarms instanceof Tinebase_Record_RecordSet ? $_currentEvent->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        // 1. assemble attendeeSet curruser has rights for
        // 2. enforcethe rights ;-)
        
        if ($_currentEvent) {
            $alarms->record_id = $_currentEvent->getId();
        }
    }
    
    /**
     * check if alarms have updates
     * 
     * @param Calendar_Model_Event $_event
     * @param Calendar_Model_Event $_currentEvent
     * @return bool true if alarms have updates
     */
    public static function hasUpdates($_event, $_currentEvent)
    {
        $alarms        = $_event->alarms instanceof Tinebase_Record_RecordSet ? $_event->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        $currentAlarms = $_currentEvent->alarms instanceof Tinebase_Record_RecordSet ? $_currentEvent->alarms : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        $diff = $currentAlarms->diff($alarms);
        return ! $diff->isEmpty();
    }
    
    /**
     * return matching alarm from alarmSet 
     * 
     * @param Tinebase_Record_RecordSet $_alarmSet
     * @param Tinebase_Model_Alarm      $_alarm
     */
    public static function getMatchingAlarm($_alarmSet, $_alarm)
    {
        $alarmSet = $_alarmSet instanceof Tinebase_Record_RecordSet ? $_alarmSet : new Tinebase_Record_RecordSet('Tinebase_Model_Alarm');
        
        $candidates = $alarmSet->filter('minutes_before', $_alarm->minutes_before);
        if ($_alarm->minutes_before == 'custom') {
            $candidates = $candidates->filter('alarm_time', $_alarm->alarm_time);
        }
        
        return $candidates->getFirstRecord();
    }
    
    /**
     * skip alarm for attendee
     * 
     * @param Tinebase_Model_Alarm    $_alarm
     * @param Calendar_Model_Attender $_attendee
     */
    public static function skipAlarm($_alarm, $_attendee)
    {
        $skip = $_alarm->getOption('skip');
        $skip = is_array($skip) ? $skip : array();
        
        array_push($skip, self::attendeeToOption($_attendee));
        
        $_alarm->setOption('skip', $skip);
    }
    
    /**
     * converts attendee to option array
     * 
     * @param Calendar_Model_Attender $_attendee
     * @return array
     */
    public static function attendeeToOption($_attendee)
    {
        return array(
            'user_type' => $_attendee->user_type,
            'user_id'   => $_attendee->user_id instanceof Tinebase_Record_Abstract ? $_attendee->user_id->getId() : $_attendee->user_id
        );
    }
    
    /**
     * gets acknowledged time in alarm
     *
     * @param Tinebase_Model_Alarm     $alarm
     * @param Tinebase_Model_User      $user
     * @return Tinebase_DateTime|array
     */
    public static function getAcknowledgeTime($alarm, $user = null)
    {
        $user = $user instanceof Tinebase_Model_User ?: Tinebase_Core::getUser();
        $times = $alarm->getOption("acknowledged-{$user->contact_id}");
        
        if (is_array($times)) {
            foreach($times as $idx => $time) {
                $times[$idx] = $times[$idx] ? new Tinebase_DateTime($time) : $times[$idx];
            }
            return $times;
        } else {
            return $times ? new Tinebase_DateTime($times) : $times;
        }
    }
    
    /**
     * sets acknowledged time in alarm
     * 
     * @param Tinebase_Model_Alarm     $alarm
     * @param DateTime                 $time
     * @param Tinebase_Model_User      $user
     */
    public static function setAcknowledgeTime($alarm, $time, $user = null)
    {
        $user = $user instanceof Tinebase_Model_User ?: Tinebase_Core::getUser();
        $alarm->setOption("acknowledged-{$user->contact_id}", $time->format(Tinebase_Record_Abstract::ISO8601LONG));
        
        $accessLog = Tinebase_Core::get(Tinebase_Core::USERACCESSLOG);
        if ($accessLog) {
            $alarm->setOption(Tinebase_Model_Alarm::OPTION_ACK_IP, $accessLog->ip);
            $alarm->setOption(Tinebase_Model_Alarm::OPTION_ACK_CLIENT, $accessLog->clienttype);
        }
    }
    
    /**
     * gets snoozed time in alarm
     *
     * @param Tinebase_Model_Alarm     $alarm
     * @param Tinebase_Model_User      $user
     */
    public static function getSnoozeTime($alarm, $user = null)
    {
        $user = $user instanceof Tinebase_Model_User ?: Tinebase_Core::getUser();
        $times = $alarm->getOption("snoozed-{$user->contact_id}");
        
        if (is_array($times)) {
            foreach($times as $idx => $time) {
                $times[$idx] = $times[$idx] ? new Tinebase_DateTime($time) : $times[$idx];
            }
            return $times;
        } else {
            return $times ? new Tinebase_DateTime($times) : $times;
        }
    }
    
    /**
     * sets snoozed time in alarm
     *
     * @param Tinebase_Model_Alarm     $alarm
     * @param DateTime                 $time
     * @param Tinebase_Model_User      $user
     */
    public static function setSnoozeTime($alarm, $time, $user = null)
    {
        $user = $user instanceof Tinebase_Model_User ?: Tinebase_Core::getUser();
        $alarm->setOption("snoozed-{$user->contact_id}", $time->format(Tinebase_Record_Abstract::ISO8601LONG));
    }
}