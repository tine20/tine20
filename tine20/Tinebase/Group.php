<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * primary class to handle groups
 *
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group
{
    const SQL = 'Sql';
    
    const LDAP = 'Ldap';
    
    const TYPO3 = 'Typo3';
    
    /**
     * default admin group name
     * 
     * @var string
     */
    const DEFAULT_ADMIN_GROUP = 'Administrators';
    
    /**
     * default user group name
     * 
     * @var string
     */
    const DEFAULT_USER_GROUP = 'Users';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Group
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Group_Abstract
     */
    public static function getInstance() 
    {
        $backendType = Tinebase_User::getConfiguredBackend();
        if (self::$_instance === NULL) {
            $backendType = Tinebase_User::getConfiguredBackend();
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' groups backend: ' . $backendType);

            self::$_instance = self::factory($backendType);
        }
        
        return self::$_instance;
    }
    
    /**
     * return an instance of the current groups backend
     *
     * @param   string $_backendType name of the groups backend
     * @return  Tinebase_Group_Abstract
     * @throws  Tinebase_Exception_InvalidArgument
     */
    public static function factory($_backendType) 
    {
        switch($_backendType) {
            case self::LDAP:
                $options = Tinebase_User::getBackendConfiguration();
                
                $options['plugins'] = array();
                
                // manage samba sam?
                if (isset(Tinebase_Core::getConfig()->samba) && Tinebase_Core::getConfig()->samba->get('manageSAM', FALSE) == true) {
                    $options['plugins'][] = Tinebase_Group_Ldap::PLUGIN_SAMBA;
                    $options[Tinebase_Group_Ldap::PLUGIN_SAMBA] = Tinebase_Core::getConfig()->samba->toArray();
                }
                
                $result = new Tinebase_Group_Ldap($options);
                
                break;
                
            case self::SQL:
                $result = new Tinebase_Group_Sql();
                break;
            
            case self::TYPO3:
                $result = new Tinebase_Group_Typo3();
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument("Groups backend type $_backendType not implemented.");
        }
        
        return $result;
    }
    
    /**
     * syncronize groupmemberships for given $_username from syncbackend to local sql backend
     * 
     * @todo sync secondary group memberships
     * @param  mixed  $_username  the login id of the user to synchronize
     */
    public static function syncMemberships($_username)
    {
        if ($_username instanceof Tinebase_Model_FullUser) {
            $username = $_username->accountLoginName;
        } else {
            $username = $_username;
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Sync group memberships for: " . $username);
        
        $userBackend  = Tinebase_User::getInstance();
        $groupBackend = Tinebase_Group::getInstance();
        
        $user = $userBackend->getUserByProperty('accountLoginName', $username, 'Tinebase_Model_FullUser');
        
        $membershipsSyncBackend = $groupBackend->getGroupMembershipsFromSyncBackend($user);
        if (! in_array($user->accountPrimaryGroup, $membershipsSyncBackend)) {
            $membershipsSyncBackend[] = $user->accountPrimaryGroup;
        }
        
        $membershipsSqlBackend = $groupBackend->getGroupMemberships($user);
        
        sort($membershipsSqlBackend);
        sort($membershipsSyncBackend);
        if ($membershipsSqlBackend == $membershipsSyncBackend) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Group memberships are already in sync.');
            return;
        }
        
        $newGroupMemberships = array_diff($membershipsSyncBackend, $membershipsSqlBackend);
        foreach ($newGroupMemberships as $groupId) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Add user to groupId " . $groupId);
            // make sure new groups exist in sql backend / create empty group if needed
            try {
                $groupBackend->getGroupById($groupId);
            } catch (Tinebase_Exception_Record_NotDefined $tern) {
                $group = $groupBackend->getGroupByIdFromSyncBackend($groupId);
                $groupBackend->addGroupInSqlBackend($group);
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            .' Set new group memberships: ' . print_r($membershipsSyncBackend, TRUE));
        
        $groupIds = $groupBackend->setGroupMembershipsInSqlBackend($user, $membershipsSyncBackend);
        self::syncListsOfUserContact($groupIds, $user->contact_id);
    }
    
    /**
     * creates or updates addressbook lists for an array of group ids
     * 
     * @param array $groupIds
     * @param string $contactId
     */
    public static function syncListsOfUserContact($groupIds, $contactId)
    {
        // check addressbook and empty contact id (for example cronuser)
        if (! Tinebase_Application::getInstance()->isInstalled('Addressbook') || empty($contactId)) {
            return;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            .' Syncing ' . count($groupIds) . ' group -> lists / memberships');
        
        $listBackend = new Addressbook_Backend_List();
        
        $listIds = array();
        foreach ($groupIds as $groupId) {
            // get single groups to make sure that container id is joined
            $group = Tinebase_Group::getInstance()->getGroupById($groupId);

            $list = NULL;
            if (! empty($group->list_id)) {
                try {
                    $list = $listBackend->get($group->list_id);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        .' List ' . $group->name . ' not found.');
                }
            }
            
            // could not get list by list_id -> try to get by name 
            // if no list can be found, create new one
            if (! $list) {
                $list = $listBackend->getByGroupName($group->name);
                if (! $list) {
                    $list = Addressbook_Controller_List::getInstance()->createByGroup($group);
                }
            }

            if ($group->list_id !== $list->getId()) {
                // list id changed / is new -> update group and make group visible
                $group->list_id = $list->getId();
                $group->visibility = Tinebase_Model_Group::VISIBILITY_DISPLAYED;
                
                Tinebase_Group::getInstance()->updateGroup($group);
            }
            
            $listIds[] = $list->getId();
        }
        
        $listBackend->setMemberships($contactId, $listIds);
    }
    
    /**
     * import groups from sync backend
     *
     */
    public static function syncGroups()
    {
        $groupBackend = Tinebase_Group::getInstance();
        
        $groups = $groupBackend->getGroupsFromSyncBackend(NULL, NULL, 'ASC', NULL, NULL, 'Tinebase_Model_FullUser');
        
        foreach($groups as $group) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' sync group: ' . $group->name);
            // update or create user in local sql backend
            try {
                $sqlGroup = $groupBackend->getGroupById($group);
                $groupBackend->mergeMissingProperties($group, $sqlGroup);
                $groupBackend->updateGroupInSqlBackend($group);
            } catch (Tinebase_Exception_Record_NotDefined $tern) {
                $groupBackend->addGroupInSqlBackend($group);
            }
        }
    }
    
    /**
     * create initial groups
     * 
     * Method is called during Setup Initialization
     */
    public static function createInitialGroups()
    {
        $defaultAdminGroupName = (Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY)) 
            ? Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY)
            : self::DEFAULT_ADMIN_GROUP;
        $adminGroup = new Tinebase_Model_Group(array(
            'name'          => $defaultAdminGroupName,
            'description'   => 'Group of administrative accounts'
        ));
        Tinebase_Group::getInstance()->addGroup($adminGroup);

        $defaultUserGroupName = (Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY))
            ? Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY)
            : self::DEFAULT_USER_GROUP;
        $userGroup = new Tinebase_Model_Group(array(
            'name'          => $defaultUserGroupName,
            'description'   => 'Group of user accounts'
        ));
        Tinebase_Group::getInstance()->addGroup($userGroup);
    }
}
