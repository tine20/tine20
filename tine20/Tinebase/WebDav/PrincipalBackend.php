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
        
        if (Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN)) {
            try {
                $defaultCalendar = Tinebase_Core::getPreference('Calendar')->getValue(Calendar_Preference::DEFAULTCALENDAR);
                
                $principal['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL']  = $defaultCalendar;
                $principal['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL'] = $defaultCalendar;
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' no default calendar found');
            }
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
        
        if (Tinebase_Core::getUser()->hasRight('Calendar', Tinebase_Acl_Rights::RUN)) {
            try {
                $defaultCalendar = Tinebase_Core::getPreference('Calendar')->getValue(Calendar_Preference::DEFAULTCALENDAR);
                
                $principal['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-inbox-URL']  = $defaultCalendar;
                $principal['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-outbox-URL'] = $defaultCalendar;
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' no default calendar found');
            }
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
        
        list(, $contactId) = Sabre_DAV_URLUtil::splitPath($principal);
        
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
}
