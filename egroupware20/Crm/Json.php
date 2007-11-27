<?php
/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Crm application
 *
 * @package     Crm
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Json.php 199 2007-10-15 16:30:00Z twadewitz $
 *
 */
class Crm_Json extends Egwbase_Application_Json_Abstract
{

    protected $_appname = 'Crm';
  
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
        $currentAccount = Zend_Registry::get('currentAccount');
         
        switch($_location) {
            case 'mainTree':
                $treeNodes = array();
                
                $treeNode = new Egwbase_Ext_Treenode('Crm', 'projekte', 'projekte', 'Projekte', FALSE);
                $treeNode->setIcon('apps/package-multimedia.png');
                $treeNode->cls = 'treemain';
                $treeNode->contextMenuClass = 'ctxMenuProject';
                $treeNode->owner = 'allprojects';
                $treeNode->jsonMethod = 'Crm.getProjectsByOwner';
                $treeNode->dataPanelType = 'projects';

                $childNode = new Egwbase_Ext_Treenode('Crm', 'leads', 'leads', 'Leads', TRUE);
                $childNode->owner = $currentAccount->account_id;
                $childNode->jsonMethod = 'Crm.getLeadsByOwner';
                $childNode->dataPanelType = 'leads';
                $childNode->contextMenuClass = 'ctxMenuLeadsTree';
                $treeNode->addChildren($childNode);
                
                $childNode = new Egwbase_Ext_Treenode('Crm', 'partner', 'partner', 'Partner', TRUE);
                $childNode->owner = $currentAccount->account_id;
                $childNode->jsonMethod = 'Crm.getPartnerByOwner';
                $childNode->dataPanelType = 'partner';
                $childNode->contextMenuClass = 'ctxMenuPartnerTree';
                $treeNode->addChildren($childNode);
                
                $treeNodes[] = $treeNode;

                return $treeNodes;
                 
                break;
        }
    }   
  
    
    /**
     * returns the nodes for the dynamic tree
     *
     * @param string $node which node got selected in the UI
     * @param string $datatype what kind of data to search
     * @return string json encoded array
     */
    public function getSubTree($node, $owner, $datatype, $location)
    {
        $nodes = array();
     
            switch($datatype) {
                case 'projects':
                   //  $backend = Crm_Backend::factory(Crm_Backend::SQL);
                   //  $venues = $backend->getVenues();
                    
                    $venues = array('0' => array('id' => '1', 'name' => 'Schmidt Theater'),
                                    '1' => array('id' => '2', 'name' => 'Schmidts TIVOLI'));
                                        
                    // foreach($venues as $venueObject) {
                     foreach($venues as $venue) {
                    
                        $treeNode = new Egwbase_Ext_Treenode(
                            'Crm',
                            'stages',
                            'venue-'.$venue['id'], 
                            $venue['name'],
                          //  $venueObject->venue_id, 
                          //  $venueObject->venue_name,
                            FALSE
                        );
                        $treeNode->contextMenuClass = 'ctxMenuHouse';
                        $treeNode->venueId = $venue['id'];
                       // $treeNode->jsonMethod = 'Addressbook.getContactsByListId';
                        $treeNode->dataPanelType = 'venues';
                      //  $treeNode->owner  = $owner;
                        $nodes[] = $treeNode;
                    }
                break;                  


               case 'stages':
                // $backend = Crm_Backend::factory(Crm_Backend::SQL);
               //  $stages = $backend->getStages();
                
                $stages = array('0' => array('id' => '1', 'name' => 'B&uuml;hne A'),
                                    '1' => array('id' => '2', 'name' => 'B&uuml;hne B'),
                                    '2' => array('id' => '3', 'name' => 'B&uuml;hne C'));
                
                
                // foreach($stages as $stageObject) {
                 foreach($stages as $stage) {
                    $treeNode = new Egwbase_Ext_Treenode(
                        'Crm',
                        'events',
                        'stage-'.$stage['id'], 
                        $stage['name'],
                      //  $stageObject->stage_id, 
                      //  $stageObject->stage_name,
                        TRUE
                    );
                    $treeNode->contextMenuClass = 'ctxMenuEvent';
                    $treeNode->stageId = $stage['id'];
                   // $treeNode->jsonMethod = 'Addressbook.getContactsByListId';
                    $treeNode->dataPanelType = 'stages';
                  //  $treeNode->owner  = $owner;
                    $nodes[] = $treeNode;
                }
               break;			
        }

        echo Zend_Json::encode($nodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
     }
     

  /**
	 * save one project
	 *
	 * if $_projectId is 0 the project gets added, otherwise it gets updated
	 *
	 * @return array
	 */	
	public function saveProject()
    {
        if(empty($_POST['pj_id'])) {
            unset($_POST['pj_id']);
        }

        $project = new Crm_Project();
        try {
            $project->setFromUserData($_POST);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $project->getValidationErrors(),
                            'errorMessage'      => 'filter NOT ok');

            return $result;
        }

        $backend = Crm_Backend::factory(Crm_Backend::SQL);
         
        try {
            $backend->saveProject($project);
            $result = array('success'           => true,
                            'welcomeMessage'    => 'Entry updated');
        } catch (Exception $e) {
            $result = array('success'           => false,
        					'errorMessage'      => $e->getMessage());
        }

        return $result;
         
    }      
      
     
    public function getProjectsByOwner($filter, $owner, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        if(empty($filter)) {
            $filter = NULL;
        }

        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        if($rows = $backend->getProjectsByOwner($owner, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows;
            //$result['totalcount'] = $backend->getCountByOwner($owner);
        }

        return $result;
    }
     
   public function getProjectstate()
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
error_log('JSON :: getProjectstate');
        $backend = Crm_Backend::factory(Crm_Backend::SQL);
        if($rows = $backend->getProjectstates()) {
                 
            $result['results']    = $rows;
        }

        return $result;
    }     
     
     
}