<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

class Admin_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 11.0
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Admin', '11.0');
    }
}
