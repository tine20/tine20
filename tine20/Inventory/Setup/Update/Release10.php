<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);

class Inventory_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Inventory', '10.1');
    }

    /**
     * update to 10.2
     *
     * - convert is_deleted to smallint
     * - convert deprecated_status to smallint
     * - create price (float)
     *
     * @see 0012182: item price is not saved
     *
     * @return void
     */
    public function update_1()
    {
        // convert price column to double for pgsql as this can't be done with the doctrine schema tool
        // ERROR:  column "price" cannot be cast automatically to type double precision
        if ($this->_db instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            $field = '
             <field>
                    <name>price</name>
                    <type>float</type>
                </field>';

            $declaration = new Setup_Backend_Schema_Field_Xml($field);
            $this->_backend->alterCol('inventory_item', $declaration);

            // this is needed to make doctrine work correctly after alter column
            $this->_db->commit();
            $this->_db->beginTransaction();
        }

        // update according to current modelconfigV2 definition using doctrine2
        // NOTE: depending on update action you might need to move this to a later update
        //       as your update case might be gone after this got executed in an previous
        //      (this) update
        $this->updateSchema('Inventory', array('Inventory_Model_InventoryItem'));

        $this->setApplicationVersion('Inventory', '10.2');
    }

    /**
     * update to 10.3
     *
     * - recreate import export definitions as field changed from costcentre to costcenter
     *
     * @return void
     */
    public function update_2()
    {
        $this->updateSchema('Inventory', array('Inventory_Model_InventoryItem'));

        $app = Tinebase_Application::getInstance()->getApplicationByName('Inventory');
        Setup_Controller::getInstance()->createImportExportDefinitions($app);

        $this->setApplicationVersion('Inventory', '10.3');
    }

    /**
     * update to 10.4
     *
     * change container_id to uuid
     *
     * @return void
     */
    public function update_3()
    {
        $this->updateSchema('Inventory', array('Inventory_Model_InventoryItem'));

        $this->setApplicationVersion('Inventory', '10.4');
    }

    /**
     * update to 10.5
     *
     * add unique constraint on name + deleted_time
     *
     * @return void
     */
    public function update_4()
    {
        $this->updateSchema('Inventory', array('Inventory_Model_InventoryItem'));

        $this->setApplicationVersion('Inventory', '10.5');
    }

    /**
     * update to 11.0
     *
     * @return void
     */
    public function update_5()
    {
        $this->setApplicationVersion('Inventory', '11.0');
    }
}
