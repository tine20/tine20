<?php
/**
 * Tine 2.0
 *
 * @package     Events
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Events_Setup_Update_Release1 extends Setup_Update_Abstract
{
    /**
     * update to 10.0
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Events', '10.0');
    }
}
