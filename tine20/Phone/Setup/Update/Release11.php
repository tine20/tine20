<?php
/**
 * Tine 2.0
 *
 * @package     Phone
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2016-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);

class Phone_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 12.0
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Phone', '12.0');
    }
}
