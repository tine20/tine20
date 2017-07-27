<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christoph Elisabeth Hintermüller <christoph@out-world.com>
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to convert single event to/from VCalendar
 *
 * @package     Tasks
 * @subpackage  Convert
 */
class Tasks_Convert_Task_VCalendar_Evolution extends Tasks_Convert_Task_VCalendar_Abstract
{

    // Evolution/3.18.5
    const HEADER_MATCH = '/Evolution\/(?P<version>.*)/';
    
    protected $_supportedFields = array(
        'seq',#sequence
        'dtstart',
        #'transp',
        'class',
        'description',
        #'geo',
        #'location',
        'priority',
        'summary',
        'url',
        #'alarms',
        'tags',
        'status',
        'due',
        'percent', # percent-complete
        'completed',
        #'exdate',
        #'rrule',
        #'recurid',
        #'is_all_day_event',
        #'rrule_until',
        'originator_tz'
    );
    
    /**
     * converts vcalendar to Tasks_Model_Task
     * 
     * @param  mixed                 $_blob   the vcalendar to parse
     * @param  Calendar_Model_Event  $_record  update existing event
     * @param  array                 $options
     * @return Calendar_Model_Event
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null, $options = array())
    {
        $task = parent::toTine20Model($_blob,$_record,$options);
        /**
         * TODO remove if tine core can better handle ical filename and uid extended by hostname task was created by and timestamp when change
         *      occured:
         **
         * Evolution 3.10.04 creates the names of the event files by replacing any non url characters by _ and appending a timestamp to it
         * set id of task to UID without name of computer created and time stamp of change, may result in conflicts if changes ond serveral computers
         * at the same time but for now this would fix things
         */
        
        // bypass filters until end of this funtion
        $task->bypassFilters = true;
        if ( isset($task->uid) ) {
             if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                       __METHOD__ . '::' . __LINE__ . ' Fixing Evolution TaskID: trip timestamp, if present, and hostname from uid ');
             $evolutionid = preg_replace('/[-_]/','',preg_replace('/@.*$/','',$task->uid ));
             if ( $evolutionid !== $task->uid ) {
                 if ( strlen($evolutionid) <= 40 ) {
                     $task->setId($evolutionid);
                     if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                         __METHOD__ . '::' . __LINE__ . ' Fixing Evolution TaskID: ' . $task->getId() );
                 }
                 else if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                       __METHOD__ . '::' . __LINE__ . ' Fixing Evolution TaskID: failed! id to long (>40 char) after striping any non relevant character (' . $evolutionid . ')' );
             }
             else if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                       __METHOD__ . '::' . __LINE__ . ' Fixing Evolution TaskID: not necessary as not created by evolution ' );
 
        }
        else if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                   __METHOD__ . '::' . __LINE__ . ' Fixing Evolution TaskID: not necessary or possible as uid empty' );
        
        // enable filters again
        $task->bypassFilters = false;
        
        $task->isValid(true);
        return $task;
    }
}


