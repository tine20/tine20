<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * calendar VCALENDAR converter factory class
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar_Factory
{
    const CLIENT_GENERIC     = 'generic';
    const CLIENT_IPHONE      = 'iphone';
    const CLIENT_KDE         = 'kde';
    const CLIENT_MACOSX      = 'macosx';
    const CLIENT_THUNDERBIRD = 'thunderbird';
    const CLIENT_EMCLIENT    = 'emclient';
    const CLIENT_EMCLIENT7   = 'emclient7';
    const CLIENT_TINE        = 'tine';
    const CLIENT_DAVDROID    = 'davdroid';
    const CLIENT_CALDAVSYNCHRONIZER = 'caldavsynchronizer';
    const CLIENT_FANTASTICAL = 'fantastical';
    const CLIENT_BUSYCAL     = 'busycal';
    const CLIENT_EVOLUTION   = 'evolution';

    /**
     * cache parsed user-agent strings
     * 
     * @var array
     */
    static protected $_parsedUserAgentCache = array();
    
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
                
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_IPHONE:
                return new Calendar_Convert_Event_VCalendar_Iphone($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_BUSYCAL:
                return new Calendar_Convert_Event_VCalendar_BusyCal($_version);
                
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_KDE:
                return new Calendar_Convert_Event_VCalendar_KDE($_version);

                
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX:
                return new Calendar_Convert_Event_VCalendar_MacOSX($_version);
                
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD:
                return new Calendar_Convert_Event_VCalendar_Thunderbird($_version);
 
            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_EMCLIENT:
                return new Calendar_Convert_Event_VCalendar_EMClient($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_EMCLIENT7:
                return new Calendar_Convert_Event_VCalendar_EMClient7($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_DAVDROID:
                return new Calendar_Convert_Event_VCalendar_DavDroid($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_CALDAVSYNCHRONIZER:
                return new Calendar_Convert_Event_VCalendar_CalDAVSynchronizer($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_FANTASTICAL:
                return new Calendar_Convert_Event_VCalendar_Fantastical($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_TINE:
                return new Calendar_Convert_Event_VCalendar_Tine($_version);

            case Calendar_Convert_Event_VCalendar_Factory::CLIENT_EVOLUTION:
                return new Calendar_Convert_Event_VCalendar_Evolution($_version);

        }
        return new Calendar_Convert_Event_VCalendar_Generic($_version);
    }
    
    /**
     * parse useragent and return backend and version
     * 
     * @return array
     */
    static public function parseUserAgent($_userAgent)
    {
        if (isset(self::$_parsedUserAgentCache[$_userAgent])) {
            return self::$_parsedUserAgentCache[$_userAgent];
        }
        
        // MacOS X
        if (preg_match(Calendar_Convert_Event_VCalendar_MacOSX::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_MACOSX;
            $version = $matches['version'];
        
        // iPhone
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_Iphone::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_IPHONE;
            $version = $matches['version'];
        
        // BusyCal
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_BusyCal::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_BUSYCAL;
            $version = $matches['version'];

        // KDE
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_KDE::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_KDE;
            $version = $matches['version'];
        
        // Thunderbird
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_Thunderbird::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_THUNDERBIRD;
            $version = $matches['version'];

        // EMClient 7 calendar
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_EMClient7::HEADER_MATCH, $_userAgent, $matches) && (floor($matches['version']) >= 7)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_EMCLIENT7;
            $version = $matches['version'];

        // EMClient
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_EMClient::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_EMCLIENT;


        // Evolution
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_Evolution::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_EVOLUTION;
            $version = $matches['version'];

        // Tine 2.0
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_Tine::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_TINE;
            $version = $matches['version'];

        // DavDroid
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_DavDroid::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_DAVDROID;
            $version = $matches['version'];

        // CalDAVSynchronizer
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_CalDAVSynchronizer::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_CALDAVSYNCHRONIZER;
            $version = $matches['version'];

        // Fantastical
        } elseif (preg_match(Calendar_Convert_Event_VCalendar_Fantastical::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_FANTASTICAL;
            $version = $matches['version'];

        } else {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC;
            $version = null;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $_userAgent ->  backend: $backend version: $version");
        
        self::$_parsedUserAgentCache[$_userAgent] = array($backend, $version);
        
        return array($backend, $version);
    }

    /**
     * parse useragent and return backend and version
     *
     * @param string $_userAgent
     * @return boolean
     */
    static public function supportsSyncToken($_userAgent)
    {
        $result = false;
        if (Tinebase_Config::getInstance()->get(Tinebase_Config::WEBDAV_SYNCTOKEN_ENABLED)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport enabled');
            list($backend, $version) = self::parseUserAgent($_userAgent);
            switch ($backend) {
                case self::CLIENT_MACOSX:
                    if (version_compare($version, '10.9', '>=')) {
                        $result = true;
                    }
                    break;
                case self::CLIENT_THUNDERBIRD:
                    if (version_compare($version, '4', '>=')) {
                        $result = true;
                    }
                    break;
            }

            if ($result) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                        ' Client ' . $backend . ' version ' . $version . ' supports SyncToken.');
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport disabled');
        }
        return $result;
    }
}
