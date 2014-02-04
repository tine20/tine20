<?php

use Sabre\DAVACL;

/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class to handle webdav principals
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_PrincipalBackend implements DAVACL\PrincipalBackend\BackendInterface
{
    const PREFIX_USERS  = 'principals/users';
    const PREFIX_GROUPS = 'principals/groups';
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAVACL\IPrincipalBackend::getPrincipalsByPrefix()
     * @todo currently we return the current user and his groups only // we should return ALL users/groups here
     */
    public function getPrincipalsByPrefix($prefixPath) 
    {
        $principals = array();
        
        switch ($prefixPath) {
            case self::PREFIX_GROUPS:
                $groups = Tinebase_Group::getInstance()->getMultiple(Tinebase_Core::getUser()->getGroupMemberships());
                
                foreach ($groups as $group) {
                    if (empty($group->list_id)) {
                        continue;
                    }
                    $principals[] = array(
                        'uri'               => self::PREFIX_GROUPS . '/' . $group->list_id,
                        '{DAV:}displayname' => $group->name
                    );
                }
                
                
                break;
                
            case self::PREFIX_USERS:
                $principal = array(
                    'uri'               => self::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id,
                    '{DAV:}displayname' => Tinebase_Core::getUser()->accountDisplayName
                );
                
                if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
                    $principal['{http://sabredav.org/ns}email-address'] = Tinebase_Core::getUser()->accountEmailAddress;
                }
                
                $principals[] = $principal;
                
                break;
        }
        
        return $principals;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAVACL\IPrincipalBackend::getPrincipalByPath()
     * @todo implement search for users and groups
     */
    public function getPrincipalByPath($path) 
    {
        $principal = null;
        
        list($prefix, $name) = \Sabre\DAV\URLUtil::splitPath($path);
        
        switch ($prefix) {
            case self::PREFIX_GROUPS:
                $principal = array(
                    'uri'               => self::PREFIX_GROUPS . '/' . $name,
                    '{DAV:}displayname' => 'Tine 2.0 group ' . $name
                );
                
                break;
                
            case self::PREFIX_USERS:
                $principal = array(
                    'uri'               => self::PREFIX_USERS . '/' . Tinebase_Core::getUser()->contact_id,
                    '{DAV:}displayname' => Tinebase_Core::getUser()->accountDisplayName
                );
                
                if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
                    $principal['{http://sabredav.org/ns}email-address'] = Tinebase_Core::getUser()->accountEmailAddress;
                }
                
                break;
        }
        
        return $principal;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAVACL\IPrincipalBackend::getGroupMemberSet()
     */
    public function getGroupMemberSet($principal) 
    {
        $result = array();
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAVACL\IPrincipalBackend::getGroupMembership()
     */
    public function getGroupMembership($principal) 
    {
        $result = array();
        
        list(, $contactId) = Sabre\DAV\URLUtil::splitPath($principal);
        
        $user = Tinebase_User::getInstance()->getUserByProperty('contactId', $contactId);
        
        $groupIds = Tinebase_Group::getInstance()->getGroupMemberships($user);
        $groups   = Tinebase_Group::getInstance()->getMultiple($groupIds);
        
        foreach ($groups as $group) {
            $result[] = 'principals/groups/' . $group->list_id;
        }
        
        return $result;
    }
    
    public function setGroupMemberSet($principal, array $members) 
    {
        // do nothing
    }
    
    public function updatePrincipal($path, $mutations)
    {
        return false;
    }
    
    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT. You should at least allow searching on
     * http://sabredav.org/ns}email-address.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * If multiple properties are being searched on, the search should be
     * AND'ed.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param array $searchProperties
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' path: ' . $prefixPath . ' properties: ' . print_r($searchProperties, true));
        
        $principalUris = array();
        
        switch ($prefixPath) {
            case self::PREFIX_USERS:
                if (!empty($searchProperties['{http://sabredav.org/ns}email-address'])) {
                    $filter = new Addressbook_Model_ContactFilter(array(
                        array(
                            'field'     => 'email_query',
                            'operator'  => 'equals',
                            'value'     => $searchProperties['{http://sabredav.org/ns}email-address']
                        ),
                        array(
                            'field'     => 'type',
                            'operator'  => 'equals',
                            'value'     => Addressbook_Model_Contact::CONTACTTYPE_USER
                        )
                    ));
                    
                    $result = Addressbook_Controller_Contact::getInstance()->search($filter, null, false, true);
                    
                    if (count($result) > 0) {
                        $principalUris[] = 'principals/users/' . $result[0];
                    }
                }
                
                break;
        }
        
        return $principalUris;
    }
}
