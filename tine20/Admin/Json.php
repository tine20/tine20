<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html
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
    
    public function getAccounts($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $accounts = Tinebase_Account::getInstance()->getFullAccounts($filter, $sort, $dir, $start, $limit);

        /*foreach($accounts as $key => $account) {
            if($account['account_lastlogin'] !== NULL) {
                 $accounts[$key]['account_lastlogin'] = $account['account_lastlogin']->get(Zend_Date::ISO_8601);
            }
            if($account['account_lastpwd_change'] !== NULL) {
                 $accounts[$key]['account_lastpwd_change'] = $account['account_lastpwd_change']->get(Zend_Date::ISO_8601);
            }
            if($account['account_expires'] !== NULL) {
                 $accounts[$key]['account_expires'] = $account['account_expires']->get(Zend_Date::ISO_8601);
            }
        }*/
        
        $result['results'] = $accounts->toArray();
        $result['totalcount'] = count($accounts);
        
        return $result;
    }
    
    public function deleteAccessLogEntries($logIds)
    {
        try {
            $logIds = Zend_Json::decode($logIds);

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
    
    public function getApplication($applicationId)
    {
        $tineApplications = new Tinebase_Application();
        
        $application = $tineApplications->getApplicationById($applicationId);
        
        return $application->toArray();
    }
    
    public function getApplications($filter, $sort, $dir, $start, $limit)
    {
        if(empty($filter)) {
            $filter = NULL;
        }
        
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
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
    
    public function setApplicationState($applicationIds, $state)
    {
        $applicationIds = Zend_Json::decode($applicationIds);

        Tinebase_Application::getInstance()->setApplicationState($applicationIds, $state);

        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }

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
        
        $accessLogSet = Tinebase_AccessLog::getInstance()->getEntries($filter, $sort, $dir, $start, $limit, $fromDateObject, $toDateObject);
        
        $result['results']    = $accessLogSet->toArray();
        if($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = Tinebase_AccessLog::getInstance()->getTotalCount($fromDateObject, $toDateObject, $filter);
        }
        
        foreach($result['results'] as $key => $value) {
            try {
                $result['results'][$key]['accountObject'] = Tinebase_Account::getInstance()->getAccountById($value['account_id'])->toArray();
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