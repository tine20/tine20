<?php
/**
 * Tine 2.0
 *
 * @package     RequestTracker
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

class RequestTracker_Setup_Update_Release0 extends Setup_Update_Abstract
{
    /**
     * update to 2.0
     * @return void
     */
    public function update_1()
    {
        $this->setApplicationVersion('RequestTracker', '2.0');
    }
}
