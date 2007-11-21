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
    
    public function deleteAccessLogEntries($logIds)
    {
        try {
            $logIds = Zend_Json::decode($logIds);

            $egwAccessLog = new Egwbase_AccessLog();
            $egwAccessLog->deleteEntries($logIds);

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
    
    public function getApplications($filter, $sort, $dir, $limit, $start)
    {
        if(empty($filter)) {
            $filter = NULL;
        }
        
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $egwApplications = Egwbase_Application::getInstance();
        
        $applicationSet = $egwApplications->getApplications($sort, $dir, $filter, $limit, $start);

        $result['results']    = $applicationSet->toArray();
        if(count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = $egwApplications->getTotalApplicationCount();
        }
        
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
        
        $egwAccessLog = new Egwbase_AccessLog();

        $accessLogSet = $egwAccessLog->getEntries($fromDateObject, $toDateObject, $sort, $dir, $filter, $limit, $start);
        
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
        if(count($result['results']) < $limit) {
            $result['totalcount'] = count($result['results']);
        } else {
            $result['totalcount'] = $egwAccessLog->getTotalCount();
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