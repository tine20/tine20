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
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
        try {
            $sharedContractsId = Sales_Config::getInstance()->get(Sales_Model_Config::SHAREDCONTRACTSID);
            $sharedContracts = Tinebase_Container::getInstance()->get($sharedContractsId ? $sharedContractsId : 1);
        } catch (Tinebase_Exception_NotFound $tenf) {
            // try to fetch default shared container
            $filter = new Tinebase_Model_ContainerFilter(array(
                array('field' => 'application_id', 'operator' => 'equals',
                    'value' => $appId),
                array('field' => 'name', 'operator' => 'equals', 'value' => 'Shared Contracts'),
                array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Container::TYPE_SHARED),
            ));
            
            $sharedContracts = Tinebase_Container::getInstance()->search($filter)->getFirstRecord();
            if ($sharedContracts) {
                Sales_Config::getInstance()->set(Sales_Model_Config::SHAREDCONTRACTSID, $sharedContracts->getId());
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
    
    /**
     * update from 5.2 -> 5.3
     * - default contracts & products
     * 
     * @return void
     */
    public function update_2() {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cleared_in</name>
                <type>text</type>
                <length>256</length>
                <notnull>false</notnull>
            </field>'
        );
        $this->_backend->addCol('sales_contracts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cleared</name>
                <type>boolean</type>
                <default>false</default>
            </field>'
        );
        $this->_backend->addCol('sales_contracts', $declaration);
        
        $this->setTableVersion('sales_contracts', 2);
        $this->setApplicationVersion('Sales', '5.3');
    }
    
    /**
     * update from 5.3 -> 5.4
     * - change number type to text
     * 
     * @return void
     */    
    public function update_3()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>number</name>
                <type>text</type>
                <length>64</length>
                <notnull>true</notnull>
            </field>'
        );
        
        $this->_backend->alterCol('sales_contracts', $declaration);
        $this->setTableVersion('sales_contracts', 3);
        $this->setApplicationVersion('Sales', '5.4');
    }
    
    /**
     * update from 5.4 -> 5.5
     * - set cleared to text
     * - change default values for cleared, status 
     * 
     * @return void
     */
    public function update_4()
    {
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>cleared</name>
                <type>text</type>
                <length>64</length>
                <default>not yet cleared</default>
            </field>'
        );
        
        $this->_backend->alterCol('sales_contracts', $declaration);
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>64</length>
                <default>open</default>
            </field>');
        
        $this->_backend->alterCol('sales_contracts', $declaration);
        
        $this->setTableVersion('sales_contracts', 4);
        
        // transfer cleared value
        $be = new Sales_Backend_Contract();
        
        $filter = new Sales_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text('cleared', 'equals', '0'));
        $results = $be->search($filter, null, false, true);
        $be->updateMultiple($results, array('cleared' => 'NOTCLEARED'));

        $filter = new Sales_Model_ContractFilter(array(), 'AND');
        $filter->addFilter(new Tinebase_Model_Filter_Text('cleared', 'equals', '1'));
        $results = $be->search($filter, null, false, true);
        $be->updateMultiple($results, array('cleared' => 'CLEARED'));
        
        // keyfieldconfigs
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
                array('id' => 'TOCLEAR',    'value' => 'to clear',        'icon' => 'images/oxygen/16x16/actions/dialog-warning.png', 'system' => true),
                array('id' => 'NOTCLEARED', 'value' => 'not yet cleared', 'icon' => 'images/oxygen/16x16/actions/edit-delete.png', 'system' => true),
                array('id' => 'CLEARED',    'value' => 'cleared',         'icon' => 'images/oxygen/16x16/actions/dialog-ok-apply.png', 'system' => true),
                
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $appId,
            'name'              => Sales_Config::CONTRACT_CLEARED,
            'value'             => json_encode($salesClearedConfig),
        )));
        
        
        $this->setApplicationVersion('Sales', '5.5');
    }

    /**
     * update from 5.5 -> 5.6
     * - added table cost_centers
     * 
     * @return void
     */
    public function update_5()
    {
        $tableDefinition = '
            <table>
            <name>sales_cost_centers</name>
            <version>1</version>
            <declaration>
                <field>
                    <name>id</name>
                    <type>text</type>
                    <length>40</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>number</name>
                    <type>text</type>
                    <length>64</length>
                    <notnull>true</notnull>
                </field>
                <field>
                    <name>remark</name>
                    <type>text</type>
                    <length>255</length>
                    <notnull>false</notnull>
                </field>
                <index>
                    <name>id</name>
                    <primary>true</primary>
                    <field>
                        <name>id</name>
                    </field>
                </index>
                <index>
                    <name>number</name>
                    <unique>true</unique>
                    <field>
                        <name>number</name>
                    </field>
                </index>
            </declaration>
        </table>
        ';
        $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableDefinition);
        $this->_backend->createTable($table, 'Sales', 'sales_cost_centers');
        $this->setApplicationVersion('Sales', '5.6');
    }

    /*
     * update to 6.0
     *
     * @return void
     */
    public function update_6()
    {
        $this->setApplicationVersion('Sales', '6.0');
    }
}
