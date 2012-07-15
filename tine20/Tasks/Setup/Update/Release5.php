<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

class Tasks_Setup_Update_Release5 extends Setup_Update_Abstract
{
    /**
     * update to 5.1
     *  - move task status to key field config
     */
    public function update_0()
    {
        $tasksAppId = Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId();
        
        // remove status_id keys
        try {
            $this->_backend->dropForeignKey('tasks', 'tasks::status_id--tasks_status::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // do nothing (fk not found)
        }
    
        try {
            $this->_backend->dropIndex('tasks', 'status_id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // do nothing (fk not found)
        }
        
        // need to replace all NULL values first
        $this->_db->update(SQL_TABLE_PREFIX . 'tasks', array(
            'status_id' => 1,
        ), "`status_id` IS NULL");

        // alter status_id -> status
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>status</name>
                <type>text</type>
                <length>40</length>
                <default>NEEDS-ACTION</default>
                <notnull>true</notnull>
            </field>');
        
        $this->_backend->alterCol('tasks', $declaration, 'status_id');
        
        // get all current status datas and drop old status table
        $stmt = $this->_db->query("SELECT * FROM `" . SQL_TABLE_PREFIX . "tasks_status`");
        $statusDatas = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $this->_backend->dropTable('tasks_status', $tasksAppId);
        
        // update task table
        $statusMap = array(); // oldId => newId
        foreach ($statusDatas as $statusData) {
            $statusMap[$statusData['id']] = $statusData['status_name'];
            $this->_db->update(SQL_TABLE_PREFIX . 'tasks', array(
                'status' => $statusData['status_name'],
            ), "`status` = '{$statusData['id']}'");
        }
        
        // create status config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        
        $tasksStatusConfig = array(
            'name'    => Tasks_Config::TASK_STATUS,
            'records' => array(
                array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true), //_('No response')
                array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Completed')
                array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Cancelled')
                array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true), //_('In process')
            ),
        );
        
        // add non system custom status
        foreach ($statusDatas as $statusData) {
            if (! in_array($statusData['status_name'], array('NEEDS-ACTION', 'COMPLETED', 'CANCELLED', 'IN-PROCESS'))) {
                $tasksStatusConfig['records'][] = array('id' => $statusData['status_name'], 'value' => $statusData['status_name'], 'is_open' => $statusData['status_is_open'], 'icon' => $statusData['status_icon']);
            }
        }
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $tasksAppId,
            'name'              => Tasks_Config::TASK_STATUS,
            'value'             => json_encode($tasksStatusConfig),
        )));
        
        // update persistent filters
        $stmt = $this->_db->query("SELECT * FROM `" . SQL_TABLE_PREFIX . "filter` WHERE ".
            "`application_id` = '" . $tasksAppId . "' AND ".
            "`model` = 'Tasks_Model_TaskFilter'"
        );
        $pfiltersDatas = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach($pfiltersDatas as $pfilterData) {
            $filtersData = Zend_Json::decode($pfilterData['filters']);
            foreach($filtersData as &$filterData) {
                if (array_key_exists('field', $filterData) && $filterData['field'] == 'status_id') {
                    $filterData['field'] = 'status';
                    $newStatusIds = array();
                    foreach((array) $filterData['value'] as $oldStatusId) {
                        $newStatusIds[] = $statusMap[$oldStatusId];
                    }
                    $filterData['value'] = is_array($filterData['value']) ? $newStatusIds : $newStatusIds[0];
                    
            
                    Tinebase_Core::getLogger()->ERR(print_r($filterData, TRUE));
                }
            }
            $this->_db->update(SQL_TABLE_PREFIX . 'filter', array(
                'filters' => Zend_Json::encode($filtersData),
            ), "`id` LIKE '{$pfilterData['id']}'");
        }
        
        $this->setTableVersion('tasks', '4');
        $this->setApplicationVersion('Tasks', '5.1');
    }

    /**
     * update to 5.2
     *  - remove task status table from application tables (this was not done by dropTable until now)
     */
    public function update_1()
    {
        Tinebase_Application::getInstance()->removeApplicationTable(Tinebase_Application::getInstance()->getApplicationByName('Tasks'), 'tasks_status');
        $this->setApplicationVersion('Tasks', '5.2');
    }

    /**
     * update to 5.3
     *  - move task priority to key field config
     */
    public function update_2()
    {
        $tasksAppId = Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId();

        // need to replace all NULL values first
        $this->_db->update(SQL_TABLE_PREFIX . 'tasks', array(
            'priority' => 1,
        ), "`priority` IS NULL");
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>priority</name>
                <type>text</type>
                <length>40</length>
                <default>NORMAL</default>
                <notnull>true</notnull>
            </field>');
        
        $this->_backend->alterCol('tasks', $declaration);
        
        // create status config
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        
        $tasksPriorityConfig = array(
            'name'    => Tasks_Config::TASK_PRIORITY,
            'records' => array(
                array('id' => 'LOW',    'value' => 'low',      'icon' => 'images/oxygen/16x16/actions/go-down.png', 'system' => true), //_('low')
                array('id' => 'NORMAL', 'value' => 'normal',   'icon' => 'images/oxygen/16x16/actions/go-next.png', 'system' => true), //_('normal')
                array('id' => 'HIGH',   'value' => 'high',     'icon' => 'images/oxygen/16x16/actions/go-up.png',   'system' => true), //_('high')
                array('id' => 'URGENT', 'value' => 'urgent',   'icon' => 'images/oxygen/16x16/emblems/emblem-important.png', 'system' => true), //_('urgent')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => $tasksAppId,
            'name'              => Tasks_Config::TASK_PRIORITY,
            'value'             => json_encode($tasksPriorityConfig),
        )));

        // update task table
        foreach ($tasksPriorityConfig['records'] as $index => $prioData) {
            $this->_db->update(SQL_TABLE_PREFIX . 'tasks', array(
                'priority' => $prioData['id'],
            ), "`priority` = '{$index}'");
        }
        
        $this->setTableVersion('tasks', '5');
        $this->setApplicationVersion('Tasks', '5.3');
    }
    
    /**
     * update to 5.4
     * - status_id might be set in state as sort column
     */
    public function update_3()
    {
        $stmt = $this->_db->query("SELECT * FROM `" . SQL_TABLE_PREFIX . "state`");
        $stateDatas = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        foreach ($stateDatas as $state) {
            $this->_db->update(SQL_TABLE_PREFIX . 'state', array(
                'data' => str_replace('sort%3Do%253Afield%253Ds%25253Astatus_id', 'sort%3Do%253Afield%253Ds%25253Astatus', $state['data'])
            ), "`id` = '{$state['id']}'");
        }
        
        $this->setApplicationVersion('Tasks', '5.4');
    }
    
    /**
    * update to 6.0
    *
    * @return void
    */
    public function update_4()
    {
        $this->setApplicationVersion('Tasks', '6.0');
    }
}
