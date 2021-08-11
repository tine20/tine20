<?php
/**
 * Tine 2.0
 *
 * @package     Projects
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

class Projects_Setup_Update_Release11 extends Setup_Update_Abstract
{
    public function update_0()
    {
        $this->setApplicationVersion('Projects', '12.0');
    }
}
