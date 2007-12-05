<?php
/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the admin application
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Admin_Json extends Egwbase_Application_Json_Abstract
{
    protected $_appname = 'Admin';
    
    public function getAccounts($filter, $sort, $dir, $start, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $accounts = Admin_Controller::getInstance()->getAccounts($filter, $sort, $dir, $start, $limit);

        foreach($accounts as $key => $account) {
            if($account['account_lastlogin'] !== NULL) {
                 $accounts[$key]['account_lastlogin'] = $account['account_lastlogin']->get(Zend_Date::ISO_8601);
            }
            if($account['account_lastpwd_change'] !== NULL) {
                 $accounts[$key]['account_lastpwd_change'] = $account['account_lastpwd_change']->get(Zend_Date::ISO_8601);
            }
            if($account['account_expires'] !== NULL) {
                 $accounts[$key]['account_expires'] = $account['account_expires']->get(Zend_Date::ISO_8601);
            }
        }
        
        $result['results'] = $accounts;
        $result['totalcount'] = count($accounts);
        
        return $result;
        return $result->toArray();
    }
    
    public function deleteAccessLogEntries($logIds)
    {
        try {
            $logIds = Zend_Json::decode($logIds);

            Egwbase_AccessLog::getInstance()->deleteEntries($logIds);

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
        $egwApplications = new Egwbase_Application();
        
        $application = $egwApplications->getApplicationById($applicationId);
        
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
        
        $egwApplications = Egwbase_Application::getInstance();
        
        $applicationSet = $egwApplications->getApplications($filter, $sort, $dir, $start, $limit);

        $result['results']    = $applicationSet->toArray();
        if($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = $egwApplications->getTotalApplicationCount($filter);
        }
        
        return $result;
    }
    
    public function setApplicationState($applicationIds, $state)
    {
        $applicationIds = Zend_Json::decode($applicationIds);

        Egwbase_Application::getInstance()->setApplicationState($applicationIds, $state);

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
    
    public function resetPassword($accountId, $password)
    {
        $accountIds = Zend_Json::decode($accountIds);
        
        $controller = Admin_Controller::getInstance();

        $controller->setAccountPassword($accountId, $password, $password);
        
        $result = array(
            'success' => TRUE
        );
        
        return $result;
    }
    
    public function getAccessLogEntries($from, $to, $filter, $sort, $dir, $limit, $start)
    {
        if (!Zend_Date::isDate($from, 'YYYY-MM-dd hh:mm:ss')) {
            throw new Exception('invalid date specified for $from');
        }
        if (!Zend_Date::isDate($to, 'YYYY-MM-dd hh:mm:ss')) {
            throw new Exception('invalid date specified for $to');
        }
        
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $fromDateObject = new Zend_Date($from, Zend_Date::ISO_8601);
        $toDateObject = new Zend_Date($to, Zend_Date::ISO_8601);
        
        $egwAccessLog = Egwbase_AccessLog::getInstance();

        $accessLogSet = $egwAccessLog->getEntries($fromDateObject, $toDateObject, $filter, $sort, $dir, $start, $limit);
        
        $arrayAccessLogRowSet = $accessLogSet->toArray();

        $dateFormat = Zend_Registry::get('locale')->getTranslationList('Dateformat');
        $timeFormat = Zend_Registry::get('locale')->getTranslationList('Timeformat');
        
        foreach($arrayAccessLogRowSet as $id => $row) {
            $row['li'] = $row['li']->get(Zend_Date::ISO_8601);
            if($row['lo'] instanceof Zend_Date) {
                $row['lo'] = $row['lo']->get(Zend_Date::ISO_8601);
            }
            $arrayAccessLogRowSet[$id] = $row;
        }
        
        $result['results']    = $arrayAccessLogRowSet;
        if($start == 0 && count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = $egwAccessLog->getTotalCount($fromDateObject, $toDateObject, $filter);
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

/*        $treeNode = new Egwbase_Ext_Treenode('Admin', 'applications', 'applications', 'Applications', TRUE);
        //$treeNode->setIcon('apps/kaddressbook.png');
        $treeNode->cls = 'treemain';
        $treeNode->jsonMethod = 'Admin.getApplications';
        $treeNode->dataPanelType = 'applications';
        $treeNodes[] = $treeNode;

        $treeNode = new Egwbase_Ext_Treenode('Admin', 'accesslog', 'accesslog', 'Access Log', TRUE);
        //$treeNode->setIcon('apps/kaddressbook.png');
        $treeNode->cls = 'treemain';
        $treeNode->jsonMethod = 'Admin.getAccessLog';
        $treeNode->dataPanelType = 'accesslog';
        $treeNodes[] = $treeNode;
*/
        return $treeNodes;
    }
}