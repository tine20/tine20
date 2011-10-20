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
    const CLIENT_GENERIC     = 'generic';
    const CLIENT_IPHONE      = 'iphone';
    const CLIENT_MACOSX      = 'macosx';
    const CLIENT_THUNDERBIRD = 'thunderbird';
    
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
	            
	        case Calendar_Convert_Event_VCalendar_Factory::CLIENT_IPHONE:
	            return new Calendar_Convert_Event_VCalendar_Iphone($_version);
	            
	            break;
	            
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX:
	            return new Calendar_Convert_Event_VCalendar_MacOSX($_version);
	            
	            break;
	            
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD:
                return new Calendar_Convert_Event_VCalendar_Thunderbird($_version);
                 
                break;
	                 
	    }
	}
	
	/**
	 * parse iseragent and return backend and version
	 * 
	 * @return array
	 */
	static public function parseUserAgent($_userAgent)
	{
        // CalendarStore/5.0 (1127); iCal/5.0 (1535); Mac OS X/10.7.1 (11B26)
        if (preg_match('/^CalendarStore.*Mac OS X\/(?P<version>.*) /', $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX;
            $version = $matches['version'];
        
        // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13
        } elseif (preg_match('/ Thunderbird\/(?P<version>.*)/', $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD;
            $version = $matches['version'];
        
        // iPhone
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_Iphone::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_IPHONE;
            $version = $matches['version'];
        
        } else {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC;
            $version = null;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " backend: $backend version: $version");
        
        return array($backend, $version);
	}
}
