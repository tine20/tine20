<?php
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Sales_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update from 5.0 -> 5.1
     * - save shared contracts container id in config
     * 
     * @return void
     */
    public function update_0()
    {
        try {
            $sharedContractsId = Tinebase_Config::getInstance()->getConfig(Sales_Model_Config::SHAREDCONTRACTSID, $appId)->value;
            $sharedContracts = Tinebase_Container::getInstance()->get($sharedContractsId ? $sharedContractsId : 1);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // try to fetch default shared container
            $filter = new Tinebase_Model_ContainerFilter(array(
                array('field' => 'application_id', 'operator' => 'equals',
                    'value' => Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId()),
                array('field' => 'name', 'operator' => 'equals', 'value' => 'Shared Contracts'),
                array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_SHARED),
            ));
            
            $sharedContracts = Tinebase_Container::getInstance()->search($filter)->getFirstRecord();
            if ($sharedContracts) {
                Tinebase_Config::getInstance()->setConfigForApplication(Sales_Model_Config::SHAREDCONTRACTSID, $sharedContracts->getId(), 'Sales');
            }
        }
        
        $this->setApplicationVersion('Sales', '5.1');
    }    
    
    /**
     * update from 5.1 -> 5.2
     * - default contracts & products
     * 
     * @return void
     */
    public function update_1() {
        
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
        
        $this->setApplicationVersion('Sales', '5.2');
    }
}
