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
        $this->_backend->dropForeignKey('tasks', 'tasks::status_id--tasks_status::id');
        $this->_backend->dropIndex('tasks', 'status_id');
        
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
            'name'    => Calendar_Config::ATTENDEE_STATUS,
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
        
        // update persitent filters
        $stmt = $this->_db->query("SELECT * FROM `" . SQL_TABLE_PREFIX . "filter` WHERE ".
            "`application_id` = '" . $tasksAppId . "' AND ".
            "`model` = 'Tasks_Model_TaskFilter'"
        );
        $pfiltersDatas = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        
        // update persitent filters
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
}
