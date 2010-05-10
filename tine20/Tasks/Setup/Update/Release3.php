<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

class Tasks_Setup_Update_Release3 extends Setup_Update_Abstract
{
    /**
     * migragte from class_id (not used) to class
     */
    public function update_0()
    {
        // we need to drop the foreign key first
        try {
            $this->_backend->dropForeignKey('tasks', 'tasks::class_id--class::id');
        } catch (Zend_Db_Statement_Exception $zdse) {
            // try it again with table prefix
            try {
                $this->_backend->dropForeignKey('tasks', SQL_TABLE_PREFIX . 'tasks::class_id--class::id');
            } catch (Zend_Db_Statement_Exception $zdse) {
                // already dropped
            }
        }
        
        $this->_backend->dropCol('tasks', 'class_id');
        $this->_backend->dropTable('class');
        
        $declaration = new Setup_Backend_Schema_Field_Xml('
            <field>
                <name>class</name>
                <type>text</type>
                <length>40</length>
                <default>PUBLIC</default>
                <notnull>true</notnull>
            </field>');
        $this->_backend->addCol('tasks', $declaration, 11);
        
        $declaration = new Setup_Backend_Schema_Index_Xml('
            <index>
                <name>class</name>
                <field>
                    <name>class</name>
                </field>
            </index>');
        $this->_backend->addIndex('tasks', $declaration);
        
        $this->setTableVersion('tasks', 3);
        $this->setApplicationVersion('Tasks', '3.1');
    }
    
    /**
     * create default persistent filters
     */
    public function update_1()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $myEventsPFilter = $pfe->create(new Tinebase_Model_PersistentFilter(array(
            'name'              => Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All my tasks", // _("All my tasks")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
            'filters'           => array(),
        )));
        
        $this->setApplicationVersion('Tasks', '3.2');
    }
}
