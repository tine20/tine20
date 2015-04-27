<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Session
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Guilherme Striquer Bisotto <guilherme.bisotto@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Session and Session Namespaces for Tinebase_User
 *
 * @package     Expressomail
 * @subpackage  Session
 */
class Expressomail_Session extends Tinebase_Session_Abstract {    
    
    /**
     * Session namespace for Expressomail
     */
    const EXPRESSOMAIL_SESSION_NAMESPACE = 'Expressomail_Session_Namespace';
    
	/**
	 * Gets Expressomail session namespace
	 * 
	 * @throws Exception
	 * @return Ambigous <Zend_Session_Namespace, NULL, mixed>
	 */
	public static function getSessionNamespace()
	{	    
	    try {
		   return self::_getSessionNamespace(self::EXPRESSOMAIL_SESSION_NAMESPACE);
	    } catch(Exception $e) {	        
	        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Session error: ' . $e->getMessage());
	        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());	        
	        throw $e;
	    }	    
	}    
}