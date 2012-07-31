<?php
/**
 * Tine 2.0
 * @package     Crm
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $result = $this->_search($filter, $paging, $this->_controller, 'Crm_Model_LeadFilter', TRUE);
        
        // add totalcounts of leadstates/leadsources/leadtypes
        $result['totalleadstates'] = $result['totalcount']['leadstates'];
        $result['totalleadsources'] = $result['totalcount']['leadsources'];
        $result['totalleadtypes'] = $result['totalcount']['leadtypes'];
        
        $result['totalcount'] = $result['totalcount']['totalcount'];
                
        return $result;
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getLead($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveLead($recordData)
    {
        return $this->_save($recordData, $this->_controller, 'Lead');
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
        $settings = $this->getSettings();
        $defaults = $settings['defaults'];
        $defaults['container_id'] = $this->getDefaultContainer();
        
        $registryData = array(
            'leadtypes'     => array(
                'results' => $settings[Crm_Model_Config::LEADTYPES],
                'totalcount' => count($settings[Crm_Model_Config::LEADTYPES])
            ),
            'leadstates'    => array(
                'results' => $settings[Crm_Model_Config::LEADSTATES],
                'totalcount' => count($settings[Crm_Model_Config::LEADSTATES])
            ),
            'leadsources'   => array(
                'results' => $settings[Crm_Model_Config::LEADSOURCES],
                'totalcount' => count($settings[Crm_Model_Config::LEADSOURCES])
            ),
            'defaults'      => $defaults,
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
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer($this->_applicationName, NULL, Crm_Preference::DEFAULTLEADLIST)->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(
            Tinebase_Core::getUser(),
            $defaultContainerArray['id']
        )->toArray();
        
        return $defaultContainerArray;
    }
    
    /**
     * Returns settings for crm app
     *
     * @return  array record data
     *
     * @todo    return json store style with totalcount/result?
     */
    public function getSettings()
    {
        $result = Crm_Controller::getInstance()->getConfigSettings()->toArray();
        
        return $result;
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
