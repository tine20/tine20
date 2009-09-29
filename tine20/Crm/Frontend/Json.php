<?php
/**
 * Tine 2.0
 * @package     Crm
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Json.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
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
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Crm';
        $this->_controller = Crm_Controller_Lead::getInstance();
    }
    
    /************************************** protected helper functions **************************************/
    
    /************************************** public API **************************************/
    
    /**
     * Search for records matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     */
    public function searchLeads($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'Crm_Model_LeadFilter');
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
     * @param  string $recordData
     * @return array created/updated record
     */
    public function saveLead($recordData)
    {
        return $this->_save($recordData, $this->_controller, 'Lead');        
    }
    
    /**
     * deletes existing records
     *
     * @param string $ids 
     * @return string
     */
    public function deleteLeads($ids)
    {
        $this->_delete($ids, $this->_controller);
    }    

}
