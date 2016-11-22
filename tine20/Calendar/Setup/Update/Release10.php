<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1
     * - Update Calendar Import Export definitions
     */
    public function update_0()
    {
        $release9 = new Calendar_Setup_Update_Release9($this->_backend);
        $release9->update_7();
        $this->setApplicationVersion('Calendar', '10.1');
    }
}
