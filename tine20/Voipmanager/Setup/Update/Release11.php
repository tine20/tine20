<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2013-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

echo __FILE__ . ' must not be used or autoloaded or required etc.' . PHP_EOL;
exit(1);

class Voipmanager_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 12.0
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Voipmanager', '12.0');
    }
}
