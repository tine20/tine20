<?php
/**
 * Tine 2.0
 * @package     Inventory
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *
 * This class handles all Json requests for the Inventory application
 *
 * @package     Inventory
 * @subpackage  Frontend
 */
class Inventory_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var Inventory_Controller_InventoryItem
     */
    protected $_controller = NULL;
    
    /**
     * user fields (created_by, ...) to resolve in _multipleRecordsToJson and _recordToJson
     *
     * @var array
     */
    protected $_resolveUserFields = array(
        'Inventory_Model_InventoryItem' => array('created_by', 'last_modified_by')
    );
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Inventory';
        $this->_controller = Inventory_Controller_InventoryItem::getInstance();
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchInventoryItems($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'Inventory_Model_InventoryItemFilter', TRUE);
    }     
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getInventoryItem($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveInventoryItem($recordData)
    {
        return $this->_save($recordData, $this->_controller, 'InventoryItem');        
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids 
     * @return string
     */
    public function deleteInventoryItems($ids)
    {
        return $this->_delete($ids, $this->_controller);
    }    

    /**
     * Returns registry data
     * 
     * @return array
     */
    public function getRegistryData()
    {   
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer(Tinebase_Core::getUser()->getId(), $this->_applicationName)->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainerArray['id'])->toArray();
        
        return array(
            'defaultContainer' => $defaultContainerArray
        );
    }
}
