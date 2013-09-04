<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */
class Tasks_Setup_Update_Release7 extends Setup_Update_Abstract
{
    /**
     * update to 7.1
     * - add seq
     * 
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function update_0()
    {
        $declaration = Tinebase_Setup_Update_Release7::getRecordSeqDeclaration();
        try {
            $this->_backend->addCol('tasks', $declaration);
        } catch (Zend_Db_Statement_Exception $zdse) {
            // ignore
        }
        $this->setTableVersion('tasks', 6);
        
        Tinebase_Setup_Update_Release7::updateModlogSeq('Tasks_Model_Task', 'tasks');
        
        $this->setApplicationVersion('Tasks', '7.1');
    }
    
    /**
     * update to 7.2
     * - add uid field
     */
    public function update_1()
    {
        $this->validateTableVersion('tasks', 6);
        
        // first add with notnull == false ...
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>uid</name>
                <type>text</type>
                <length>255</length>
                <notnull>false</notnull>
            </field>
        ');
        $this->_backend->addCol('tasks', $declaration);
        
        $tasksBackend = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tasks_Model_Task', 
            'tableName' => 'tasks',
        ));
        
        $allTasks = $tasksBackend->getAll();
        
        // add uid to all tasks
        foreach ($allTasks as $task) {
            $task->uid = $task->id;
            
            $tasksBackend->update($task);
        }
        
        // ... now set notnull to true
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>uid</name>
                <type>text</type>
                <length>255</length>
                <notnull>true</notnull>
            </field>
        ');
        $this->_backend->alterCol('tasks', $declaration);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>uid--id</name>
                <field>
                    <name>uid</name>
                </field>
                <field>
                    <name>id</name>
                </field>
            </index>
        ');
        $this->_backend->addIndex('tasks', $declaration);
        
        $this->setTableVersion('tasks', 7);
        
        $this->setApplicationVersion('Tasks', '7.2');
    }
}
