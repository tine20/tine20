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
     * the models handled by this frontend
     * @var array
     */
    protected $_models = array('InventoryItem');
    
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
     * return autocomplete suggestions for a given property and value
     *
     * @todo have spechial controller/backend fns for this
     * @todo move to abstract json class and have tests
     * @todo make this work
     *
     * @param  string   $property
     * @param  string   $startswith
     * @return array
     */
    
    public function autoCompleteInventoryItemProperty($property, $startswith)
    {
        if (! in_array($property, Inventory_Model_InventoryItem::getValidFields())) {
            // NOTE: it would be better to ask the model for property presece, but we can't atm.
            // NOTE: Maybe we can .. not sure. - Michael
            throw new Tasks_Exception_UnexpectedValue('bad property name');
        }
        
        $filter = new Inventory_Model_InventoryItemFilter(array(
            array('field' => $property, 'operator' => 'startswith', 'value' => $startswith),
        ));
        
        $paging = new Tinebase_Model_Pagination(array('sort' => $property));
        
        $values = array_unique(Inventory_Controller_InventoryItem::getInstance()->search($filter, $paging)->{$property});
        
        $result = array(
            'results'   => array(),
            'totalcount' => count($values)
        );
        
        foreach($values as $value) {
            $result['results'][] = array($property => $value);
        }
        
        return $result;
    }
    
    /**
     * get inventory import definitions
     *
     * @return Tinebase_Record_RecordSet
     *
     * @todo generalize this
     */
    protected function _getImportDefinitions()
    {
        $filter = new Tinebase_Model_ImportExportDefinitionFilter(array(
            array('field' => 'application_id',  'operator' => 'equals', 'value' => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId()),
            array('field' => 'type',            'operator' => 'equals', 'value' => 'import'),
        ));
        
        $importDefinitions = Tinebase_ImportExportDefinition::getInstance()->search($filter);
        
        return $importDefinitions;
    }
    
    /**
     * import inventory items
     *
     * @param string $tempFileId to import
     * @param string $definitionId
     * @param array $importOptions
     * @param array $clientRecordData
     * @return array
     */
    public function importInventoryItems($tempFileId, $definitionId, $importOptions, $clientRecordData = array())
    {
        return $this->_import($tempFileId, $definitionId, $importOptions, $clientRecordData);
    }
    
    /**
     * get default definition
     *
     * @param Tinebase_Record_RecordSet $_importDefinitions
     * @return Tinebase_Model_ImportExportDefinition
     *
     * @todo generalize this
     */
    protected function _getDefaultImportDefinition($_importDefinitions)
    {
        try {
            $defaultDefinition = Tinebase_ImportExportDefinition::getInstance()->getByName('inv_tine_import_csv');
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (count($_importDefinitions) > 0) {
                $defaultDefinition = $_importDefinitions->getFirstRecord();
            } else {
                $defaultDefinition = NULL;
            }
        }
        return $defaultDefinition;
    }
    
    /**
     * Returns registry data of the inventory.
     * @see Tinebase_Application_Json_Abstract
     *
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {
        $definitionConverter = new Tinebase_Convert_ImportExportDefinition_Json();
        $importDefinitions = $this->_getImportDefinitions();
        $defaultDefinition = $this->_getDefaultImportDefinition($importDefinitions);
        
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer('Inventory_Model_InventoryItem', NULL, 'defaultInventoryItemContainer')->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainerArray['id'])->toArray();
        
        
        $registryData = array(
                'defaultInventoryItemContainer' => $defaultContainerArray,
                'defaultImportDefinition'   => $definitionConverter->fromTine20Model($defaultDefinition),
                'importDefinitions'         => array(
                    'results'               => $definitionConverter->fromTine20RecordSet($importDefinitions),
                    'totalcount'            => count($importDefinitions),
                ),
        );
        return $registryData;
    }
}
