<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        use functions from Tinebase_Frontend_Json_Abstract
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the admin application
 *
 * @package     Admin
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
     * @var bool
     */
    protected $_manageImapEmailUser = FALSE;
    
    /**
     * @var bool
     */
    protected $_manageSmtpEmailUser = FALSE;
    
    /**
     * constructs Admin_Frontend_Json
     */
    public function __construct()
    {
        // manage samba sam?
		if(isset(Tinebase_Core::getConfig()->samba)) {
			$this->_manageSAM = Tinebase_Core::getConfig()->samba->get('manageSAM', false); 
		}
		
        // manage email user settings
        if (Tinebase_EmailUser::manages(Tinebase_Model_Config::IMAP)) {
            $this->_manageImapEmailUser = TRUE; 
        }
        if (Tinebase_EmailUser::manages(Tinebase_Model_Config::SMTP)) {
            $this->_manageSmtpEmailUser = TRUE; 
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
        $registryData = array(
            'manageSAM' => $this->_manageSAM,
            'manageImapEmailUser' => $this->_manageImapEmailUser,
            'manageSmtpEmailUser' => $this->_manageSmtpEmailUser,
        );        
        return $registryData;    
    }
    
    /******************************* Access Log *******************************/
    
    /**
     * delete access log entries
     *
     * @param array $logIds list of logIds to delete
     * @return array with success flag
     */
    public function deleteAccessLogEntries($logIds)
    {
        return $this->_delete($logIds, Admin_Controller_AccessLog::getInstance());
    }
    
    
    /**
     * get list of access log entries
     *
     * @param string $from (date format example: 2008-03-31T00:00:00)
     * @param string $to (date format example: 2008-03-31T00:00:00)
     * @param string $filter
     * @param array $paging paging data (Tinebase_Model_Pagination)
     * @return array with results array & totalcount (int)
     * 
     * @todo switch to new api with only filter and paging params
     */
    public function getAccessLogEntries($from, $to, $filter, $paging)
    {
        $fromDateObject = new Zend_Date($from, Tinebase_Record_Abstract::ISO8601LONG);
        $toDateObject = new Zend_Date($to, Tinebase_Record_Abstract::ISO8601LONG);
        $pagination = new Tinebase_Model_Pagination($paging);
        
        $accessLogSet = Admin_Controller_AccessLog::getInstance()->search_($filter, $pagination, $fromDateObject, $toDateObject);
        
        $result = $this->_multipleRecordsToJson($accessLogSet);

        foreach ($result as $key => &$value) {
            if (! empty($value['account_id'])) {
            	try {
            		$accountObject = Admin_Controller_User::getInstance()->get($value['account_id'])->toArray();
            	} catch (Tinebase_Exception_NotFound $e) {
            		$accountObject = Tinebase_User::getInstance()->getNonExistentUser('Tinebase_Model_FullUser')->toArray();
            	}
                $value['accountObject'] = $accountObject;
            }
        }

        return array(
            'results'       => $result,
            'totalcount'    => Admin_Controller_AccessLog::getInstance()->searchCount_($fromDateObject, $toDateObject, $filter),
        );
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
     * @param int $id
     */
    public function getUser($id)
    {
        if (!empty($id)) {
            $user = Admin_Controller_User::getInstance()->get($id);
            $user->setTimezone(Tinebase_Core::get('userTimeZone'));
            $userArray = $user->toArray();
            
            // add primary group to account for the group selection combo box
            $group = Tinebase_Group::getInstance()->getGroupById($user->accountPrimaryGroup);
        } else {
            $userArray = array('accountStatus' => 'enabled', 'visibility' => 'displayed');
            
            // get default primary group for the group selection combo box
            $group = Tinebase_Group::getInstance()->getDefaultGroup();
        }
        
        // encode the account array
        $userArray['accountPrimaryGroup'] = $group->toArray();

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
     */
    public function getUsers($filter, $sort, $dir, $start, $limit)
    {
        $accounts = Admin_Controller_User::getInstance()->searchFullUsers($filter, $sort, $dir, $start, $limit);

        $result = array(
            'results'     => $this->_multipleRecordsToJson($accounts),
            'totalcount'  => Admin_Controller_User::getInstance()->searchCount($filter)
        );
        
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
        $password = $recordData['accountPassword'];
        
        $account = new Tinebase_Model_FullUser();
        
        try {
            $account->setFromArray($recordData);
            if (isset($recordData['sambaSAM'])) {
                $account->sambaSAM = new Tinebase_Model_SAMUser($recordData['sambaSAM']);
            }
            
            if (isset($recordData['emailUser'])) {
                $account->emailUser = new Tinebase_Model_EmailUser($recordData['emailUser']);
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
        
        if ($account->getId() == NULL) {
            if(!Tinebase_User_Registration::getInstance()->checkUniqueUsername($account->accountLoginName)) {
                $result = array(
                    'errors'            => 'invalid username',
                    'errorMessage'      => 'Username already used.',
                    'status'            => 'failure'
                );
                return $result;
            }
            
            $account = Admin_Controller_User::getInstance()->create($account, $password, $password);
        } else {
            $account = Admin_Controller_User::getInstance()->update($account, $password, $password);
        }
        
        $account->accountPrimaryGroup = Tinebase_Group::getInstance()->getGroupById($account->accountPrimaryGroup);
        $result = $account->toArray();
        
        return $result;
    }
    
    /**
     * delete users
     *
     * @param   array $accountIds array of account ids
     * @return  array with success flag
     */
    public function deleteUsers($accountIds)
    {
        Admin_Controller_User::getInstance()->delete($accountIds);
        
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
            $account = new Tinebase_Model_FullUser($account);
        } else {
            $account = Tinebase_User::factory(Tinebase_User::getConfiguredBackend())->getFullUserById($account);
        }
        
        $controller = Admin_Controller_User::getInstance();
        $controller->setAccountPassword($account, $password, $password, $mustChange);
        
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
                    $item[$prefix . 'name'] = Tinebase_User::getInstance()->getUserById($item[$prefix . 'id'])->accountDisplayName;
                    break;
                case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                    $item[$prefix . 'name'] = Tinebase_Group::getInstance()->getGroupById($item[$prefix . 'id'])->name;
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
    
    /********************************* Groups *********************************/
    
    /**
     * gets a single group
     *
     * @param int $groupId
     * @return array
     */
    public function getGroup($groupId)
    {
        $group = array();
        
        if ($groupId) {
            $group = Admin_Controller_Group::getInstance()->get($groupId)->toArray();
        }
        
        $group['groupMembers'] = $this->getGroupMembers($groupId);
        return $group;
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
     * get list of groupmembers
     *
     * @param int $groupId
     * @return array with results / totalcount
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
    
            $result['results'] = array();
            foreach ($accountIds as $accountId) {
                $account = Tinebase_User::getInstance()->getUserById($accountId);
                $result['results'][] = array(
                    'id'        => $accountId,
                    'type'      => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                    'name'      => $account->accountDisplayName,
                ); 
            }
                    
            $result['totalcount'] = count($result['results']);
        }
        
        return $result;
    }
        
    /**
     * save group data from edit form
     *
     * @param   array $groupData        group data
     * @param   array $groupMembers     group members
     * 
     * @return  array
     */
    public function saveGroup($groupData, $groupMembers)
    {
        // unset if empty
        if (empty($groupData['id'])) {
            unset($groupData['id']);
        }
        
        $group = new Tinebase_Model_Group($groupData);
        $group->members = $groupMembers;
        
        if ( empty($group->id) ) {
            $group = Admin_Controller_Group::getInstance()->create($group);
        } else {
            $group = Admin_Controller_Group::getInstance()->update($group);
        }

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
        $tag['appList'] = Tinebase_Application::getInstance()->getApplications('%')->toArray();
        
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
        
        $tags = Admin_Controller_Tags::getInstance()->search($filter, $paging);
        
        $result = array(
            'results'     => $this->_multipleRecordsToJson($tags),
            'totalcount'  => Admin_Controller_Tags::getInstance()->searchCount($filter)
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
            'name'        => '%' . $query . '%',
            'description' => '%' . $query . '%'
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
     * @param   array $roleMembers     role rights
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
     * @todo    get right description from Tinebase_Application/Acl_Rights
     * @todo    get only active applications rights?
     */
    public function getAllRoleRights()
    {
        $result = array();
        
        // get all applications
        $applications = Admin_Controller_Application::getInstance()->search(NULL, 'name', 'ASC', NULL, NULL);
        
        foreach ( $applications as $application ) {
            $appId = $application->getId();
            $rightsForApplication = array(
                "application_id"    => $appId,
                "text"              => $application->name,
                "children"          => array()
            );
            
            $allAplicationRights = Tinebase_Application::getInstance()->getAllRights($appId);
            
            foreach ( $allAplicationRights as $right ) {
                $description = Tinebase_Application::getInstance()->getRightDescription($appId, $right);
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
    
}
