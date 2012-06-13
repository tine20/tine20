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
     * enforce acl restrictions to alram options
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
        
        $migration = $currentAlarms->getMigration($alarms->getArrayOfIds());
        $migration['toCreateIds'] = $alarms->getIdLessIndexes();
        
        array_filter($migration['toUpdateIds'], function($alarmId) use($alarms, $currentAlarms) {
            return (bool) $currentAlarms->getById($alarmId)->diff($alarms->getById($alarmId));
        });
        array_filter($migration, function($toX) {
            return ! empty($toX);
        });
        
        return ! empty($migration);
    }
}