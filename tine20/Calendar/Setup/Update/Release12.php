<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release12 extends Setup_Update_Abstract
{
    /**
     * update to 12.1
     * - run update 11.7 & 11.8
     */
    public function update_0()
    {
        $release11 = new Calendar_Setup_Update_Release8($this->_backend);
        $release11->update_6();
        $release11->update_7();
        $this->setApplicationVersion('Calendar', '12.1');
    }
}
