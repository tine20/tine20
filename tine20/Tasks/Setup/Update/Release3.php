<?php
/**
 * Tine 2.0
 *
 * @package     Tasks
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
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
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $myEventsPFilter = $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array(
            'name'              => Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All tasks of my taskslists", // _("All tasks of my taskslists")
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
            'filters'           => array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT),
             )
        )));
        
        $this->setApplicationVersion('Tasks', '3.2');
    }
    
    /**
     * add status to default persistent filter
     */
    public function update_2()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        $defaultFavorite = $pfe->getByProperty(Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME, 'name');
        $defaultFavorite->bypassFilters = TRUE;
        
        $closedIds = $this->_getClosedStatusIds();
        $defaultFavorite->filters = array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT),
            array('field' => 'status_id',    'operator' => 'notin',  'value' => $closedIds),
        );
        $pfe->update($defaultFavorite);
        $this->setApplicationVersion('Tasks', '3.3');
    }
    
    /**
     * get closed status ids
     * 
     * @return array
     */
    protected function _getClosedStatusIds()
    {
        $stmt = $this->_db->query("SELECT id FROM `" . SQL_TABLE_PREFIX . "tasks_status` where status_is_open = 0");
        $statusDatas = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        $closedIds = array();
        foreach ($statusDatas as $status) {
            $closedIds[] = $status['id'];
        }
        
        return $closedIds;
    }
    
    /**
     * add more default favorites
     */
    public function update_3()
    {
        $pfe = Tinebase_PersistentFilter::getInstance();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
        );
        
        $closedIds = $this->_getClosedStatusIds();
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My open tasks",
            'description'       => "My open tasks",
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
                array('field' => 'status_id',    'operator' => 'notin',  'value' => $closedIds),
            )
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My open tasks this week",
            'description'       => "My open tasks this week",
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
                array('field' => 'due',          'operator' => 'within', 'value' => 'weekThis'),
                array('field' => 'status_id',    'operator' => 'notin',  'value' => $closedIds),
            )
        ))));

        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All tasks for me",
            'description'       => "All tasks that I am responsible for",
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
            )
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me",
            'description'       => "All tasks that I have last modified",
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));
        
        $pfe->createDuringSetup(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Tasks without responsible",
            'description'       => "Tasks without responsible",
            'filters'           => array(array(
                'field'     => 'organizer',
                'operator'  => 'equals',
                'value'     => '',
            )),
        ))));
        
        $this->setApplicationVersion('Tasks', '3.4');
    }
    
    /**
     * update from 3.0 -> 4.0
     * @return void
     */
    public function update_4()
    {
        $this->setApplicationVersion('Tasks', '4.0');
    }
    
    /**
     * update from 3.0 -> 4.0
     * 
     * Neele release received updates up to 3.5 after branching
     * 
     * @return void
     */
    public function update_5()
    {
        $this->setApplicationVersion('Tasks', '4.0');
    }
}
