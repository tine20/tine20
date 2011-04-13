<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Phone_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * update from 3.0 -> 4.0
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Phone', '4.0');
    }
}
