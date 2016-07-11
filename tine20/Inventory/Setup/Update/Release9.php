<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
class Inventory_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 10.0
     *
     * @return void
     */
    public function update_0()
    {
        // update according to current modelconfigV2 definition using doctrine2
        // NOTE: depending on update action you might need to move this to a later update
        //       as your update case might be gone ofter this got executetd in an previoud (this) update
        $this->updateSchema('Inventory', array('Inventory_Model_InventoryItem'));

        $this->setApplicationVersion('Inventory', '10.0');
    }
}
