<?php
/**
 * Tine 2.0
 * 
 * @package     Tasks
 * @subpackage    Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Goekmen Ciyiltepe <g.ciyiltepe@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class for Tasks initialization
 * 
 * @package     Tasks
 * @subpackage    Setup
 */
class Tasks_Setup_Initialize extends Setup_Initialize
{
    /**
     * init key fields
     */
    protected function _initializeKeyFields()
    {
        $cb = new Tinebase_Backend_Sql(array(
            'modelName' => 'Tinebase_Model_Config', 
            'tableName' => 'config',
        ));
        
        $tasksStatusConfig = array(
            'name'    => Tasks_Config::TASK_STATUS,
            'records' => array(
                array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'is_open' => 1,  'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true), //_('No response')
                array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0,  'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Completed')
                array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0,  'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Cancelled')
                array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1,  'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true), //_('In process')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'name'              => Tasks_Config::TASK_STATUS,
            'value'             => json_encode($tasksStatusConfig),
        )));
        
        $tasksPriorityConfig = array(
            'name'    => Tasks_Config::TASK_PRIORITY,
            'records' => array(
                array('id' => 'LOW',     'value' => 'low',        'icon' => 'images/oxygen/16x16/actions/go-down.png', 'system' => true), //_('low')
                array('id' => 'NORMAL', 'value' => 'normal',   'icon' => 'images/oxygen/16x16/actions/go-next.png', 'system' => true), //_('normal')
                array('id' => 'HIGH',   'value' => 'high',     'icon' => 'images/oxygen/16x16/actions/go-up.png',   'system' => true), //_('high')
                array('id' => 'URGENT', 'value' => 'urgent',   'icon' => 'images/oxygen/16x16/emblems/emblem-important.png', 'system' => true), //_('urgent')
            ),
        );
        
        $cb->create(new Tinebase_Model_Config(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'name'              => Tasks_Config::TASK_PRIORITY,
            'value'             => json_encode($tasksPriorityConfig),
        )));
    }
    
    /**
     * init favorites
     */
    protected function _initializeFavorites()
    {
        $pfe = new Tinebase_PersistentFilter_Backend_Sql();
        
        $commonValues = array(
            'account_id'        => NULL,
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            'model'             => 'Tasks_Model_TaskFilter',
        );
        
        $closedStatus = Tasks_Config::getInstance()->get(Tasks_Config::TASK_STATUS)->records->filter('is_open', 0);
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => Tasks_Preference::DEFAULTPERSISTENTFILTER_NAME,
            'description'       => "All tasks of my taskslists", // _("All tasks of my taskslists")
            'filters'           => array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => '/personal/' . Tinebase_Model_User::CURRENTACCOUNT),
                array('field' => 'status',    'operator' => 'notin',  'value' => $closedStatus->getId()),
            )
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My open tasks",
            'description'       => "My open tasks", // _("My open tasks")
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
                array('field' => 'status',    'operator' => 'notin',  'value' => $closedStatus->getId()),
            )
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "My open tasks this week",
            'description'       => "My open tasks this week", // _("My open tasks this week")
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
                array('field' => 'due',          'operator' => 'within', 'value' => 'weekThis'),
                array('field' => 'status',    'operator' => 'notin',  'value' => $closedStatus->getId()),
            )
        ))));

        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "All tasks for me",                      // _("All tasks for me")
            'description'       => "All tasks that I am responsible for",   // _("All tasks that I am responsible for")
            'filters'           => array(
                array('field' => 'organizer',    'operator' => 'equals', 'value' => Tinebase_Model_User::CURRENTACCOUNT),
            )
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Last modified by me", // _("Last modified by me")
            'description'       => "All tasks that I have last modified", // _("All tasks that I have last modified")
            'filters'           => array(array(
                'field'     => 'last_modified_by',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_User::CURRENTACCOUNT,
            )),
        ))));
        
        $pfe->create(new Tinebase_Model_PersistentFilter(array_merge($commonValues, array(
            'name'              => "Tasks without responsible", // _("Tasks without responsible")
            'description'       => "Tasks without responsible",
            'filters'           => array(array(
                'field'     => 'organizer',
                'operator'  => 'equals',
                'value'     => '',
            )),
        ))));
    }
}
