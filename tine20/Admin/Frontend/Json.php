<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the admin application
 *
 * @package     Admin
 */
class Admin_Frontend_Json extends Tinebase_Application_Frontend_Json_Abstract
{
    /**
     * the application name
     *
     * @var string
     */
    protected $_appname = 'Admin';
    
    /******************************* Access Log *******************************/
    
    /**
     * delete access log entries
     *
     * @param string $logIds json encoded list of logIds to delete
     * @return array with success flag
     */
    public function deleteAccessLogEntries($logIds)
    {
        try {
            $logIds = Zend_Json::decode($logIds);

            Admin_Controller_AccessLog::getInstance()->deleteAccessLogEntries($logIds);

            $result = array(
                'success' => TRUE
            );
        } catch (Exception $e) {
            $result = array(
                'success' => FALSE 
            );
        }
        
        return $result;
    }
    
    
    /**
     * get list of access log entries
     *
     * @param string $from (date format example: 2008-03-31T00:00:00)
     * @param string $to (date format example: 2008-03-31T00:00:00)
     * @param string $filter
     * @param string $paging json encoded pagin data (Tinebase_Model_Pagination)
     * 
     * @return array with results array & totalcount (int)
     */
    public function getAccessLogEntries($from, $to, $filter, $paging)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        // debug params
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' function params: '. $from . ', ' . $to . ', ... ');
        
        $fromDateObject = new Zend_Date($from, Tinebase_Record_Abstract::ISO8601LONG);
        $toDateObject = new Zend_Date($to, Tinebase_Record_Abstract::ISO8601LONG);
        $pagination = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        $accessLogSet = Admin_Controller_AccessLog::getInstance()->getAccessLogEntries($filter, $pagination, $fromDateObject, $toDateObject);
        
        $result['results']    = $accessLogSet->toArray();
        if (count($result['results']) < $pagination->limit) {
            $result['totalcount'] = $pagination->start + count($result['results']);
        } else {
            $result['totalcount'] = Admin_Controller_AccessLog::getInstance()->getTotalAccessLogEntryCount($fromDateObject, $toDateObject, $filter);
        }
        
        foreach ($result['results'] as $key => $value) {
            try {
                $result['results'][$key]['accountObject'] = Admin_Controller_User::getInstance()->getAccount($value['account_id'])->toArray();
            } catch (Tinebase_Exception_NotFound $e) {
                // account not found
                // do nothing so far
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' account ' . $value['account_id'] .' not found');
            }
        }
        
        return $result;
    }
    
    /****************************** Applications ******************************/
    
    /**
     * get application
     *
     * @param   int $applicationId application id to get
     * @return  array with application data
     */
    public function getApplication($applicationId)
    {
        $application = Admin_Controller_Application::getInstance()->getApplication($applicationId);
        
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
        
        $applicationSet = Admin_Controller_Application::getInstance()->getApplications($filter, $sort, $dir, $start, $limit);

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
     * @param   string $applicationIds  json encoded array of application ids
     * @param   string $state           state to set
     * @return  array with success flag
     */
    public function setApplicationState($applicationIds, $state)
    {
        $applicationIds = Zend_Json::decode($applicationIds);

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
     * @param int $userId
     */
    public function getUser($userId)
    {
        if (!empty($userId)) {
            $user = Tinebase_User::getInstance()->getFullUserById($userId);
            $user->setTimezone(Zend_Registry::get('userTimeZone'));
            $userArray = $user->toArray();
            
            // add primary group to account for the group selection combo box
            $group = Tinebase_Group::getInstance()->getGroupById($user->accountPrimaryGroup);
        } else {
            $userArray = array('accountStatus' => 'enabled');
            
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
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $accounts = Admin_Controller_User::getInstance()->getFullUsers($filter, $sort, $dir, $start, $limit);

        /*foreach($accounts as $key => $account) {
            if ($account['last_login'] !== NULL) {
                 $accounts[$key]['last_login'] = $account['last_login']->get(Tinebase_Record_Abstract::ISO8601LONG);
            }
            if ($account['last_password_change'] !== NULL) {
                 $accounts[$key]['last_password_change'] = $account['last_password_change']->get(Tinebase_Record_Abstract::ISO8601LONG);
            }
            if ($account['expires_at'] !== NULL) {
                 $accounts[$key]['expires_at'] = $account['expires_at']->get(Tinebase_Record_Abstract::ISO8601LONG);
            }
        }*/
        
        $result['results'] = $accounts->toArray();
        $result['totalcount'] = count($accounts);
        
        return $result;
    }

    /**
     * save user
     *
     * @param string $accountData JSON encoded Tinebase_Model_FullUser
     * @param string $password the new password
     * @param string $passwordRepeat the new password repeated
     * @return array with 
     */
    public function saveUser($accountData, $password, $passwordRepeat)
    {
        $decodedAccountData = Zend_Json::decode($accountData);
        
        $account = new Tinebase_Model_FullUser();
        
        try {
            $account->setFromArray($decodedAccountData);
        } catch (Tinebase_Record_Exception_Validation $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $account->getValidationErrors(),
                            'errorMessage'      => 'invalid data for some fields');

            return $result;
        }
        
        if ($account->getId() == NULL) {
            $account = Admin_Controller_User::getInstance()->addUser($account, $password, $passwordRepeat);
        } else {
            $account = Admin_Controller_User::getInstance()->updateUser($account, $password, $passwordRepeat);
        }
        
        $account->accountPrimaryGroup = Tinebase_Group::getInstance()->getGroupById($account->accountPrimaryGroup);
        $result = $account->toArray();
        
        
        return $result;
        
    }
    
    /**
     * delete users
     *
     * @param   string $accountIds  json encoded array of account ids
     * @return  array with success flag
     */
    public function deleteUsers($accountIds)
    {
        $accountIds = Zend_Json::decode($accountIds);
        Admin_Controller_User::getInstance()->deleteUsers($accountIds);
        
        $result = array(
            'success' => TRUE
        );
        return $result;
    }

    /**
     * set account state
     *
     * @param   string $accountIds  json encoded array of account ids
     * @param   string $state      state to set
     * @return  array with success flag
     */
    public function setAccountState($accountIds, $state)
    {
        $accountIds = Zend_Json::decode($accountIds);
        
        $controller = Admin_Controller_User::getInstance();
        foreach ($accountIds as $accountId) {
            $controller->setAccountStatus($accountId, $state);
        }

        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }
    
    /**
     * reset password for given account
     *
     * @param string $account JSON encoded Tinebase_Model_FullUser
     * @param string $password the new password
     * @return array
     */
    public function resetPassword($account, $password)
    {
        $account = new Tinebase_Model_FullUser(Zend_Json::decode($account));
        
        $controller = Admin_Controller_User::getInstance();
        $controller->setAccountPassword($account, $password, $password);
        
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
     * @return array  items with appended name 
     * @throws UnexpectedValueException 
     */
    public static function resolveAccountName(array $_items, $_hasAccountPrefix=false)
    {
        $prefix = $_hasAccountPrefix ? 'account_' : '';
        
        $return = array();
        foreach ($_items as $num => $item) {
            
            switch ($item[$prefix . 'type']) {
                case 'user':
                    $item[$prefix . 'name'] = Tinebase_User::getInstance()->getUserById($item[$prefix . 'id'])->accountDisplayName;
                    break;
                case 'group':
                    $item[$prefix . 'name'] = Tinebase_Group::getInstance()->getGroupById($item[$prefix . 'id'])->name;
                    break;
                case 'anyone':
                    $item[$prefix . 'name'] = 'Anyone';
                    break;
                default:
                    throw new UnexpectedValueException('Unsupported accountType: ' . $item[$prefix . 'type']);
                    break;
            }
            $return[$num] = $item;
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
            $group = Admin_Controller_Group::getInstance()->getGroup($groupId)->toArray();
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
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $groups = Admin_Controller_Group::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);

        $result['results'] = $groups->toArray();
        $result['totalcount'] = count($groups);
        
        return $result;
    }

    /**
     * get list of groupmembers
     *
     * @param int $groupId
     * @return array with results / totalcount
     */
    public function getGroupMembers($groupId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if ($groupId) {
            $accountIds = Admin_Controller_Group::getInstance()->getGroupMembers($groupId);
    
            $result['results'] = array ();
            foreach ( $accountIds as $accountId ) {
                $result['results'][] = Tinebase_User::getInstance()->getUserById($accountId)->toArray();
            }
                    
            $result['totalcount'] = count($result['results']);
        }
        
        return $result;
    }
        
    /**
     * save group data from edit form
     *
     * @param   string $groupData        json encoded group data
     * @param   string $groupMembers     json encoded array of group members
     * 
     * @return  array
     */
    public function saveGroup($groupData, $groupMembers)
    {
        $decodedGroupData = Zend_Json::decode($groupData);
        $decodedGroupMembers = Zend_Json::decode($groupMembers);
        
        // unset if empty
        if (empty($decodedGroupData['id'])) {
            unset($decodedGroupData['id']);
        }
        
        $group = new Tinebase_Model_Group($decodedGroupData);
        
        if ( empty($group->id) ) {
            $group = Admin_Controller_Group::getInstance()->addGroup($group, $decodedGroupMembers);
        } else {
            $group = Admin_Controller_Group::getInstance()->updateGroup($group, $decodedGroupMembers);
        }

        return $this->getGroup($group->getId());
        
    }    
        
    /**
     * delete multiple groups
     *
     * @param string $groupIds json encoded list of contactId's to delete
     * @return array with success flag
     */
    public function deleteGroups($groupIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $groupIds = Zend_Json::decode($groupIds);
        
        Admin_Controller_Group::getInstance()->deleteGroups($groupIds);

        return $result;
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
            $tag = Admin_Controller_Tags::getInstance()->getTag($tagId)->toArray();
            //$tag->rights = $tag->rights->toArray();
            $tag['rights'] = Admin_Json::resolveAccountName($tag['rights'] , true);
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
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $tags = Admin_Controller_Tags::getInstance()->getTags($query, $sort, $dir, $start, $limit);

        $result['results'] = $tags->toArray();
        $result['totalcount'] = count($tags);
        
        return $result;
    }
        
    /**
     * save tag data from edit form
     *
     * @param   string $tagData
     * 
     * @return  array with success, message, tag data and tag members
     */
    public function saveTag($tagData)
    {
        $decodedTagData = Zend_Json::decode($tagData);
        
        //Zend_Registry::get('logger')->debug(print_r($decodedTagData,true));
        // unset if empty
        if (empty($decodedTagData['id'])) {
            unset($decodedTagData['id']);
        }
        
        $tag = new Tinebase_Model_FullTag($decodedTagData);
        $tag->rights = new Tinebase_Record_RecordSet('Tinebase_Model_TagRight', $decodedTagData['rights']);
        //Zend_Registry::get('logger')->debug(print_r($tag->toArray(),true));
        
        if ( empty($tag->id) ) {
            $tag = Admin_Controller_Tags::getInstance()->addTag($tag);
        } else {
            $tag = Admin_Controller_Tags::getInstance()->updateTag($tag);
        }
        
        return $this->getTag($tag->getId());
        
    }    
        
    /**
     * delete multiple tags
     *
     * @param string $tagIds json encoded list of contactId's to delete
     * @return array with success flag
     */
    public function deleteTags($tagIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $tagIds = Zend_Json::decode($tagIds);
        
        Admin_Controller_Tags::getInstance()->deleteTags($tagIds);

        return $result;
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
            $role = Admin_Controller_Role::getInstance()->getRole($roleId)->toArray();
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
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $roles = Admin_Controller_Role::getInstance()->getRoles($query, $sort, $dir, $start, $limit);

        //$result['results'] = array ( array("name" => "role1", "description" => "blabla", "id" => 1) );
        //$result['totalcount'] = 1;
        
        $result['totalcount'] = count($roles);
        $result['results'] = $roles->toArray();
        
        return $result;
    }

    /**
     * save role data from edit form
     *
     * @param   string $roleData        json encoded role data
     * @param   string $roleMembers     json encoded role members
     * @param   string $roleMembers     json encoded role rights
     * @return  array
     */
    public function saveRole($roleData, $roleMembers, $roleRights)
    {
        $decodedRoleData = Zend_Json::decode($roleData);
        $decodedRoleMembers = Zend_Json::decode($roleMembers);
        $decodedRoleRights = Zend_Json::decode($roleRights);
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($decodedRoleData, true));
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($decodedRoleMembers, true));
        
        // unset if empty
        if (empty($decodedRoleData['id'])) {
            unset($decodedRoleData['id']);
        }
        
        $role = new Tinebase_Model_Role($decodedRoleData);
        
        if (empty($role->id) ) {
            $role = Admin_Controller_Role::getInstance()->addRole($role, $decodedRoleMembers, $decodedRoleRights);
        } else {
            $role = Admin_Controller_Role::getInstance()->updateRole($role, $decodedRoleMembers, $decodedRoleRights);
        }
        
        return $this->getRole($role->getId());
    }    

    /**
     * delete multiple roles
     *
     * @param string $roleIds json encoded list of roleId's to delete
     * @return array with success flag
     */
    public function deleteRoles($roleIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $roleIds = Zend_Json::decode($roleIds);
        
        Admin_Controller_Role::getInstance()->deleteRoles($roleIds);

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
    
            $result['results'] = self::resolveAccountName($members, true);
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
        $applications = Admin_Controller_Application::getInstance()->getApplications(NULL, 'name', 'ASC', NULL, NULL);
        
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