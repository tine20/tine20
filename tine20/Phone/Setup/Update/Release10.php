<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);


class Phone_Setup_Update_Release10 extends Setup_Update_Abstract
{
    /**
     * update to 10.1: removed original update script -> moved to 10.2
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Phone', '10.1');
    }

    /**
     * update to 10.2: adds contact_id col
     *
     * @return void
     */
    public function update_1()
    {
        $update9 = new Phone_Setup_Update_Release9($this->_backend);
        $update9->update_2();
        $this->setApplicationVersion('Phone', '10.2');
    }

    /**
     * update to 11.0
     *
     * @return void
     */
    public function update_2()
    {
        $this->setApplicationVersion('Phone', '11.0');
    }
}
