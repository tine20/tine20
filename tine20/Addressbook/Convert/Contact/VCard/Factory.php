<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * addressbook vcard convert factory class
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_Factory
{
    const CLIENT_GENERIC        = 'generic';
    const CLIENT_IOS            = 'ios';
    const CLIENT_KDE            = 'kde';
    const CLIENT_MACOSX         = 'macosx';
    const CLIENT_SOGO           = 'sogo';
    const CLIENT_EMCLIENT       = 'emclient';
    const CLIENT_EMCLIENT7      = 'emclient7';
    const CLIENT_COLLABORATOR   = 'WebDAVCollaborator';
    const CLIENT_AKONADI        = 'akonadi';
    const CLIENT_EVOLUTION      = 'evolution';
    const CLIENT_TELEFONBUCH    = 'telefonbuch';
    const CLIENT_DAVDROID       = 'davdroid';
    const CLIENT_CARDDAVSYNC    = 'org.dmfs.carddav.sync';
    const CLIENT_CALDAVSYNCHRONIZER = 'caldavsynchronizer';
    const CLIENT_CARDBOOK       = 'CardBook';
    
    /**
     * cache parsed user-agent strings
     * 
     * @var array
     */
    static protected $_parsedUserAgentCache = array();
    
    /**
     * factory function to return a selected phone backend class
     *
     * @param   string $_backend
     * @param   string $_version
     * @return  Addressbook_Convert_Contact_VCard_Abstract
     */
    static public function factory($_backend, $_version = null)
    {
        switch ($_backend) {
            case self::CLIENT_GENERIC:
                return new Addressbook_Convert_Contact_VCard_Generic($_version);
                
            case self::CLIENT_IOS:
                return new Addressbook_Convert_Contact_VCard_IOS($_version);
                
            case self::CLIENT_KDE:
                return new Addressbook_Convert_Contact_VCard_KDE($_version);
                
            case self::CLIENT_AKONADI:
                return new Addressbook_Convert_Contact_VCard_Akonadi($_version);
            
            case self::CLIENT_MACOSX:
                return new Addressbook_Convert_Contact_VCard_MacOSX($_version);
                
            case self::CLIENT_SOGO:
                return new Addressbook_Convert_Contact_VCard_Sogo($_version);
                
            case self::CLIENT_EMCLIENT:
                return new Addressbook_Convert_Contact_VCard_EMClient($_version);
                
            case self::CLIENT_EMCLIENT7:
                return new Addressbook_Convert_Contact_VCard_EMClient7($_version);

            case self::CLIENT_COLLABORATOR:
                return new Addressbook_Convert_Contact_VCard_WebDAVCollaborator($_version);

            case self::CLIENT_DAVDROID:
                return new Addressbook_Convert_Contact_VCard_DavDroid($_version);

            case self::CLIENT_CARDDAVSYNC:
                return new Addressbook_Convert_Contact_VCard_CardDAVSync($_version);

            case self::CLIENT_CALDAVSYNCHRONIZER:
                return new Addressbook_Convert_Contact_VCard_CalDAVSynchronizer($_version);

            case self::CLIENT_TELEFONBUCH:
                return new Addressbook_Convert_Contact_VCard_Telefonbuch($_version);

            case self::CLIENT_EVOLUTION:
                return new Addressbook_Convert_Contact_VCard_Evolution($_version);

            case self::CLIENT_CARDBOOK:
                return new Addressbook_Convert_Contact_VCard_CardBook($_version);
	    }
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
        if (preg_match(Addressbook_Convert_Contact_VCard_MacOSX::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_MACOSX;
            $version = $matches['version'];
        
        // Thunderbird with Sogo Connector
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_Sogo::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_SOGO;
            $version = $matches['version'];
        
        // iOS addressbook
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_IOS::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_IOS;
            $version = $matches['version'];
        
        // KDE addressbook
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_KDE::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_KDE;
            $version = isset($matches['version']) ? $matches['version'] : '0.0' ;
        
        // Akonadi DAV addressbook
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_Akonadi::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_AKONADI;
            $version = $matches['version'];
            
        // eM Client 7 addressbook
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_EMClient7::HEADER_MATCH, $_userAgent, $matches) && (floor($matches['version']) >= 7)) {
            $backend = self::CLIENT_EMCLIENT7;
            $version = $matches['version'];

        // eM Client addressbook
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_EMClient::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_EMCLIENT;
            $version = $matches['version'];
        
        // Outlook WebDAV Collaborator
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_WebDAVCollaborator::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_COLLABORATOR;
            $version = $matches['version'];

        // DavDROID
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_DavDroid::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_DAVDROID;
            $version = $matches['version'];

        // DMFS CardDAVSync
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_CardDAVSync::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_CARDDAVSYNC;
            $version = $matches['version'];

        // Evolution 
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_Evolution::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_EVOLUTION;
            $version = $matches['version'];
        
        // CalDAVSynchronizer
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_CalDAVSynchronizer::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = Calendar_Convert_Event_VCalendar_Factory::CLIENT_CALDAVSYNCHRONIZER;
            $version = $matches['version'];

        // CardBook
        } elseif (preg_match(Addressbook_Convert_Contact_VCard_CardBook::HEADER_MATCH, $_userAgent, $matches)) {
            $backend = self::CLIENT_CARDBOOK;
            $version = $matches['version'];

        // generic client
        } else {
            $backend = self::CLIENT_GENERIC;
            $version = null;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " $_userAgent ->  backend: $backend version: $version");
        
        self::$_parsedUserAgentCache[$_userAgent] = array($backend, $version);
        
        return array($backend, $version);
    }
    
    /**
     * returns CalDAV user agent
     * 
     * @return array($agent, $version)
     */
    public static function getUserAgent()
    {
        $userAgent = (isset($_SERVER['HTTP_USER_AGENT']) || array_key_exists('HTTP_USER_AGENT', $_SERVER)) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
        
        return self::parseUserAgent($userAgent);
    }
}
