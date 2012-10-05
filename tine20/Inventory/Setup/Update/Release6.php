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
}
