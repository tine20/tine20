<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the admin application
 *
 * @package     Admin
 */
class Admin_Json extends Tinebase_Application_Json_Abstract
{
    /**
     * the application name
     *
     * @var string
     */
    protected $_appname = 'Admin';
    
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
    public function getAccounts($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $accounts = Admin_Controller::getInstance()->getFullAccounts($filter, $sort, $dir, $start, $limit);

        /*foreach($accounts as $key => $account) {
            if($account['last_login'] !== NULL) {
                 $accounts[$key]['last_login'] = $account['last_login']->get(Zend_Date::ISO_8601);
            }
            if($account['last_password_change'] !== NULL) {
                 $accounts[$key]['last_password_change'] = $account['last_password_change']->get(Zend_Date::ISO_8601);
            }
            if($account['expires_at'] !== NULL) {
                 $accounts[$key]['expires_at'] = $account['expires_at']->get(Zend_Date::ISO_8601);
            }
        }*/
        
        $result['results'] = $accounts->toArray();
        $result['totalcount'] = count($accounts);
        
        return $result;
    }

    /**
     * save account
     *
     * @param string $accountData JSON encoded Tinebase_Account_Model_FullAccount
     * @param string $password the new password
     * @param string $password2 the new password repeated
     * @return array with 
     */
    public function saveAccount($accountData, $password, $password2)
    {
        $decodedAccountData = Zend_Json::decode($accountData);
        
        $account = new Tinebase_Account_Model_FullAccount();
        
        try {
            $account->setFromArray($decodedAccountData);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $account->getValidationErrors(),
                            'errorMessage'      => 'invalid data for some fields');

            return $result;
        }
        
        if($account->getId() == NULL) {
            $account = Admin_Controller::getInstance()->addAccount($account, $password, $password2);
        } else {
            $account = Admin_Controller::getInstance()->updateAccount($account, $password, $password2);
        }
        
        $result = $account->toArray();
        
