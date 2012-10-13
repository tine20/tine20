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

/**
 * Inventory updates for version 0.x
 *
 * @package     Inventory
 * @subpackage  Setup
 */
class Inventory_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update to 6.0
     */
    public function update_1()
    {
        $this->setApplicationVersion('Inventory', '6.0');
    }
}
