<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Events
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Events
 * @subpackage  Convert
 */
class Events_Convert_Event_Json extends Tinebase_Convert_Json
{
    /**
     * resolves child records before converting the record set to an array
     *
     * @param Tinebase_Record_RecordSet $records
     * @param Tinebase_ModelConfiguration $modelConfiguration
     * @param boolean $multiple
     */
    protected function _resolveBeforeToArray($records, $modelConfiguration, $multiple = false)
    {
        parent::_resolveBeforeToArray($records, $modelConfiguration, $multiple);

        // resolve containers and organizers of event relations
        foreach ($records->relations as $relations) {
            // TODO do this in one loop
            if ($relations instanceof Tinebase_Record_RecordSet && count($relations) > 0) {
                $eventRelations = $relations->filter('related_model', 'Calendar_Model_Event');
                $calEvents = new Tinebase_Record_RecordSet('Calendar_Model_Event', $eventRelations->related_record);
                Tinebase_Frontend_Json_Abstract::resolveContainersAndTags($calEvents);
                Calendar_Convert_Event_Json::resolveOrganizer($calEvents);
                Calendar_Model_Attender::resolveAttendee($calEvents->attendee, TRUE, $calEvents);
            }
        }
    }
}
