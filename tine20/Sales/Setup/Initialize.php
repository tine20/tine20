<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Jonas Fischer <j.fischer@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $commonValues['model'] = 'Sales_Model_ContractFilter';
        
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
        
        // Customers
        $commonValues['model'] = 'Sales_Model_CustomerFilter';
        
        $pfe->create(new Tinebase_Model_PersistentFilter(
            array_merge($commonValues, array(
                'name'        => "All Customers", // _('All Customers')
                'description' => "All customer records", // _('All customer records')
                'filters'     => array(
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
        // create type config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config',
            'tableName' => 'config',
        ));
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
    
        $tc = array(
            'name'    => Sales_Config::INVOICE_TYPE,
            'records' => array(
                array('id' => 'INVOICE',  'value' => 'invoice',   'system' => true), // _('invoice')
                array('id' => 'REVERSAL', 'value' => 'reversal',  'system' => true), // _('reversal')
                array('id' => 'CREDIT',   'value' => 'credit',    'system' => true) // _('credit')
            ),
        );
    
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sales_Config::INVOICE_TYPE,
            'value'             => json_encode($tc),
        )));
    
        // create cleared state keyfields
        $tc = array(
            'name'    => Sales_Config::INVOICE_CLEARED,
            'records' => array(
                array('id' => 'TO_CLEAR',  'value' => 'to clear',   'system' => true), // _('to clear')
                array('id' => 'CLEARED', 'value' => 'cleared',  'system' => true), // _('cleared')
            ),
        );
    
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sales_Config::INVOICE_CLEARED,
            'value'             => json_encode($tc),
        )));
    }
}
