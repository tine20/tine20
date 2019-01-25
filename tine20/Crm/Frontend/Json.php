<?php
/**
 * Tine 2.0
 * @package     Crm
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * This class handles all Json requests for the Crm application
 *
 * @package     Crm
 * @subpackage  Frontend
 */
class Crm_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var Crm_Controller_Lead
     */
    protected $_controller = NULL;
    
    protected $_relatableModels = array('Crm_Model_Lead');
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Crm';
        $this->_controller = Crm_Controller_Lead::getInstance();
    }
    
    /************************************** public API **************************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchLeads($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'Crm_Model_LeadFilter', array('Addressbook_Model_Contact', 'Sales_Model_Product'));
    }

    /**
     * do search count request only when resultset is equal
     * to $pagination->limit or we are not on the first page
     *
     * @param $filter
     * @param $pagination
     * @param Tinebase_Controller_SearchInterface $controller the record controller
     * @param $totalCountMethod
     * @param integer $resultCount
     * @return array
     */
    protected function _getSearchTotalCount($filter, $pagination, $controller, $totalCountMethod, $resultCount)
    {
        if ($controller instanceof Crm_Controller_Lead) {
            $result = $controller->searchCount($filter);

            $totalresult = [];

            // add totalcounts of leadstates/leadsources/leadtypes
            $totalresult['totalleadstates'] = $result['leadstates'];
            $totalresult['totalleadsources'] = $result['leadsources'];
            $totalresult['totalleadtypes'] = $result['leadtypes'];
            $totalresult['totalcount'] = $result['totalcount'];

            return $totalresult;
        } else {
            return parent:: _getSearchTotalCount($filter, $pagination, $controller, $totalCountMethod, $resultCount);
        }
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getLead($id)
    {
        $organizerIds = array();
        $lead = $this->_get($id, $this->_controller);
        
        foreach($lead['relations'] as $relation) {
            if ($relation['related_model'] == 'Tasks_Model_Task') {
                if (isset($relation['related_record'])) {
                    $organizerIds[] = $relation['related_record']['organizer'];
                }
            }
        }
        
        $be = new Tinebase_User_Sql();
        $organizers = $be->getMultiple($organizerIds);

        for ($i = 0; $i < count($lead['relations']); $i++) {
            if ($lead['relations'][$i]['related_model'] == 'Tasks_Model_Task' && isset($lead['relations'][$i]['related_record'])) {
                $organizer = $organizers->getById($lead['relations'][$i]['related_record']['organizer']);
                if ($organizer) {
                    $lead['relations'][$i]['related_record']['organizer'] = $organizer->toArray();
                }
            }
        }
        
        return $lead;
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  boolean $duplicateCheck
     * @return array created/updated record
     */
    public function saveLead($recordData, $duplicateCheck = true)
    {
        return $this->_save($recordData, $this->_controller, 'Lead', 'id' , array($duplicateCheck));
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteLeads($ids)
    {
        return $this->_delete($ids, $this->_controller);
    }

    /**
     * Returns registry data of crm.
     * @see Tinebase_Application_Json_Abstract
     *
     * @return  mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $registryData = array(
            'defaultContainer' => $this->getDefaultContainer(),
        );

        return $registryData;
    }
    
    /**
     * get default container for leads
     * 
     * @return array
     */
    public function getDefaultContainer()
    {
        $defaultContainer = Tinebase_Container::getInstance()->getDefaultContainer(Crm_Model_Lead::class, NULL, Crm_Preference::DEFAULTLEADLIST);
        $defaultContainerArray = $defaultContainer->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(
            Tinebase_Core::getUser(),
            $defaultContainer
        )->toArray();
        
        return $defaultContainerArray;
    }

    /**
     * creates/updates settings
     *
     * @return array created/updated settings
     */
    public function saveSettings($recordData)
    {
        $settings = new Crm_Model_Config($recordData);
        $result = Crm_Controller::getInstance()->saveConfigSettings($settings)->toArray();

        return $result;
    }
}
