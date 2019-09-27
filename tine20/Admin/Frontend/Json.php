<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        try to split this into smaller parts (record proxy should support 'nested' json frontends first)
 * @todo        use functions from Tinebase_Frontend_Json_Abstract
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the admin application
 *
 * @package     Admin
 * @subpackage  Frontend
 */
class Admin_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the application name
     *
     * @var string
     */
    protected $_applicationName = 'Admin';
    
    /**
     * @var bool
     */
    protected $_manageSAM = false;
    
    /**
     * constructs Admin_Frontend_Json
     */
    public function __construct()
    {
        // manage samba sam?
        if (isset(Tinebase_Core::getConfig()->samba)) {
            $this->_manageSAM = Tinebase_Core::getConfig()->samba->get('manageSAM', false);
        }
    }
    
    /**
     * Returns registry data of admin.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();

        $registryData = array(
            'manageSAM'                     => $this->_manageSAM,
            'defaultPrimaryGroup'           => Tinebase_Group::getInstance()->getDefaultGroup()->toArray(),
            'defaultInternalAddressbook'    => (
                    isset($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])
                    && $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK] !== NULL)
                ? Tinebase_Container::getInstance()->get($appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK])->toArray() 
                : NULL,
        );

        return $registryData;
    }
    
    /******************************* Access Log *******************************/
    
    /**
     * delete access log entries
     *
     * @param array $ids list of logIds to delete
     * @return array with success flag
     */
    public function deleteAccessLogs($ids)
    {
        return $this->_delete($ids, Admin_Controller_AccessLog::getInstance());
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchAccessLogs($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_AccessLog::getInstance(), 'Tinebase_Model_AccessLogFilter');
        
        return $result;
    }

    /****************************** Applications ******************************/
    
    /**
     * get application
     *
     * @param   int $applicationId application id to get
     * @return  array with application data
     * 
     */
    public function getApplication($applicationId)
    {
        $application = Admin_Controller_Application::getInstance()->get($applicationId);
        
        return $application->toArray();
    }
    
    /**
     * get list of applications
     *
     * @param string $filter
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
     * @return array with results array & totalcount (int)
     * 
     * @todo switch to new api with only filter and paging params
     */
    public function getApplications($filter, $sort, $dir, $start, $limit)
    {
        if (empty($filter)) {
            $filter = NULL;
        }
        
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $applicationSet = Admin_Controller_Application::getInstance()->search($filter, $sort, $dir, $start, $limit);

        $result['results']    = $applicationSet->toArray();
        if ($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = Admin_Controller_Application::getInstance()->getTotalApplicationCount($filter);
        }
        
        return $result;
    }

    /**
     * set application state
     *
     * @param   array  $applicationIds  array of application ids
     * @param   string $state           state to set
     * @return  array with success flag
     */
    public function setApplicationState($applicationIds, $state)
    {
        Admin_Controller_Application::getInstance()->setApplicationState($applicationIds, $state);

        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }
            
    /********************************** Users *********************************/
    
    /**
     * returns a fullUser
     *
     * @param string $id
     * @return array
     */
    public function getUser($id)
    {
        if (!empty($id)) {
            $user = Admin_Controller_User::getInstance()->get($id);
            $userArray = $this->_recordToJson($user);
            
            // don't send some infos to the client: unset email uid+gid
            if ((isset($userArray['emailUser']) || array_key_exists('emailUser', $userArray))) {
                $unsetFields = array('emailUID', 'emailGID');
                foreach ($unsetFields as $field) {
                    unset($userArray['emailUser'][$field]);
                }

                if(isset($userArray['imapUser']['emailMailSize']) && ($userArray['imapUser']['emailMailQuota'] !== null)) {
                    $userArray['emailUser']['emailMailSize'] = $userArray['imapUser']['emailMailSize'];
                    $userArray['emailUser']['emailMailQuota'] = $userArray['imapUser']['emailMailQuota'];
                }
            }
            
            // add primary group to account for the group selection combo box
            $group = Tinebase_Group::getInstance()->getGroupById($user->accountPrimaryGroup);
            
            $userGroups = Tinebase_Group::getInstance()->getMultiple(Tinebase_Group::getInstance()->getGroupMemberships($user->accountId))->toArray();
            
            try {
                $roleMemberships = Tinebase_Acl_Roles::getInstance()->getRoleMemberships($user->accountId);
                $userRoles = Tinebase_Acl_Roles::getInstance()->getMultiple($roleMemberships)->toArray();
            } catch (Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::CRIT)) Tinebase_Core::getLogger()->crit(__METHOD__ . '::' . __LINE__ . 
                    ' Failed to fetch role memberships for user ' . $user->accountFullName . ': ' . $tenf->getMessage()
                );
                $userRoles = array();
            }
            
            
        } else {
            $userArray = array('accountStatus' => 'enabled', 'visibility' => 'displayed');
            
            // get default primary group for the group selection combo box
            $group = Tinebase_Group::getInstance()->getDefaultGroup();
            
            // no user groups by default
            $userGroups = array();
            
            // no user roles by default
            $userRoles = array();
        }
        
        // encode the account array
        $userArray['accountPrimaryGroup'] = $group->toArray();
        
        // encode the groups array
        $userArray['groups'] = array(
            'results'         => $userGroups,
            'totalcount'     => count($userGroups)
        );
        
        // encode the roles array
        $userArray['accountRoles'] = array(
            'results'         => $userRoles,
            'totalcount'     => count($userRoles)
        );
        
        return $userArray;
    }
    
    /**
     * get list of accounts
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     * 
     * @todo switch to new api with only filter and paging params
     */
    public function getUsers($filter, $sort, $dir, $start, $limit)
    {
        $accounts = Admin_Controller_User::getInstance()->searchFullUsers($filter, $sort, $dir, $start, $limit);
        $results = array();
        foreach ($this->_multipleRecordsToJson($accounts) as $val) {
            $val['filesystemSize'] = null;
            $val['filesystemRevisionSize'] = null;
            $results[$val['accountId']] = $val;
        }

        if (Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            $accountIds = $accounts->getId();
            /** @var Tinebase_Model_Tree_Node $node */
            foreach (Tinebase_FileSystem::getInstance()->searchNodes(new Tinebase_Model_Tree_Node_Filter(array(
                array('field' => 'path', 'operator' => 'equals', 'value' => '/Filemanager/folders/personal'),
                array('field' => 'name', 'operator' => 'in', 'value' => $accountIds)
            ), '', array('ignoreAcl' => true))) as $node) {
                if (isset($results[$node->name])) {
                    $results[$node->name]['filesystemSize'] = $node->size;
                    $results[$node->name]['filesystemRevisionSize'] = $node->revision_size;
                }
            }
        }

        $result = array(
            'results'     => array_values($results),
            'totalcount'  => Admin_Controller_User::getInstance()->searchCount($filter)
        );
        
        return $result;
    }

    /**
     * search for users/accounts
     * 
     * @param array $filter
     * @param array $paging
     * @return array with results array & totalcount (int)
     */
    public function searchUsers($filter, $paging)
    {
        $sort = (isset($paging['sort']))    ? $paging['sort']   : 'accountDisplayName';
        $dir  = (isset($paging['dir']))     ? $paging['dir']    : 'ASC';
        
        $result = $this->getUsers($filter[0]['value'], $sort, $dir, isset($paging['start']) ? $paging['start'] : 0,
            isset($paging['limit']) ? $paging['limit'] : null);
        $result['filter'] = $filter[0];
        
        return $result;
    }
    
    /**
     * Search for groups matching given arguments
     *
     * @param  array $_filter
     * @param  array $_paging
     * @return array
     * 
     * @todo replace this by Admin.searchGroups / getGroups (without acl check)? or add getGroupCount to Tinebase_Group
     */
    public function searchGroups($filter, $paging)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        // old fn style yet
        $sort = (isset($paging['sort']))    ? $paging['sort']   : 'name';
        $dir  = (isset($paging['dir']))     ? $paging['dir']    : 'ASC';
        $groups = Tinebase_Group::getInstance()->getGroups($filter[0]['value'], $sort, $dir, isset($paging['start']) ?
            $paging['start'] : 0, isset($paging['limit']) ? $paging['limit'] : null);

        $result['results'] = $groups->toArray();
        $result['totalcount'] = Admin_Controller_Group::getInstance()->searchCount($filter[0]['value']);
        
        return $result;
    }
    
    /**
     * save user
     *
     * @param  array $recordData data of Tinebase_Model_FullUser
     * @return array  
     */
    public function saveUser($recordData)
    {
        $password = (isset($recordData['accountPassword'])) ? $recordData['accountPassword'] : '';
        
        $account = new Tinebase_Model_FullUser();
        
        // always re-evaluate fullname
        unset($recordData['accountFullName']);
        
        try {
            $account->setFromJsonInUsersTimezone($recordData);
            if (isset($recordData['sambaSAM'])) {
                $account->sambaSAM = new Tinebase_Model_SAMUser($recordData['sambaSAM']);
            }
            
            if (isset($recordData['emailUser'])) {
                $account->emailUser = new Tinebase_Model_EmailUser($recordData['emailUser']);
                $account->imapUser  = new Tinebase_Model_EmailUser($recordData['emailUser']);
                $account->smtpUser  = new Tinebase_Model_EmailUser($recordData['emailUser']);
            }
        } catch (Tinebase_Exception_Record_Validation $e) {
            // invalid data in some fields sent from client
            $result = array(
                'errors'            => $account->getValidationErrors(),
                'errorMessage'      => 'invalid data for some fields',
                'status'            => 'failure'
            );
            
            return $result;
        }
        
        // this needs long 3execution time because cache invalidation may take long
        // @todo remove this when "0007266: make groups / group memberships cache cleaning more efficient" is resolved 
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(300); // 5 minutes
        
        if ($account->getId() == NULL) {
            $account = Admin_Controller_User::getInstance()->create($account, $password, $password);
        } else {
            $account = Admin_Controller_User::getInstance()->update($account, $password, $password);
        }
        
        $result = $this->_recordToJson($account);
        
        // add primary group to account for the group selection combo box
        $group = Tinebase_Group::getInstance()->getGroupById($account->accountPrimaryGroup);
        
        // add user groups
        $userGroups = Tinebase_Group::getInstance()->getMultiple(Tinebase_Group::getInstance()->getGroupMemberships($account->accountId))->toArray();
        
        // add user roles
        $userRoles = Tinebase_Acl_Roles::getInstance()->getMultiple(Tinebase_Acl_Roles::getInstance()->getRoleMemberships($account->accountId))->toArray();
        
        // encode the account array
        $result['accountPrimaryGroup'] = $group->toArray();
        
        // encode the groups array
        $result['groups'] = array(
            'results'         => $userGroups,
            'totalcount'     => count($userGroups)
        );
        
        // encode the roles array
        $result['accountRoles'] = array(
            'results'         => $userRoles,
            'totalcount'     => count($userRoles)
        );
        
        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);
        
        return $result;
    }
    
    /**
     * delete users
     *
     * @param   array $ids array of account ids
     * @return  array with success flag
     */
    public function deleteUsers($ids)
    {
        Admin_Controller_User::getInstance()->delete($ids);
        
        $result = array(
            'success' => TRUE
        );
        return $result;
    }

    /**
     * set account state
     *
     * @param   array  $accountIds  array of account ids
     * @param   string $state      state to set
     * @return  array with success flag
     */
    public function setAccountState($accountIds, $status)
    {
        $controller = Admin_Controller_User::getInstance();
        foreach ($accountIds as $accountId) {
            $controller->setAccountStatus($accountId, $status);
        }

        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }
    
    /**
     * reset password for given account
     *
     * @param array|string $account Tinebase_Model_FullUser data or account id
     * @param string $password the new password
     * @param bool $mustChange
     * @return array
     */
    public function resetPassword($account, $password, $mustChange)
    {
        if (is_array($account)) {
            if (isset($account['accountPrimaryGroup']) && is_array($account['accountPrimaryGroup']) && isset($account['accountPrimaryGroup']['id'])) {
                $account['accountPrimaryGroup'] = $account['accountPrimaryGroup']['id'];
            }
            $account = new Tinebase_Model_FullUser($account);
        } else {
            $account = Tinebase_User::factory(Tinebase_User::getConfiguredBackend())->getFullUserById($account);
        }
        
        $controller = Admin_Controller_User::getInstance();
        $controller->setAccountPassword($account, $password, $password, (bool)$mustChange);
        
        $result = array(
            'success' => TRUE
        );
        return $result;
    }

    /**
     * reset password for given account
     *
     * @param array|string $account Tinebase_Model_FullUser data or account id
     * @param string $password the new password
     * @return array
     */
    public function resetPin($account, $password)
    {
        if (is_array($account)) {
            $account = new Tinebase_Model_FullUser($account);
        } else {
            $account = Tinebase_User::factory(Tinebase_User::getConfiguredBackend())->getFullUserById($account);
        }

        $controller = Admin_Controller_User::getInstance();
        $controller->setAccountPin($account, $password);

        $result = array(
            'success' => TRUE
        );
        return $result;
    }

    /**
     * adds the name of the account to each item in the name property
     * 
     * @param  array  &$_items array of arrays which contain a type and id property
     * @param  bool   $_hasAccountPrefix
     * @param  bool   $_removePrefix
     * @return array  items with appended name 
     * @throws UnexpectedValueException
     * 
     * @todo    remove all this prefix stuff? why did we add this?
     * @todo    use a resolveMultiple function here
     */
    public static function resolveAccountName(array $_items, $_hasAccountPrefix = FALSE, $_removePrefix = FALSE)
    {
        $prefix = $_hasAccountPrefix ? 'account_' : '';
        
        $return = array();
        foreach ($_items as $num => $item) {
            switch ($item[$prefix . 'type']) {
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                    try {
                        $item[$prefix . 'name'] = Tinebase_User::getInstance()->getUserById($item[$prefix . 'id'])->accountDisplayName;
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        $item[$prefix . 'name'] = 'Unknown user';
                    }
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    try {
                        $item[$prefix . 'name'] = Tinebase_Group::getInstance()->getGroupById($item[$prefix . 'id'])->name;
                    } catch (Tinebase_Exception_Record_NotDefined $ternd) {
                        $item[$prefix . 'name'] = 'Unknown group';
                    }
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                    try {
                        $item[$prefix . 'name'] = Tinebase_Acl_Roles::getInstance()->getRoleById($item[$prefix . 'id'])->name;
                    } catch(Tinebase_Exception_NotFound $tenf) {
                        $item[$prefix . 'name'] = 'Unknown role';
                    }
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE:
                    $item[$prefix . 'name'] = 'Anyone';
                    break;
                default:
                    throw new UnexpectedValueException('Unsupported accountType: ' . $item[$prefix . 'type']);
                    break;
            }
            if ($_removePrefix) {
                $return[$num] = array(
                    'id'    => $item[$prefix . 'id'],
                    'name'  => $item[$prefix . 'name'], 
                    'type'  => $item[$prefix . 'type'],
                );
            } else {
                $return[$num] = $item;
            }
        }
        return $return;
    }
    
    /**
     * search for shared addressbook containers
     * 
     * @param array $filter unused atm
     * @param array $paging unused atm
     * @return array
     * 
     * @todo add test
     */
    public function searchSharedAddressbooks($filter, $paging)
    {
        $sharedAddressbooks = Admin_Controller_User::getInstance()->searchSharedAddressbooks();
        $result = $this->_multipleRecordsToJson($sharedAddressbooks);
        
        return array(
            'results'       => $result,
            'totalcount'    => count($result),
        );
    }
    
    /********************************* Groups *********************************/
    
    /**
     * gets a single group
     *
     * @param string $id
     * @return array
     *
     * @todo use abstract _get
     */
    public function getGroup($id)
    {
        $groupArray = array();
        
        if ($id) {
            $group = Admin_Controller_Group::getInstance()->get($id);
            
            $groupArray = $group->toArray();
            
            if (!empty($group->container_id)) {
                $groupArray['container_id'] = Tinebase_Container::getInstance()->getContainerById($group->container_id)->toArray();
            }
            
        }
        
        $groupArray['members'] = $this->getGroupMembers($id);
        $groupArray['xprops'] = Tinebase_Helper::jsonDecode($groupArray['xprops']);
        
        return $groupArray;
    }
    
    /**
     * get list of groups
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     * 
     * @todo switch to new api with only filter and paging params
     */
    public function getGroups($filter, $sort, $dir, $start, $limit)
    {
        $groups = Admin_Controller_Group::getInstance()->search($filter, $sort, $dir, $start, $limit);
        
        $result = array(
            'results'     => $this->_multipleRecordsToJson($groups),
            'totalcount'  => Admin_Controller_Group::getInstance()->searchCount($filter)
        );
        
        return $result;
    }

    /**
     * get list of group members
     *
     * @param int $groupId
     * @return array with results / total count
     * 
     * @todo use Account Model?
     */
    public function getGroupMembers($groupId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if ($groupId) {
            $accountIds = Admin_Controller_Group::getInstance()->getGroupMembers($groupId);
            $users = Tinebase_User::getInstance()->getMultiple($accountIds);
            $result['results'] = array();
            foreach ($users as $user) {
                $result['results'][] = array(
                    'id'        => $user->getId(),
                    'type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                    'name'      => $user->accountDisplayName,
                );
            }
            
            $result['totalcount'] = count($result['results']);
        }
        
        return $result;
    }
        
    /**
     * save group data from edit form
     *
     * @param   array $recordData        group data
     * @return  array
     * @todo use _save
     */
    public function saveGroup($recordData)
    {
        // unset if empty
        if (empty($recordData['id'])) {
            unset($recordData['id']);
        }

        $group = new Tinebase_Model_Group($recordData);

        // this needs long execution time because cache invalidation may take long
        $oldMaxExcecutionTime = Tinebase_Core::setExecutionLifeTime(60); // 1 minute

        if ( empty($group->id) ) {
            $group = Admin_Controller_Group::getInstance()->create($group);
        } else {
            $group = Admin_Controller_Group::getInstance()->update($group);
        }

        Tinebase_Core::setExecutionLifeTime($oldMaxExcecutionTime);

        return $this->getGroup($group->getId());
    }
   
    /**
     * delete multiple groups
     *
     * @param array $groupIds list of contactId's to delete
     * @return array with success flag
     */
    public function deleteGroups($groupIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        Admin_Controller_Group::getInstance()->delete($groupIds);

        return $result;
    }
    
    /********************************** Samba Machines **********************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchSambaMachines($filter, $paging)
    {
        try {
            $result = $this->_search($filter, $paging, Admin_Controller_SambaMachine::getInstance(), 'Admin_Model_SambaMachineFilter');
        } catch (Admin_Exception $ae) {
            // no samba settings defined
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $ae->getMessage());
            $result = array(
                'results'       => array(),
                'totalcount'    => 0
            );
        }
        
        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getSambaMachine($id)
    {
        return $this->_get($id, Admin_Controller_SambaMachine::getInstance());
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveSambaMachine($recordData)
    {
        try {
            $result = $this->_save($recordData, Admin_Controller_SambaMachine::getInstance(), 'SambaMachine', 'accountId');
        } catch (Admin_Exception $ae) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Error while saving samba machine: ' . $ae->getMessage());
            $result = array('success' => FALSE);
        }
        
        return $result;
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids 
     * @return string
     */
    public function deleteSambaMachines($ids)
    {
        return $this->_delete($ids, Admin_Controller_SambaMachine::getInstance());
    }
    

    /********************************** Tags **********************************/
    
    /**
     * gets a single tag
     *
     * @param int $tagId
     * @return array
     */
    public function getTag($tagId)
    {
        $tag = array();
        
        if ($tagId) {
            $tag = Admin_Controller_Tags::getInstance()->get($tagId)->toArray();
            //$tag->rights = $tag->rights->toArray();
            $tag['rights'] = self::resolveAccountName($tag['rights'] , true);
        }
        $tag['appList'] = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED)->toArray();
        
        return $tag;
    }
    
    /**
     * get list of tags
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     */
    public function getTags($query, $sort, $dir, $start, $limit)
    {
        $filter = new Tinebase_Model_TagFilter(array(
            'name'        => '%' . $query . '%',
            'description' => '%' . $query . '%',
            'type'        => Tinebase_Model_Tag::TYPE_SHARED
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $tags = Admin_Controller_Tags::getInstance()->search_($filter, $paging);
        
        $result = array(
            'results'     => $this->_multipleRecordsToJson($tags),
            'totalcount'  => Admin_Controller_Tags::getInstance()->searchCount_($filter)
        );
        
        return $result;
    }
        
    /**
     * save tag data from edit form
     *
     * @param   array $tagData
     * 
     * @return  array with success, message, tag data and tag members
     */
    public function saveTag($tagData)
    {
        // unset if empty
        if (empty($tagData['id'])) {
            unset($tagData['id']);
        }
        
        $tag = new Tinebase_Model_FullTag($tagData);
        $tag->rights = new Tinebase_Record_RecordSet('Tinebase_Model_TagRight', $tagData['rights']);
        
        if ( empty($tag->id) ) {
            $tag = Admin_Controller_Tags::getInstance()->create($tag);
        } else {
            $tag = Admin_Controller_Tags::getInstance()->update($tag);
        }
        
        return $this->getTag($tag->getId());
        
    }    
        
    /**
     * delete multiple tags
     *
     * @param array $tagIds list of contactId's to delete
     * @return array with success flag
     */
    public function deleteTags($tagIds)
    {
        return $this->_delete($tagIds, Admin_Controller_Tags::getInstance());
    }
    
    /********************************* Roles **********************************/
    
    /**
     * get a single role with all related data
     *
     * @param int $roleId
     * @return array
     */
    public function getRole($roleId)
    {
        $role = array();
        if ($roleId) {
            $role = Admin_Controller_Role::getInstance()->get($roleId)->toArray();
        }

        $role['roleMembers'] = $this->getRoleMembers($roleId);
        $role['roleRights'] = $this->getRoleRights($roleId);
        $role['allRights'] = $this->getAllRoleRights();
        return $role;
    }
    
    /**
     * get list of roles
     *
     * @param string $query
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
     * @return array with results array & totalcount (int)
     */
    public function getRoles($query, $sort, $dir, $start, $limit)
    {
        $filter = new Tinebase_Model_RoleFilter(array(
            array('field' => 'query', 'operator' => 'contains', 'value' => $query),
        ));
        $paging = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $roles = Admin_Controller_Role::getInstance()->search($filter, $paging);
        
        $result = array(
            'results'     => $this->_multipleRecordsToJson($roles),
            'totalcount'  => Admin_Controller_Role::getInstance()->searchCount($filter)
        );
        
        return $result;
    }

    /**
     * save role data from edit form
     *
     * @param   array $roleData        role data
     * @param   array $roleMembers     role members
     * @param   array $roleRights      role rights
     * @return  array
     */
    public function saveRole($roleData, $roleMembers, $roleRights)
    {
        // unset if empty
        if (empty($roleData['id'])) {
            unset($roleData['id']);
        }
        
        $role = new Tinebase_Model_Role($roleData);
        
        if (empty($role->id) ) {
            $role = Admin_Controller_Role::getInstance()->create($role, $roleMembers, $roleRights);
        } else {
            $role = Admin_Controller_Role::getInstance()->update($role, $roleMembers, $roleRights);
        }
        
        return $this->getRole($role->getId());
    }    

    /**
     * delete multiple roles
     *
     * @param array $roleIds list of roleId's to delete
     * @return array with success flag
     */
    public function deleteRoles($roleIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        Admin_Controller_Role::getInstance()->delete($roleIds);

        return $result;
    }

    /**
     * get list of role members
     *
     * @param int $roleId
     * @return array with results / totalcount
     * 
     * @todo    move group/user resolution to new accounts class
     */
    public function getRoleMembers($roleId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if (!empty($roleId)) {
            $members = Admin_Controller_Role::getInstance()->getRoleMembers($roleId);
    
            $result['results'] = self::resolveAccountName($members, TRUE, TRUE);
            $result['totalcount'] = count($result['results']);
        }
        return $result;
    }

    /**
     * get list of role rights
     *
     * @param int $roleId
     * @return array with results / totalcount
     */
    public function getRoleRights($roleId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if (!empty($roleId)) {
            $rights = Admin_Controller_Role::getInstance()->getRoleRights($roleId);
        
            $result['results'] = $rights;
            $result['totalcount'] = count($rights);
        }    
        return $result;
    }
    
    /**
     * get list of all role rights for all applications
     *
     * @return array with all rights for applications
     * 
     * @todo    get only rights of active applications?
     */
    public function getAllRoleRights()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Get all rights of all apps.');
        
        $result = array();
        
        $applications = Admin_Controller_Application::getInstance()->search(NULL, 'name', 'ASC', NULL, NULL);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($applications->toArray(), TRUE));
        
        foreach ($applications as $application) {
            $appId = $application->getId();
            $rightsForApplication = array(
                "application_id"    => $appId,
                "text"              => $application->name,
                "children"          => array()
            );
            
            $allAplicationRights = Tinebase_Application::getInstance()->getAllRightDescriptions($appId);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' ' . print_r($allAplicationRights, TRUE));
            
            foreach ($allAplicationRights as $right => $description) {
                $rightsForApplication["children"][] = array(
                    "text"      => $description['text'],
                    "qtip"      => $description['description'],
                    "right"     => $right,
                );
            }

            $result[] = $rightsForApplication;
        }
        
        return $result;
    }
    
    /****************************** Container ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchContainers($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_Container::getInstance(), 'Tinebase_Model_ContainerFilter');
        
        // remove acl (app) filter
        foreach ($result['filter'] as $id => $filter) {
            if ($filter['field'] === 'application_id' && $filter['operator'] === 'in') {
                unset($result['filter'][$id]);
            }
        }
        
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getContainer($id)
    {
        return $this->_get($id, Admin_Controller_Container::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveContainer($recordData)
    {
        $application = Tinebase_Application::getInstance()->getApplicationById($recordData['application_id'])->name;
        if (!isset($recordData['model']) || empty($recordData['model']) ||
                !($applicationModelParts = \explode('.', $recordData['model'])) ||
                    (1 === \count($applicationModelParts) && false === \strpos($recordData['model'], '_Model_'))) {
            throw new \InvalidArgumentException('Invalid model specified.');
        }
        // Handling if a model is either in php format like Application_Model_Foobar or Application.Model.Foobar
        $recordData['model'] = \strpos($recordData['model'], '_Model_') !== false ? $recordData['model'] :
            $application . '_Model_' . \end($applicationModelParts);
        \reset($applicationModelParts);

        $additionalArguments = ((isset($recordData['note']) || array_key_exists('note', $recordData))) ? array(array('note' => $recordData['note'])) : array();
        return $this->_save($recordData, Admin_Controller_Container::getInstance(), 'Tinebase_Model_Container', 'id', $additionalArguments);
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return array
     */
    public function deleteContainers($ids)
    {
        return $this->_delete($ids, Admin_Controller_Container::getInstance());
    }    
    
    /****************************** Customfield ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter 
     * @param array $paging 
     * @return array
     */
    public function searchCustomfields($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_Customfield::getInstance(), 'Tinebase_Model_CustomField_ConfigFilter');
        
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getCustomfield($id)
    {
        return $this->_get($id, Admin_Controller_Customfield::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveCustomfield($recordData)
    {
        return $this->_save($recordData, Admin_Controller_Customfield::getInstance(), 'Tinebase_Model_CustomField_Config', 'id');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @param  array $context
     * @return array
     */
    public function deleteCustomfields($ids, array $context = array())
    {
        $controller = Admin_Controller_Customfield::getInstance();
        $controller->setRequestContext($context);

        return $this->_delete($ids, $controller);
    }

    /****************************** Config *********************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchConfigs($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_Config::getInstance(), 'Tinebase_Model_ConfigFilter', false, self::TOTALCOUNT_COUNTRESULT);

        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getConfig($id)
    {
        return $this->_get($id, Admin_Controller_Config::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveConfig($recordData)
    {
        return $this->_save($recordData, Admin_Controller_Config::getInstance(), 'Tinebase_Model_Config', 'id');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return array
     */
    public function deleteConfigs($ids)
    {
        return $this->_delete($ids, Admin_Controller_Config::getInstance());
    }

    /****************************** ImportExportDefinition ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchImportExportDefinitions($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_ImportExportDefinition::getInstance(), 'Tinebase_Model_ImportExportDefinitionFilter');

        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getImportExportDefinition($id)
    {
        return $this->_get($id, Admin_Controller_ImportExportDefinition::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveImportExportDefinition($recordData)
    {
        return $this->_save($recordData, Admin_Controller_ImportExportDefinition::getInstance(), 'Tinebase_Model_ImportExportDefinition');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteImportExportDefinitions($ids)
    {
        return $this->_delete($ids, Admin_Controller_ImportExportDefinition::getInstance());
    }


    /****************************** EmailAccount ******************************/

    /**
     * Search for records matching given arguments
     *
     * @param array $filter
     * @param array $paging
     * @return array
     */
    public function searchEmailAccounts($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Admin_Controller_EmailAccount::getInstance(), 'Felamimail_Model_AccountFilter');

        return $result;
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getEmailAccount($id)
    {
        return $this->_get($id, Admin_Controller_EmailAccount::getInstance());
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveEmailAccount($recordData)
    {
        return $this->_save($recordData, Admin_Controller_EmailAccount::getInstance(), 'Felamimail_Model_Account');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteEmailAccounts($ids)
    {
        return $this->_delete($ids, Admin_Controller_EmailAccount::getInstance());
    }


    /****************************** other *******************************/
    
    /**
     * returns phpinfo() output
     * 
     * @return array
     */
    public function getServerInfo()
    {
        if (! Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::RUN)) {
            return [];
        }
        
        ob_start();
        phpinfo();
        $out = ob_get_clean();
        
        // only return body
        $dom = new DOMDocument('1.0', 'UTF-8');
        try {
            $dom->loadHTML($out);
            $body = $dom->getElementsByTagName('body');
            $phpinfo = $dom->saveXml($body->item(0));
        } catch (Exception $e) {
            // no html (CLI)
            $phpinfo = $out;
        }
        
        return array(
            'html' => $phpinfo
        );
    }

    public function searchQuotaNodes($filter = null)
    {
        if (! Tinebase_Core::getUser()->hasRight('Admin', Admin_Acl_Rights::VIEW_QUOTA_USAGE)) {
            return FALSE;
        }

        if ($isFelamimailInstalled = Tinebase_Application::getInstance()->isInstalled('Felamimail')) {
            $emailPath = Tinebase_FileSystem::getInstance()->getApplicationBasePath('Felamimail');
        } else {
            $emailPath = '';
        }
        $virtualPath = $emailPath . '/Emails';
        $path = '';
        if (null !== $filter) {
            array_walk($filter, function ($val) use (&$path) {
                if ('path' === $val['field']) {
                    $path = $val['value'];
                }
            });
        }

        if ($isFelamimailInstalled && strpos($path, $virtualPath) === 0) {
            $records = $this->_getVirtualEmailQuotaNodes(str_replace($virtualPath, '', $path));
            $filterArray = $filter;
            $result = $this->_multipleRecordsToJson($records);
        } else {
            $filter = $this->_decodeFilter($filter, 'Tinebase_Model_Tree_Node_Filter');
            // ATTENTION sadly the pathfilter to Array does path magic, returns the flatpath and not the statpath
            // etc. this is Filemanager path magic. We don't want that here!
            $filterArray = $filter->toArray();
            array_walk($filterArray, function (&$val) use ($path) {
                if ('path' === $val['field']) {
                    $val['value'] = $path;
                }
            });
            $filter = new Tinebase_Model_Tree_Node_Filter($filterArray, '', array('ignoreAcl' => true));

            $pathFilters = $filter->getFilter('path', true);
            if (count($pathFilters) !== 1) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . 'Exactly one path filter required.');
                }
                $pathFilter = $filter->createFilter(array(
                        'field' => 'path',
                        'operator' => 'equals',
                        'value' => '/',
                    )
                );
                $filter->removeFilter('path');
                $filter->addFilter($pathFilter);
                $path = '/';
            }

            $filter->removeFilter('type');
            $filter->addFilter($filter->createFilter(array(
                'field' => 'type',
                'operator' => 'equals',
                'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
            )));

            $records = Tinebase_FileSystem::getInstance()->search($filter);
            if ($isFelamimailInstalled && $path === $emailPath) {
                $imapBackend = null;
                try {
                    $imapBackend = Tinebase_EmailUser::getInstance();
                } catch (Tinebase_Exception_NotFound $tenf) {
                }
                if ($imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
                    /** @var Tinebase_Model_Tree_Node $emailNode */
                    $emailNode = clone $records->getFirstRecord();
                    $emailNode->setId(trim($emailPath, '/'));
                    $emailNode->name = 'Emails';
                    $emailNode->path = $virtualPath;
                    $imapUsageQuota = $imapBackend->getTotalUsageQuota();
                    $emailNode->quota = $imapUsageQuota['mailQuota'];
                    $emailNode->size = $imapUsageQuota['mailSize'];
                    $emailNode->revision_size = $emailNode->size;
                    $records->addRecord($emailNode);
                }
            } elseif ($isFelamimailInstalled && '/' === $path) {
                $imapBackend = null;
                try {
                    $imapBackend = Tinebase_EmailUser::getInstance();
                } catch (Tinebase_Exception_NotFound $tenf) {
                }
                if ($imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
                    $imapUsageQuota = $imapBackend->getTotalUsageQuota();
                    $node = $records->filter('name',
                        Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId())->getFirstRecord();
                    $node->quota += $imapUsageQuota['mailQuota'];
                    $node->size += $imapUsageQuota['mailSize'];
                }
            }

            $result = $this->_multipleRecordsToJson($records, $filter);

            $filterArray = $filter->toArray();
            array_walk($filterArray, function (&$val) use ($path) {
                if ('path' === $val['field']) {
                    $val['value'] = $path;
                }
            });
        }

        return array(
            'results'       => array_values($result),
            'totalcount'    => count($result),
            'filter'        => $filterArray
        );
    }

    /**
     * @param string $path
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _getVirtualEmailQuotaNodes($path)
    {
        /** @var Tinebase_EmailUser_Imap_Dovecot $imapBackend */
        $imapBackend = Tinebase_EmailUser::getInstance();
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array());

        if (!$imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
            return $result;
        }

        $path = trim($path, '/');
        if (empty($path)) {
            $parent_id = Tinebase_Application::getInstance()->getApplicationByName('Felamimail')->getId();

            $domains = array_unique(array_merge(
                Tinebase_EmailUser::getAllowedDomains(Tinebase_Config::getInstance()->get(Tinebase_Config::IMAP)),
                $imapBackend->getAllDomains()
            ));

            foreach ($domains as $domain) {
                $usageQuota = $imapBackend->getTotalUsageQuota($domain);

                $node = new Tinebase_Model_Tree_Node(array(), true);
                $node->parent_id = $parent_id;
                $node->name = $domain;
                $node->quota = $usageQuota['mailQuota'];
                $node->size = $usageQuota['mailSize'];
                $node->revision_size = $usageQuota['mailSize'];
                $node->setId(md5($domain));
                $node->type = Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
                $result->addRecord($node);
            }
        } elseif (count($pathParts = explode('/', $path)) === 1) {
            $parent_id = md5($pathParts[0]);
            $accountIds = [];
            $nodeIds = [];

            /** @var Tinebase_Model_EmailUser $emailUser */
            foreach ($imapBackend->getAllEmailUsers($pathParts[0]) as $emailUser) {
                $node = new Tinebase_Model_Tree_Node(array(), true);
                $node->parent_id = $parent_id;
                $node->name = $emailUser->emailUsername;
                $node->quota = $emailUser->emailMailQuota;
                $node->size = $emailUser->emailMailSize;
                $node->revision_size = $emailUser->emailMailSize;
                $node->setId($emailUser->emailUserId);
                $node->type = Tinebase_Model_Tree_FileObject::TYPE_FOLDER;
                $result->addRecord($node);
                list($accountId) = explode('@', $emailUser->emailUsername);
                $nodeIds[$accountId] = $emailUser->emailUserId;
                $accountIds[] = $accountId;
            }

            /** @var Tinebase_Model_User $user */
            foreach (Tinebase_User::getInstance()->getMultiple($accountIds) as $user) {
                if (isset($nodeIds[$user->accountId])) {
                    $result->getById($nodeIds[$user->accountId])->name = $user->accountDisplayName;
                }
            }
        }

        return $result;
    }

    /****************************** common ******************************/
    
    /**
     * returns record prepared for json transport
     *
     * @param Tinebase_Record_Interface $_record
     * @return array record data
     */
    protected function _recordToJson($_record)
    {
        $result = parent::_recordToJson($_record);

        if ($_record instanceof Tinebase_Model_Container) {
            $result['account_grants'] = Tinebase_Frontend_Json_Container::resolveAccounts($_record['account_grants']->toArray());
        }

        return $result;
    }
    
    /**
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Interface
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        switch ($_records->getRecordClassName()) {
            case 'Tinebase_Model_AccessLog':
                // TODO use _resolveUserFields and remove this
                foreach ($_records as $record) {
                    if (! empty($record->account_id)) {
                        try {
                            $record->account_id = Admin_Controller_User::getInstance()->get($record->account_id)->toArray();
                        } catch (Tinebase_Exception_NotFound $e) {
                            $record->account_id = Tinebase_User::getInstance()->getNonExistentUser('Tinebase_Model_FullUser')->toArray();
                        }
                    }
                }
                break;
            case 'Tinebase_Model_Container':
            case 'Tinebase_Model_ImportExportDefinition':
            case 'Tinebase_Model_CustomField_Config':
                $applications = Tinebase_Application::getInstance()->getApplications();
                foreach ($_records as $record) {
                    $idx = $applications->getIndexById($record->application_id);
                    if ($idx !== FALSE) {
                        $record->application_id = $applications[$idx];
                    }
                }
                break;
        }
        
        return parent::_multipleRecordsToJson($_records, $_filter, $_pagination);
    }

    /***************************** sieve funcs *******************************/

    /**
     * get sieve vacation for account
     *
     * @param  string $id account id
     * @return array
     */
    public function getSieveVacation($id)
    {
        $raii = Admin_Controller_EmailAccount::getInstance()->prepareAccountForSieveAdminAccess($id);

        $result = (new Felamimail_Frontend_Json())->getVacation($id);

        Admin_Controller_EmailAccount::getInstance()->removeSieveAdminAccess();

        //for unused variable check
        unset($raii);
        return $result;
    }

    /**
     * set sieve vacation for account
     *
     * @param  array $recordData
     * @return array
     */
    public function saveSieveVacation($recordData)
    {
        $raii = Admin_Controller_EmailAccount::getInstance()->prepareAccountForSieveAdminAccess(
            $recordData['id'], Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);

        $result = (new Felamimail_Frontend_Json())->saveVacation($recordData);

        Admin_Controller_EmailAccount::getInstance()->removeSieveAdminAccess();

        //for unused variable check
        unset($raii);
        return $result;
    }

    /**
     * get sieve rules for account
     *
     * @param  string $accountId
     * @return array
     */
    public function getSieveRules($accountId)
    {
        $raii = Admin_Controller_EmailAccount::getInstance()->prepareAccountForSieveAdminAccess($accountId);

        $result = (new Felamimail_Frontend_Json())->getRules($accountId);

        Admin_Controller_EmailAccount::getInstance()->removeSieveAdminAccess();

        //for unused variable check
        unset($raii);
        return $result;
    }

    /**
     * set sieve rules for account
     *
     * @param   array $accountId
     * @param   array $rulesData
     * @return  array
     */
    public function saveRules($accountId, $rulesData)
    {
        $raii = Admin_Controller_EmailAccount::getInstance()->prepareAccountForSieveAdminAccess(
            $accountId, Admin_Acl_Rights::MANAGE_EMAILACCOUNTS);

        $result = (new Felamimail_Frontend_Json())->saveRules($accountId, $rulesData);

        Admin_Controller_EmailAccount::getInstance()->removeSieveAdminAccess();

        //for unused variable check
        unset($raii);
        return $result;
    }
}
