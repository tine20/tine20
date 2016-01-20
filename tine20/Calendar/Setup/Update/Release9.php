<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Calendar_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 9.1
     * - identify base event via new base_event_id field instead of UID
     */
    public function update_0()
    {
        if ($this->getTableVersion('cal_events') < 10) {
            $release8 = new Calendar_Setup_Update_Release8($this->_backend);
            $release8->update_8();
        }
        $this->setApplicationVersion('Calendar', '9.1');
    }

    /**
     * update to 9.2
     *
     * @see 0011266: increase size of event fields summary and location
     */
    public function update_1()
    {
        if ($this->getTableVersion('cal_events') < 11) {
            $release8 = new Calendar_Setup_Update_Release8($this->_backend);
            $release8->update_9();
        }
        $this->setApplicationVersion('Calendar', '9.2');
    }

    /**
     * update to 9.3
     *
     * @see 0011312: Make resource notification handling and default status configurable
     */
    public function update_2()
    {
        if ($this->getTableVersion('cal_resources') < 3) {
            $release8 = new Calendar_Setup_Update_Release8($this->_backend);
            $release8->update_10();
        }
        $this->setApplicationVersion('Calendar', '9.3');
    }

    /**
     * force activesync calendar resync for iOS devices
     */
    public function update_3()
    {
        $release8 = new Calendar_Setup_Update_Release8($this->_backend);
        $release8->update_11();
        $this->setApplicationVersion('Calendar', '9.4');
    }
}
