<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Inventory_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add seq
     * 
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function update_0()
    {
        $declaration = Tinebase_Setup_Update_Release7::getRecordSeqDeclaration();
        try {
            $this->_backend->addCol('inventory_item', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // ignore
        }
        $this->setTableVersion('inventory_item', 3);
        
        Tinebase_Setup_Update_Release7::updateModlogSeq('Inventory_Model_InventoryItem', 'inventory_item');
        
        $this->setApplicationVersion('Inventory', '7.1');
    }
    
    /**
     * update to 7.2
     * 
     * - Delete depreciation and amortization column
     * - Add depreciate_status
     * - Rename add_time to invoice_date
     * - Rename item_added to added_date and item_removed to removed_date
     * - Update ExportDefinitions
     */
    public function update_1()
    {
        if ($this->getTableVersion('inventory_item') != 3) {
            $release6 = new Inventory_Setup_Update_Release6($this->_backend);
            $release6->update_1();
        }
        $this->setApplicationVersion('Inventory', '7.2');
    }

    /**
     * update to 7.3
     * 
     * - Add CSV import feature and ensure that imports does not cause errors
     * - Update import / export definitions
     */
    public function update_2()
    {
        if ($this->getTableVersion('inventory_item') != 4) {
            $release6 = new Inventory_Setup_Update_Release6($this->_backend);
            $release6->update_2();
        }
        
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Inventory'));
        
        $this->setApplicationVersion('Inventory', '7.3');
    }

    /**
     * update to 7.4
     * 
     * - Add note field
     * - Update import / export definitions
     */
    public function update_3()
    {
        if ($this->getTableVersion('inventory_item') != 5) {
            $release6 = new Inventory_Setup_Update_Release6($this->_backend);
            $release6->update_4();
        }
        
        Setup_Controller::getInstance()->createImportExportDefinitions(Tinebase_Application::getInstance()->getApplicationByName('Inventory'));
        
        $this->setApplicationVersion('Inventory', '7.4');
    }
    
    /**
     * Change price field from float to text
     * 
     * #0007706
     */
    public function update_4()
    {
        $field = '
             <field>
                    <name>price</name>
                    <type>text</type>
                    <length>50</length>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration, 'price');
        
        $this->setTableVersion('inventory_item', '7');
        $this->setApplicationVersion('Inventory', '7.5');
    }
    
    /**
     * update to 7.6
     * - create default persistent filter
     * 
     * @return void
     */
    public function update_5()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
            
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Inventory')->getId(),
            'model'             => 'Inventory_Model_InventoryItemFilter',
        );
        
        // default persistent filter for all records
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All Inventory Items", // _("All Inventory Items")
            'description'       => "All existing Inventory Items", // _("All existing Inventory Items")
            'filters'           => array(),
        ))));
        
        $this->setApplicationVersion('Inventory', '7.6');
    }
}
