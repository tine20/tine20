<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

class Inventory_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update to 0.3
     * @return void
     */
    public function update_1()
    {
        $field = '<field>
                    <name>invoice</name>
                    <type>text</type>
                    <length>255</length>
                </field>
                <field>
                    <name>price</name>
                    <type>float</type>
                </field>
                <field>
                    <name>costcentre</name>
                    <type>text</type>
                    <length>255</length>
                </field>
                <field>
                    <name>warranty</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>item_added</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>item_removed</name>
                    <type>datetime</type>
                </field>
                <field>
                    <name>depreciation</name>
                    <type>float</type>
                </field>
                <field>
                    <name>amortization</name>
                    <type>float</type>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration);
        
        $this->setApplicationVersion('Inventory', '0.3');
        $this->setTableVersion('inventory_item', '3');
    }
}
