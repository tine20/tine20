<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
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

    /**
     * update to 11.2
     *
     * change priority default value
     */
    public function update_1()
    {
        $this->_backend->alterCol('tasks', new Setup_Backend_Schema_Field_Xml(
            '<field>
                    <name>priority</name>
                    <type>text</type>
                    <length>40</length>
                    <default>200</default>
                    <notnull>true</notnull>
                </field>'
        ));

        if ($this->getTableVersion('tasks') < 11) {
            $this->setTableVersion('tasks', 11);
        }

        $this->setApplicationVersion('Tasks', '11.2');
    }

    /**
     * update to 11.3
     *
     * alter priority values in database
     */
    public function update_2()
    {
        foreach (Tasks_Model_Priority::$upperStringMapping as $key => $val) {
            $this->_db->update(SQL_TABLE_PREFIX . 'tasks', ['priority' => $val],
                $this->_db->quoteIdentifier('priority') . ' = "' . $key . '"');
            $this->_db->update(SQL_TABLE_PREFIX . 'tasks', ['priority' => $val],
                $this->_db->quoteIdentifier('priority') . ' = "' . strtolower($key) . '"');
        }

        $this->setApplicationVersion('Tasks', '11.3');
    }
}