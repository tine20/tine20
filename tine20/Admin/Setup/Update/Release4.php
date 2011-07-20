<?php
/**
 * Tine 2.0
 *
 * @package     Admin
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Admin_Setup_Update_Release4 extends Setup_Update_Abstract
{
    /**
     * update to 5.0
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Admin', '5.0');
    }
}
