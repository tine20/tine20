<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

class Tasks_Setup_Update_Release4 extends Setup_Update_Abstract
{
    /**
     * rename default favorite
     */
    public function update_0()
    {
        // rename persistent filter
        $this->_db->query('UPDATE ' . SQL_TABLE_PREFIX . "filter SET name = 'All tasks for me' WHERE description = 'All tasks that I am responsible for'");

        $this->setApplicationVersion('Tasks', '4.1');
    }

    /**
     * update to 5.0
     */
    public function update_1()
    {
        $this->setApplicationVersion('Tasks', '5.0');
    }
}
