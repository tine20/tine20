<?php
/**
 * Tine 2.0
 *
 * @package     CoreData
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

class CoreData_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update to 10.0
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('CoreData', '10.0');
    }
}
