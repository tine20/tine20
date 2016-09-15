<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */
class Tasks_Setup_Update_Release9 extends Setup_Update_Abstract
{
    /**
     * update to 10.0
     *
     * @return void
     */
    public function update_0()
    {
        $this->setApplicationVersion('Tasks', '10.0');
    }
}
