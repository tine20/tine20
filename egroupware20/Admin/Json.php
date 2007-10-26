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
class Admin_Json
{
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
        
        $egwApplications = new Egwbase_Application();
        
        $applicationSet = $egwApplications->getApplications($sort, $dir, $filter, $limit, $start);

        $result['results']    = $applicationSet->toArray();
        $result['totalcount'] = $egwApplications->getTotalApplicationCount();
        
        return $result;
    }

    public function getAccessLog($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $egwAccessLog = new Egwbase_AccessLog();

        $accessLogSet = $egwAccessLog->getAccessLog($sort, $dir, $filter, $limit, $start);
        
        $arrayAccessLogRowSet = $accessLogSet->toArray();
        
        $dateFormat = Zend_Registry::get('locale')->getTranslationList('Dateformat');
        $timeFormat = Zend_Registry::get('locale')->getTranslationList('Timeformat');
        
        foreach($arrayAccessLogRowSet as $id => $row) {
            $row['li'] = $row['li']->get($dateFormat['default'] . ' ' . $timeFormat['default'], Zend_Registry::get('locale'));
            if($row['lo'] instanceof Zend_Date) {
                $row['lo'] = $row['lo']->get($dateFormat['default'] . ' ' . $timeFormat['default'], Zend_Registry::get('locale'));
            //} else {
            //    $row['lo'] = '';
            }
            $arrayAccessLogRowSet[$id] = $row;
        }
        
        $result['results']    = $arrayAccessLogRowSet;
        $result['totalcount'] = $egwAccessLog->getTotalCount();
        
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
    public function getInitialTree($_location)
    {
        switch($_location) {
            case 'mainTree':
                $treeNodes = array();

                $treeNode = new Egwbase_Ext_Treenode('Admin', 'applications', 'applications', 'Applications', TRUE);
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

                return $treeNodes;
                 
                break;
        }
    }
}