<?php
/**
 * Tine 2.0
 * 
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Inventory initialization
 * 
 * @package     Setup
 */
class Inventory_Setup_Initialize extends Setup_Initialize
{
    /**
     * init the default persistentfilters
     */
    protected function _initializeFavorites()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
            
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId(),
            'model'             => 'Inventory_Model_InventoryItemFilter',
        );
        
        // default persistent filter for all records
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All Inventory Items", // _("All Inventory Items")
            'description'       => "All existing Inventory Items", // _("All existing Inventory Items")
            'filters'           => array(),
        ))));
    }
}
