<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */
class Tasks_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     */
    public function update_0()
    {
        $this->updateKeyFieldIcon(Tasks_Config::getInstance(), Tasks_Config::TASK_STATUS);
        $this->updateKeyFieldIcon(Tasks_Config::getInstance(), Tasks_Config::TASK_PRIORITY);

        $this->setApplicationVersion('Tasks', '11.1');
    }

    public function update_1()
    {
        $this->setApplicationVersion('Tasks', '12.0');
    }
}
