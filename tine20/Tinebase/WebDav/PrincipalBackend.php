<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav principals
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_PrincipalBackend implements Sabre_DAVACL_IPrincipalBackend
{
    /**
     * (non-PHPdoc)
     * @see Sabre_DAVACL_IPrincipalBackend::getPrincipalsByPrefix()
     */
    public function getPrincipalsByPrefix($prefixPath) 
    {
        $principals = array();
        
        $principal = array(
            'uri'                                   => 'principals/users/' . Tinebase_Core::getUser()->contact_id,
            '{DAV:}displayname'                     => Tinebase_Core::getUser()->accountDisplayName
        );
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
        	$principal['{http://sabredav.org/ns}email-address'] = Tinebase_Core::getUser()->accountEmailAddress;
        }
        
        $principals[] = $principal;
        
        return $principals;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAVACL_IPrincipalBackend::getPrincipalByPath()
     */
    public function getPrincipalByPath($path) 
    {
        $principal = array(
            'uri'                                   => 'principals/users/' . Tinebase_Core::getUser()->contact_id,
            '{DAV:}displayname'                     => Tinebase_Core::getUser()->accountDisplayName
        );
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
        	$principal['{http://sabredav.org/ns}email-address'] = Tinebase_Core::getUser()->accountEmailAddress;
        }
        
        return $principal;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAVACL_IPrincipalBackend::getGroupMemberSet()
     */
    public function getGroupMemberSet($principal) 
    {
        $result = array();
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAVACL_IPrincipalBackend::getGroupMembership()
     */
    public function getGroupMembership($principal) 
    {
        $result = array();
        
        return $result;
    }
    
    public function setGroupMemberSet($principal, array $members) 
    {
        // do nothing
    }
}
