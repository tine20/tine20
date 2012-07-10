<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tinebase initialization
 * 
 * @package     Sales
 */
class Sales_Setup_Initialize extends Setup_Initialize
{
    /**
    * init favorites
    */
    protected function _initializeFavorites() {
        // Products
        $commonValues = array(
                            'account_id'        => NULL,
                            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
                            'model'             => 'Sales_Model_ProductFilter',
        );
        
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $pfe->create(new Tinebase_Model_PersistentFilter(
        array_merge($commonValues, array(
                                'name'              => "My Products", // _('My Products')
                                'description'       => "Products created by me", // _('Products created by myself')
                                'filters'           => array(
        array(
                                        'field'     => 'created_by',
                                        'operator'  => 'equals',
                                        'value'     => Tinebase_Model_User::CURRENTACCOUNT
        )
        ),
        ))
        ));
        
        // Contracts
        $commonValues = array(
                                'account_id'        => NULL,
                                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId(),
                                'model'             => 'Sales_Model_ContractFilter',
        );
        
        $pfe->create(new Tinebase_Model_PersistentFilter(
        array_merge($commonValues, array(
                                    'name'              => "My Contracts", // _('My Contracts')
                                    'description'       => "Contracts created by me", // _('Contracts created by myself')
                                    'filters'           => array(
        array(
                                            'field'     => 'created_by',
                                            'operator'  => 'equals',
                                            'value'     => Tinebase_Model_User::CURRENTACCOUNT
        )
        ),
        ))
        ));
    }
    
    /**
     * init key fields
     */
    protected function _initializeKeyFields()
    {
        // create status config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
        
        $salesStatusConfig = array(
            'name'    => Sales_Config::CONTRACT_STATUS,
            'records' => array(
                array('id' => 'OPEN', 'value' => 'open', 'icon' => 'images/oxygen/16x16/places/folder-green.png', 'system' => true),
                array('id' => 'CLOSED', 'value' => 'closed',  'icon' => 'images/oxygen/16x16/places/folder-red.png', 'system' => true),
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sales_Config::CONTRACT_STATUS,
            'value'             => json_encode($salesStatusConfig),
        )));

        $salesClearedConfig = array(
            'name'    => Sales_Config::CONTRACT_CLEARED,
            'records' => array(
                array('id' => 'TO_CLEAR',        'value' => 'to clear',        'icon' => 'images/oxygen/16x16/actions/dialog-warning.png', 'system' => true),
                array('id' => 'NOT_YET_CLEARED', 'value' => 'not yet cleared', 'icon' => 'images/oxygen/16x16/actions/edit-delete.png', 'system' => true),
                array('id' => 'CLEARED',         'value' => 'cleared',         'icon' => 'images/oxygen/16x16/actions/dialog-ok-apply.png', 'system' => true),
                
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sales_Config::CONTRACT_CLEARED,
            'value'             => json_encode($salesClearedConfig),
        )));
    }
}
