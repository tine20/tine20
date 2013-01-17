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
}
