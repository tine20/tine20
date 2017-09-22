<?php
/**
 * Tine 2.0
 *
 * @package     Inventory
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Inventory_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     *
     * @return void
     */
    public function update_0()
    {
        $this->updateSchema('Inventory', array('Inventory_Model_InventoryItem'));
        $this->setApplicationVersion('Inventory', '11.1');
    }
}
