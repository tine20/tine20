<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

/**
 * Inventory updates for version 6.x
 *
 * @package     Inventory
 * @subpackage  Setup
 */
class Inventory_Setup_Update_Release6 extends Setup_Update_Abstract
{
    /**
     * Rename old keyfield type to status
     */
    public function update_0()
    {
        $field = '<field>
                    <name>status</name>
                    <type>text</type>
                    <length>40</length>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration, 'type');
        
        $this->setApplicationVersion('Inventory', '6.1');
        $this->setTableVersion('inventory_item', '2');
    }
    
     /**
     * Delete depreciation and amortization column
     * Add depreciate_status
     * Rename add_time to invoice_date
     * Rename item_added to added_date and item_removed to removed_date
     * Update ExportDefinitions
     */
    public function update_1()
    {
        $this->_backend->dropCol('inventory_item', 'depreciation');
        $this->_backend->dropCol('inventory_item', 'amortization');
    
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>depreciate_status</name>
                <type>boolean</type>
                <default>false</default>
            </field>
        ');
        $this->_backend->addCol('inventory_item', $declaration, 16);
    
        $field = '
            <field>
                <name>invoice_date</name>
                <type>datetime</type>
                <notnull>false</notnull>
            </field>';
    
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration, 'add_time');
    
        $field = '
            <field>
                <name>added_date</name>
                <type>datetime</type>
            </field>';
    
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration, 'item_added');
    
        $field = '
            <field>
                <name>removed_date</name>
                <type>datetime</type>
            </field>';
    
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration, 'item_removed');
        
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Inventory'));
        
        $this->setApplicationVersion('Inventory', '6.2');
        $this->setTableVersion('inventory_item', '3');
    }
    
    /**
     * Add CSV import feature and ensure that imports does not cause errors
     */
    public function update_2()
    {
        $field = '
             <field>
                <name>description</name>
                <type>text</type>
                <notnull>false</notnull>
             </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration, 'description');

        $field = '
            <field>
                <name>name</name>
                <type>text</type>
                <length>250</length>
                <notnull>true</notnull>
            </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration, 'name');
        
        $this->setApplicationVersion('Inventory', '6.3');
        $this->setTableVersion('inventory_item', '4');
    }
    
    /**
     * Update import / export definitions
     */
    public function update_3()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Inventory'));
    
        $this->setApplicationVersion('Inventory', '6.4');
    }
    
    /**
     * Update import / export definitions
     * Add note field
     */
    public function update_4()
    {
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Inventory'));
        
        $field = '<field>
                    <name>adt_info</name>
                    <type>text</type>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->addCol('inventory_item', $declaration);
        
        $this->setApplicationVersion('Inventory', '6.5');
        $this->setTableVersion('inventory_item', '5');
    }
    
    /**
     * update to 7.0
     * 
     * @return void
     */
    public function update_5()
    {
        $this->setApplicationVersion('Inventory', '7.0');
    }
}
