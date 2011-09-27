<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filters for contacts that are event organizers
 * 
 * @package     Calendar
 * @subpackage  Model
 */
class Calendar_Model_ContactOrganizerFilter extends Calendar_Model_ContactAttendeeFilter 
{
    /**
     * filter fields for organizer
     * 
     * @var array
     */
    protected $_filterFields = array('organizer');
    
    /**
     * extract contact ids
     * 
     * @param Tinebase_Record_RecordSet $_events
     * @return array
     */
    protected function _getContactIds($_events)
    {
        $contactIds = array();
        
        foreach ($_events as $event) {
            if ($this->_matchFilter($event, 'organizer', 'organizer')) {
                $contactIds[] = $event->organizer;
            }
        }
        
        return array_unique($contactIds);
    }
}
