<?php
/**
 * convert functions for records from/to json (array) format
 * 
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for records from/to json (array) format
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_Json extends Tinebase_Convert_Json
{
    /**
    * converts Tinebase_Record_Abstract to external format
    *
    * @param  Tinebase_Record_Abstract $_record
    * @return mixed
    */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        Calendar_Model_Attender::resolveAttendee($_record->attendee);
        $this->_resolveRrule($_record);
        $this->_resolveOrganizer($_record);
        
        return parent::fromTine20Model($_record);
    }
}
