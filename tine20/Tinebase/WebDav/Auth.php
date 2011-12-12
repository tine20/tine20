<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav authentication
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Auth implements Sabre_DAV_Auth_IBackend
{
    /**
     * (non-PHPdoc)
     * @see Sabre_DAV_Auth_IBackend::getCurrentUser()
     */
    public function getCurrentUser()
    {
        return Tinebase_Core::getUser()->contact_id;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAV_Auth_IBackend::authenticate()
     */
    public function authenticate(Sabre_DAV_Server $_server, $_realm) 
    {
        if (Tinebase_Core::getUser() instanceof Tinebase_Model_FullUser) {
            return true;
        }
        
        return false;
    }
}
