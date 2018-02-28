<?php
/**
 * Tine 2.0
 * @package     Inventory
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_applicationName = 'Inventory';
    
    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = array('InventoryItem');
    
    /**
     * user fields (created_by, ...) to resolve in _multipleRecordsToJson and _recordToJson
     *
     * @var array
     */
    protected $_resolveUserFields = array(
        'Inventory_Model_InventoryItem' => array('created_by', 'last_modified_by')
    );

    /**
     * default import name
     *
     * @var string
     */
    protected $_defaultImportDefinitionName = 'inv_tine_import_csv';

    /**
     * Returns registry data of the inventory.
     * @see Tinebase_Application_Json_Abstract
     *
     * @return mixed array 'variable name' => 'data'
     *
     * @todo generalize
     */
    public function getRegistryData()
    {
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer('Inventory_Model_InventoryItem', NULL, 'defaultInventoryItemContainer')->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Tinebase_Core::getUser(), $defaultContainerArray['id'])->toArray();

        $registryData = array(
                'defaultInventoryItemContainer' => $defaultContainerArray,
        );
        $registryData = array_merge($registryData, $this->_getImportDefinitionRegistryData());
        return $registryData;
    }
}