        return $result;
        
    }
    
    /**
     * delete accounts
     *
     * @param   string $accountIds  json encoded array of account ids
     * @return  array with success flag
     */
    public function deleteAccounts($accountIds)
    {
        $result = array(
            'success' => TRUE
        );

        $accountIds = Zend_Json::decode($accountIds);

        Admin_Controller::getInstance()->deleteAccounts($accountIds);
        
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
        
        $controller = Admin_Controller::getInstance();
        
        foreach($accountIds as $accountId) {
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
     * @param string $account JSON encoded Tinebase_Account_Model_FullAccount
     * @param string $password the new password
     * @return array
     */
    public function resetPassword($account, $password)
    {
        $account = new Tinebase_Account_Model_FullAccount(Zend_Json::decode($account));
        
        $controller = Admin_Controller::getInstance();

        $controller->setAccountPassword($account, $password, $password);
        
        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }
    
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

            Admin_Controller::getInstance()->deleteAccessLogEntries($logIds);

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
     * get application
     *
     * @param   int $applicationId application id to get
     * @return  array with application data
     */
    public function getApplication($applicationId)
    {
        $application = Admin_Controller::getInstance()->getApplication($applicationId);
        
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
        if(empty($filter)) {
            $filter = NULL;
        }
        
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $applicationSet = Admin_Controller::getInstance()->getApplications($filter, $sort, $dir, $start, $limit);

        $result['results']    = $applicationSet->toArray();
        if($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = Admin_Controller::getInstance()->getTotalApplicationCount($filter);
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

        Admin_Controller::getInstance()->setApplicationState($applicationIds, $state);

        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }

    /**
     * get list of access log entries
     *
     * @param string $from (date format example: 2008-03-31T00:00:00)
     * @param string $to (date format example: 2008-03-31T00:00:00)
     * @param string $filter
     * @param string $sort
     * @param string $dir
     * @param int $start
     * @param int $limit
     * 
     * @return array with results array & totalcount (int)
     */
    public function getAccessLogEntries($from, $to, $filter, $sort, $dir, $start, $limit)
    {
        /*if (!Zend_Date::isDate($from, 'YYYY-MM-dd hh:mm:ss')) {
            throw new Exception('invalid date specified for $from');
        }
        if (!Zend_Date::isDate($to, 'YYYY-MM-dd hh:mm:ss')) {
            throw new Exception('invalid date specified for $to');
        }*/
        
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        // debug params
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' function params: '. $from . ', ' . $to . ', ... ');
        
        $fromDateObject = new Zend_Date($from, Zend_Date::ISO_8601);
        $toDateObject = new Zend_Date($to, Zend_Date::ISO_8601);
        
        $accessLogSet = Admin_Controller::getInstance()->getAccessLogEntries($filter, $sort, $dir, $start, $limit, $fromDateObject, $toDateObject);
        
        $result['results']    = $accessLogSet->toArray();
        if($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = Admin_Controller::getInstance()->getTotalAccessLogEntryCount($fromDateObject, $toDateObject, $filter);
        }
        
        foreach($result['results'] as $key => $value) {
            try {
                $result['results'][$key]['accountObject'] = Admin_Controller::getInstance()->getAccount($value['account_id'])->toArray();
            } catch (Exception $e) {
                // account not found
                // do nothing so far
                Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' account ' . $value['account_id'] .' not found');
            }
        }
        
        return $result;
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
        
        $groups = Admin_Controller::getInstance()->getGroups($filter, $sort, $dir, $start, $limit);

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
        
        $members = Admin_Controller::getInstance()->getGroupMembers($groupId);
        
        $result['results'] = $members;
        $result['totalcount'] = count($members);
        
        return $result;
    }
        
    /**
     * save group data from edit form
     *
     * @param   string $groupData        json encoded group data
     * @param   string $groupMembers     json encoded array of group members
     * 
     * @return  array with success, message, group data and group members
     */
    public function saveGroup($groupData, $groupMembers)
    {
        $decodedGroupData = Zend_Json::decode($groupData);
        $decodedGroupMembers = Zend_Json::decode($groupMembers);
        
        // unset if empty
        if(empty($decodedGroupData['id'])) {
            unset($decodedGroupData['id']);
        }
        
        $group = new Tinebase_Group_Model_Group();
        
        try {
            $group->setFromArray($decodedGroupData);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $group->getValidationErrors(),
                            'errorMessage'      => 'invalid data for some fields');

            return $result;
        }
        
        if ( empty($group->id) ) {
            $group = Admin_Controller::getInstance()->addGroup($group, $decodedGroupMembers);
        } else {
            $group = Admin_Controller::getInstance()->updateGroup($group, $decodedGroupMembers);
        }
                 
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $group->toArray(),
                        'groupMembers'      => Admin_Controller::getInstance()->getGroupMembers($group->getId())
        );
        
        return $result;
        
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
        
        Admin_Controller::getInstance()->deleteGroups($groupIds);

        return $result;
    }

    /**
     * Returns the structure of the initial tree for this application.
     *
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
     * 
     * @todo    is this function @deprecated ?
     */
    public function getInitialTree()
    {
        $treeNodes = array();

/*        $treeNode = new Tinebase_Ext_Treenode('Admin', 'applications', 'applications', 'Applications', TRUE);
        //$treeNode->setIcon('apps/kaddressbook.png');
        $treeNode->cls = 'treemain';
        $treeNode->jsonMethod = 'Admin.getApplications';
        $treeNode->dataPanelType = 'applications';
        $treeNodes[] = $treeNode;

        $treeNode = new Tinebase_Ext_Treenode('Admin', 'accesslog', 'accesslog', 'Access Log', TRUE);
        //$treeNode->setIcon('apps/kaddressbook.png');
        $treeNode->cls = 'treemain';
        $treeNode->jsonMethod = 'Admin.getAccessLog';
        $treeNode->dataPanelType = 'accesslog';
        $treeNodes[] = $treeNode;
*/
        return $treeNodes;
    }
        
}