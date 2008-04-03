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
        
        //@todo use controller
        $accounts = Tinebase_Account::getInstance()->getFullAccounts($filter, $sort, $dir, $start, $limit);

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
     * @param array json encoded group data
     * @return array with group data
     */
    public function saveGroup($groupData)
    {
        $decodedGroupData = Zend_Json::decode($groupData);
        
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
        
        //@todo use controller
        if(isset($decodedGroupData['id'])) {
            $group = Tinebase_Group::getInstance()->updateGroup($group);
        } else {
            $group = Tinebase_Group::getInstance()->addGroup($group);
        }
         
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $group->toArray());
        
        return $result;
        
    }    
        
    /**
     * delete multiple groups
     *
     * @param array $groupIds list of contactId's to delete
     * @return array
     */
    public function deleteGroups($groupIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $groupIds = Zend_Json::decode($groupIds);
        
        //@todo use controller
        Tinebase_Group::getInstance()->deleteGroups($groupIds);

        return $result;
    }

    //@todo add phpdoc
    public function deleteAccessLogEntries($logIds)
    {
        try {
            $logIds = Zend_Json::decode($logIds);

            //@todo use controller
            Tinebase_AccessLog::getInstance()->deleteEntries($logIds);

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
    
    //@todo add phpdoc
    public function getApplication($applicationId)
    {
        $tineApplications = new Tinebase_Application();
        
        $application = $tineApplications->getApplicationById($applicationId);
        
        return $application->toArray();
    }
    
    /**
     * get list of applications
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
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
        
        //@todo use controller
        $tineApplications = Tinebase_Application::getInstance();
        
        $applicationSet = $tineApplications->getApplications($filter, $sort, $dir, $start, $limit);

        $result['results']    = $applicationSet->toArray();
        if($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = $tineApplications->getTotalApplicationCount($filter);
        }
        
        return $result;
    }
    
    //@todo add phpdoc
    public function setApplicationState($applicationIds, $state)
    {
        $applicationIds = Zend_Json::decode($applicationIds);

        //@todo use controller
        Tinebase_Application::getInstance()->setApplicationState($applicationIds, $state);

        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }

    //@todo add phpdoc
    public function setAccountState($accountIds, $status)
    {
        $accountIds = Zend_Json::decode($accountIds);
        
        $controller = Admin_Controller::getInstance();
        
        foreach($accountIds as $accountId) {
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
     * get list of access log entries
     *
     * @param string $_filter
     * @param string $_sort
     * @param string $_dir
     * @param int $_start
     * @param int $_limit
     * @return array with results array & totalcount (int)
     */
    public function getAccessLogEntries($from, $to, $filter, $sort, $dir, $limit, $start)
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
        
        $fromDateObject = new Zend_Date($from, Zend_Date::ISO_8601);
        $toDateObject = new Zend_Date($to, Zend_Date::ISO_8601);
        
        //@todo use controller
        $accessLogSet = Tinebase_AccessLog::getInstance()->getEntries($filter, $sort, $dir, $start, $limit, $fromDateObject, $toDateObject);
        
        $result['results']    = $accessLogSet->toArray();
        if($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = Tinebase_AccessLog::getInstance()->getTotalCount($fromDateObject, $toDateObject, $filter);
        }
        
        foreach($result['results'] as $key => $value) {
            try {
                $result['results'][$key]['accountObject'] = Tinebase_Account::getInstance()->getAccountById($value['id'])->toArray();
            } catch (Exception $e) {
                // account not found
                // do nothing so far
            }
        }
        
        return $result;
    }
    
    
    /**
     * Returns the structure of the initial tree for this application.
     *
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
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
    
    //@todo add phpdoc
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
        
        $saveAccount = Admin_Controller::getInstance()->saveAccount($account, $password, $password2);
        
        $result = $saveAccount->toArray();
        
        return $result;
        
    }
    
    //@todo add phpdoc
    public function deleteAccounts($accountIds)
    {
        $result = array(
            'success' => TRUE
        );

        $accountIds = Zend_Json::decode($accountIds);

        Admin_Controller::getInstance()->deleteAccounts($accountIds);
        
        return $result;
    }
    
}