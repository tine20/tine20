<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

class Inventory_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update to 0.2
     * @return void
     */
    public function update_1()
    {
        $field = '<field>
                    <name>location</name>
                    <type>text</type>
                    <length>255</length>
                </field>';
        
        $declaration = new Setup_Backend_Schema_Field_Xml($field);
        $this->_backend->alterCol('inventory_item', $declaration);
        
        $this->setApplicationVersion('Inventory', '0.2');
        $this->setTableVersion('inventory_item', '2');
    }
}
