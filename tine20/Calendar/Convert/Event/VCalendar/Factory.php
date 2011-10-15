<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * addressbook vcard convert factory class
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Factory
{
    const CLIENT_THUNDERBIRD = 'thunderbird';
    const CLIENT_MACOSX      = 'macosx';
    const CLIENT_GENERIC     = 'generic';
    
    /**
	 * factory function to return a selected vcalendar backend class
	 *
	 * @param   string $_backend
	 * @param   string $_version
	 * @return  Tinebase_Convert_Interface
	 */
	static public function factory($_backend, $_version = null)
	{
	    switch ($_backend) {
	        case Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC:
	            return new Calendar_Convert_Event_VCalendar_Generic($_version);
	            
	            break;
	            
	        case Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX:
	            return new Calendar_Convert_Event_VCalendar_MacOSX($_version);
	            
	            break;
	            
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD:
                return new Calendar_Convert_Event_VCalendar_Thunderbird($_version);
                 
                break;
	                 
	    }
	}
}
