<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * principal backend class
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_PrincipalBackend implements \Sabre\DAVACL\PrincipalBackend\BackendInterface
{
    const PREFIX_USERS  = 'principals/users';
    const PREFIX_GROUPS = 'principals/groups';
    const SHARED        = 'shared';
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAVACL\IPrincipalBackend::getPrincipalsByPrefix()
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
                
                $principals[] = $this->_contactForSharedPrincipal();
                
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
        
        // special handling for calendar proxy principals
        // they are groups in the user namespace
        if (in_array($id, array('calendar-proxy-read', 'calendar-proxy-write'))) {
            $path = $prefix;
            
            // set prefix to calendar-proxy-read or calendar-proxy-write
            $prefix = $id;
            
            list(, $id) = \Sabre\DAV\URLUtil::splitPath($path);
        }
        
        switch ($prefix) {
            case 'calendar-proxy-read':
                return null;
                
                break;
                
            case 'calendar-proxy-write':
                // does the account exist
                $contactPrincipal = $this->getPrincipalByPath(self::PREFIX_USERS . '/' . $id);
                
                if (!$contactPrincipal) {
                    return null;
                }
                
                $principal = array(
                    'uri'                     => $contactPrincipal['uri'] . '/' . $prefix,
                    '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => 'GROUP',
                    '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'groups'
                );
                
                break;
                
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
                if ($id === self::SHARED) {
                    $principal = $this->_contactForSharedPrincipal();
                    
                } else {
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
        
        list($prefix, $id) = \Sabre\DAV\URLUtil::splitPath($principal);
        
        // special handling for calendar proxy principals
        // they are groups in the user namespace
        if (in_array($id, array('calendar-proxy-read', 'calendar-proxy-write'))) {
            $path = $prefix;
            
            // set prefix to calendar-proxy-read or calendar-proxy-write
            $prefix = $id;
            
            list(, $id) = \Sabre\DAV\URLUtil::splitPath($path);
        }
        
        switch ($prefix) {
            case 'calendar-proxy-read':
                return array();
                
                break;
                
            case 'calendar-proxy-write':
                if ($id === self::SHARED) {
                    // check if account has the right to run the calendar at all
                    if (!Tinebase_Acl_Roles::getInstance()->hasRight('Calendar', Tinebase_Core::getUser(), Tinebase_Acl_Rights::RUN)) {
                        return array();
                    }
                    
                    // collect all users which have access to any of the calendars of this user
                    $sharedContainerSync = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), 'Calendar_Model_Event', Tinebase_Model_Grants::GRANT_SYNC);
                    
                    if ($sharedContainerSync->count() > 0) {
                        $sharedContainerRead = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), 'Calendar_Model_Event', Tinebase_Model_Grants::GRANT_READ);
                        
                        $sharedContainerIds = array_intersect($sharedContainerSync->getArrayOfIds(), $sharedContainerRead->getArrayOfIds());
                        
                        $result = $this->_containerGrantsToPrincipals($sharedContainerSync->filter('id', $sharedContainerIds));
                    }
                    
                } else {
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
                    
                    if (!$contact instanceof Addressbook_Model_Contact || !$contact->account_id) {
                        return array();
                    }
                    
                    // check if account has the right to run the calendar at all
                    if (!Tinebase_Acl_Roles::getInstance()->hasRight('Calendar', $contact->account_id, Tinebase_Acl_Rights::RUN)) {
                        return array();
                    }
                    
                    // collect all users which have access to any of the calendars of this user
                    $personalContainerSync = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), 'Calendar_Model_Event', $contact->account_id, Tinebase_Model_Grants::GRANT_SYNC);
                    
                    if ($personalContainerSync->count() > 0) {
                        $personalContainerRead = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), 'Calendar_Model_Event', $contact->account_id, Tinebase_Model_Grants::GRANT_READ);
                        
                        $personalContainerIds = array_intersect($personalContainerSync->getArrayOfIds(), $personalContainerRead->getArrayOfIds());
                        
                        $result = $this->_containerGrantsToPrincipals($personalContainerSync->filter('id', $personalContainerIds));
                    }
                }
                
                break;
                
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
                    return array();
                }
                
                foreach ($list->members as $member) {
                    $result[] = self::PREFIX_USERS . '/' . $member;
                }
                
                break;
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
        
        list($prefix, $contactId) = \Sabre\DAV\URLUtil::splitPath($principal);
        
        switch ($prefix) {
            case self::PREFIX_GROUPS:
                // @TODO implement?
                break;
        
            case self::PREFIX_USERS:
                if ($contactId !== self::SHARED) {
                    $user = Tinebase_User::getInstance()->getUserByProperty('contactId', $contactId);
                    
                    $groupIds = Tinebase_Group::getInstance()->getGroupMemberships($user);
                    $groups   = Tinebase_Group::getInstance()->getMultiple($groupIds);
                    
                    foreach ($groups as $group) {
                        if ($group->list_id && $group->visibility == Tinebase_Model_Group::VISIBILITY_DISPLAYED) {
                            $result[] = self::PREFIX_GROUPS . '/' . $group->list_id;
                        }
                    }
                    
                    // return user only, if the containers have the sync AND read grant set
                    $otherUsersSync = Tinebase_Container::getInstance()->getOtherUsers($user, 'Calendar', Tinebase_Model_Grants::GRANT_SYNC);
                    
                    if ($otherUsersSync->count() > 0) {
                        $otherUsersRead = Tinebase_Container::getInstance()->getOtherUsers($user, 'Calendar', Tinebase_Model_Grants::GRANT_READ);
                        
                        $otherUsersIds = array_intersect($otherUsersSync->getArrayOfIds(), $otherUsersRead->getArrayOfIds());
                        
                        foreach ($otherUsersIds as $userId) {
                            if ($otherUsersSync->getById($userId)->contact_id && $otherUsersSync->getById($userId)->visibility == Tinebase_Model_User::VISIBILITY_DISPLAYED) {
                                $result[] = self::PREFIX_USERS . '/' . $otherUsersSync->getById($userId)->contact_id . '/calendar-proxy-write';
                            }
                        }
                    }
                    
                    // return user only, if the containers have the sync AND read grant set
                    $sharedContainersSync = Tinebase_Container::getInstance()->getSharedContainer($user, 'Calendar', Tinebase_Model_Grants::GRANT_SYNC);
                    
                    if ($sharedContainersSync->count() > 0) {
                        $sharedContainersRead = Tinebase_Container::getInstance()->getSharedContainer($user, 'Calendar', Tinebase_Model_Grants::GRANT_READ);
                        
                        $sharedContainerIds = array_intersect($sharedContainersSync->getArrayOfIds(), $sharedContainersRead->getArrayOfIds());
                        
                        if (count($sharedContainerIds) > 0) {
                            $result[] = self::PREFIX_USERS . '/' . self::SHARED . '/calendar-proxy-write';
                        }
                    }
                }
                
                break;
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
     * @todo implement handling for shared pseudo user
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
                
                if (!empty($searchProperties['{DAV:}displayname'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'name',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{DAV:}displayname']
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
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{http://sabredav.org/ns}email-address']
                    )));
                }
                
                if (!empty($searchProperties['{DAV:}displayname'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'query',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{DAV:}displayname']
                    )));
                }
                
                if (!empty($searchProperties['{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'n_given',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name']
                    )));
                }
                
                if (!empty($searchProperties['{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'])) {
                    $filter->addFilter($filter->createFilter(array(
                        'field'     => 'n_family',
                        'operator'  => 'contains',
                        'value'     => $searchProperties['{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name']
                    )));
                }
                
                #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                #    __METHOD__ . '::' . __LINE__ . ' path: ' . $prefixPath . ' properties: ' . print_r($filter->toArray(), true));
                
                $result = Addressbook_Controller_Contact::getInstance()->search($filter, null, false, true);
                
                foreach ($result as $contactId) {
                    $principalUris[] = $prefixPath . '/' . $contactId;
                }
                
                break;
        }
        
        return $principalUris;
    }
    
    /**
     * return shared pseudo principal (principal for the shared containers) 
     */
    protected function _contactForSharedPrincipal()
    {
        $principal = array(
            'uri'                     => self::PREFIX_USERS . '/' . self::SHARED,
            '{DAV:}displayname'       => 'Principal for shared folders',
            
            '{' . \Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-type'  => 'INDIVIDUAL',
            
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}record-type' => 'users',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}first-name'  => 'Folders',
            '{' . \Sabre\CalDAV\Plugin::NS_CALENDARSERVER . '}last-name'   => 'Shared'
        );
        
        return $principal;
        
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
    
    protected function _containerGrantsToPrincipals(Tinebase_Record_RecordSet $containers)
    {
        $result = array();
        
        foreach ($containers as $container) {
            $grants = Tinebase_Container::getInstance()->getGrantsOfContainer($container);
            
            foreach ($grants as $grant) {
                switch ($grant->account_type) {
                    case 'group':
                        $group = Tinebase_Group::getInstance()->getGroupById($grant->account_id);
                        if ($group->list_id) {
                            $result[] = self::PREFIX_GROUPS . '/' . $group->list_id;
                        }
                        break;
                        
                    case 'user':
                        // skip if grant belongs to the owner of the calendar
                        if ($contact->account_id == $grant->account_id) {
                            continue;
                        }
                        $user = Tinebase_User::getInstance()->getUserById($grant->account_id);
                        if ($user->contact_id) {
                            $result[] = self::PREFIX_USERS . '/' . $user->contact_id;
                        }
                        
                        break;
                }
            }
        }
        
        // users and groups can be duplicate
        $result = array_unique($result);
        
        return $result;
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
