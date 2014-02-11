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
                $filter = new Addressbook_Model_ListFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_List::LISTTYPE_GROUP
                    )
                ));
                
                $lists = Addressbook_Controller_List::getInstance()->search($filter);
                
                foreach ($lists as $list) {
                    $principals[] = $this->_listToPrincipal($list);
                }
                
                break;
                
            case self::PREFIX_USERS:
                $filter = new Addressbook_Model_ContactFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_Contact::CONTACTTYPE_USER
                    )
                ));
                
                $contacts = Addressbook_Controller_Contact::getInstance()->search($filter);
                
                foreach ($contacts as $contact) {
                    $principals[] = $this->_contactToPrincipal($contact);
                }
                
                
                break;
        }
        
        return $principals;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAVACL\IPrincipalBackend::getPrincipalByPath()
     * @todo resolve real $path
     */
    public function getPrincipalByPath($path) 
    {
        $principal = null;
        
        list($prefix, $id) = \Sabre\DAV\URLUtil::splitPath($path);
        
        switch ($prefix) {
            case self::PREFIX_GROUPS:
                $filter = new Addressbook_Model_ListFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_List::LISTTYPE_GROUP
                    ),
                    array(
                        'field'     => 'id',
                        'operator'  => 'equals',
                        'value'     => $id
                    ),
                ));
                
                $list = Addressbook_Controller_List::getInstance()->search($filter)->getFirstRecord();
                
                if (!$list) {
                    return null;
                }
                
                $principal = $this->_listToPrincipal($list);
                
                break;
                
            case self::PREFIX_USERS:
                $filter = new Addressbook_Model_ContactFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_Contact::CONTACTTYPE_USER
                    ),
                    array(
                        'field'     => 'id',
                        'operator'  => 'equals',
                        'value'     => $id
                    ),
                ));
                
                $contact = Addressbook_Controller_Contact::getInstance()->search($filter)->getFirstRecord();
                
                if (!$contact) {
                    return null;
                }
                
                $principal = $this->_contactToPrincipal($contact);
                
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
        
        list($path, $listId) = Sabre\DAV\URLUtil::splitPath($principal);
        
        if ($path == self::PREFIX_GROUPS) {
            $filter = new Addressbook_Model_ListFilter(array(
                array(
                    'field'     => 'type',
                    'operator'  => 'equals',
                    'value'     => Addressbook_Model_List::LISTTYPE_GROUP
                ),
                array(
                    'field'     => 'id',
                    'operator'  => 'equals',
                    'value'     => $listId
                ),
            ));
            
            $list = Addressbook_Controller_List::getInstance()->search($filter)->getFirstRecord();
            
            if (!$list) {
                return array();
            }
            
            foreach ($list->members as $member) {
                $result[] = self::PREFIX_USERS . '/' . $member;
            }
        }
        
        return $result;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAVACL\IPrincipalBackend::getGroupMembership()
     */
    public function getGroupMembership($principal) 
    {
        $result = array();
        
        list($path, $contactId) = Sabre\DAV\URLUtil::splitPath($principal);
        
        if ($path == self::PREFIX_USERS) {
            $user = Tinebase_User::getInstance()->getUserByProperty('contactId', $contactId);
            
            $groupIds = Tinebase_Group::getInstance()->getGroupMemberships($user);
            $groups   = Tinebase_Group::getInstance()->getMultiple($groupIds);
            
            foreach ($groups as $group) {
                $result[] = self::PREFIX_GROUPS . '/' . $group->list_id;
            }
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
            case self::PREFIX_GROUPS:
                $filter = new Addressbook_Model_ListFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_List::LISTTYPE_GROUP
                    )
                ));
                
                if (!empty($searchProperties['{http://calendarserver.org/ns/}search-token'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{http://calendarserver.org/ns/}search-token']
                    )));
                }
                
                $result = Addressbook_Controller_List::getInstance()->search($filter, null, false, true);
                
                foreach ($result as $listId) {
                    $principalUris[] = $prefixPath . '/' . $listId;
                }
                
                break;
                
            case self::PREFIX_USERS:
                $filter = new Addressbook_Model_ContactFilter(array(
                    array(
                        'field'     => 'type',
                        'operator'  => 'equals',
                        'value'     => Addressbook_Model_Contact::CONTACTTYPE_USER
                    )
                ));
                
                if (!empty($searchProperties['{http://calendarserver.org/ns/}search-token'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{http://calendarserver.org/ns/}search-token']
                    )));
                }
                
                if (!empty($searchProperties['{http://sabredav.org/ns}email-address'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'email_query',
                        'operator'  => 'equals',
                        'value'     => $searchProperties['{http://sabredav.org/ns}email-address']
                    )));
                }
                
                $result = Addressbook_Controller_Contact::getInstance()->search($filter, null, false, true);
                
                foreach ($result as $contactId) {
                    $principalUris[] = $prefixPath . '/' . $contactId;
                }
                
                break;
        }
        
        return $principalUris;
    }
    
    /**
     * convert contact model to principal array
     * 
     * @param Addressbook_Model_Contact $contact
     * @return array
     */
    protected function _contactToPrincipal(Addressbook_Model_Contact $contact)
    {
        $principal = array(
            'uri'                     => self::PREFIX_USERS . '/' . $contact->getId(),
            '{DAV:}displayname'       => $contact->n_fileas,
            '{DAV:}alternate-URI-set' => array('urn:uuid:' . $contact->getId()),
            
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => 'INDIVIDUAL',
            
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'users',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'  => $contact->n_given,
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'   => $contact->n_family
        );
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $principal['{http://sabredav.org/ns}email-address'] = $contact->email;
        }
        
        return $principal;
    }
    
    /**
     * convert list model to principal array
     * 
     * @param Addressbook_Model_List $list
     * @return array
     */
    protected function _listToPrincipal(Addressbook_Model_List $list)
    {
        $principal = array(
            'uri'                     => self::PREFIX_GROUPS . '/' . $list->getId(),
            '{DAV:}displayname'       => $list->name . ' (Group)',
            '{DAV:}alternate-URI-set' => array('urn:uuid:' . $list->getId()),
            
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => 'GROUP',
            
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'groups',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'  => 'Group',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'   => $list->name,
        );
        
        return $principal;
    }
}
