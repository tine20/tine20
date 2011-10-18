<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * addressbook vcard convert factory class
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_Factory
{
    const CLIENT_SOGO       = 'sogo';
    const CLIENT_MACOSX     = 'macosx';
    const CLIENT_GENERIC    = 'generic';
    
    /**
	 * factory function to return a selected phone backend class
	 *
	 * @param   string $_backend
	 * @param   string $_version
	 * @return  Addressbook_Convert_Contact_VCard_Interface
	 */
	static public function factory($_backend, $_version = null)
	{
	    switch ($_backend) {
	        case Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC:
	            return new Addressbook_Convert_Contact_VCard_Generic($_version);
	            
	            break;
	            
	        case Addressbook_Convert_Contact_VCard_Factory::CLIENT_MACOSX:
	            return new Addressbook_Convert_Contact_VCard_MacOSX($_version);
	            
	            break;
	            
            case Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO:
                return new Addressbook_Convert_Contact_VCard_Sogo($_version);
                 
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
        // AddressBook/6.0 (1043) CardDAVPlugin/182 CFNetwork/520.0.13 Mac_OS_X/10.7.1 (11B26)
        if (preg_match('/^AddressBook.*Mac_OS_X\/(?P<version>.*) /', $_userAgent, $matches)) {
            $backend = Addressbook_Convert_Contact_VCard_Factory::CLIENT_MACOSX;
            $version = $matches['version'];
        
            // Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.2.21) Gecko/20110831 Lightning/1.0b2 Thunderbird/3.1.13
        } elseif (preg_match('/ Thunderbird\/(?P<version>.*)/', $_userAgent, $matches)) {
            $backend = Addressbook_Convert_Contact_VCard_Factory::CLIENT_SOGO;
            $version = $matches['version'];
        
            // generic client
        } else {
            $backend = Addressbook_Convert_Contact_VCard_Factory::CLIENT_GENERIC;
            $version = null;
        }
        
        return array($backend, $version);
	}
}
