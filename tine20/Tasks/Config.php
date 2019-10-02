<?php
/**
 * @package     Tasks
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * task config class
 * 
 * @package     Tasks
 * @subpackage  Config
 */
class Tasks_Config extends Tinebase_Config_Abstract
{
    /**
     * Tasks Status Available
     * 
     * @var string
     */
    const TASK_STATUS = 'taskStatus';
    
    /**
     * Tasks Priorities Available
     * 
     * @var string
     */
    const TASK_PRIORITY = 'taskPriority';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::TASK_STATUS => array(
                                   //_('Tasks status available')
            'label'                 => 'Tasks status available',
                                   //_('Possible tasks status. Please note that additional attendee status might impact other Tasks systems on export or syncronisation.')
            'description'           => 'Possible tasks status. Please note that additional attendee status might impact other Tasks systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Tasks_Model_Status'),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'is_open' => 1,  'icon' => 'images/icon-set/icon_invite.svg', 'system' => true), //_('No response')
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0,  'icon' => 'images/icon-set/icon_ok.svg',                   'system' => true), //_('Completed')
                    array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0,  'icon' => 'images/icon-set/icon_stop.svg',        'system' => true), //_('Cancelled')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1,  'icon' => 'images/icon-set/icon_reload.svg',         'system' => true), //_('In process')
                ),
                'default' => 'NEEDS-ACTION'
            )
        ),
        self::TASK_PRIORITY => array(
                                   //_('Task priorities available')
            'label'                 => 'Task priorities available',
                                   //_('Possible task priorities. Please note that additional priorities might impact other Tasks systems on export or syncronisation.')
            'description'           => 'Possible task priorities. Please note that additional priorities might impact other Tasks systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Tasks_Model_Priority'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => Tasks_Model_Priority::LOW,    'value' => 'low',      'icon' => 'images/icon-set/icon_prio_low.svg', 'system' => true), //_('low')
                    array('id' => Tasks_Model_Priority::NORMAL, 'value' => 'normal',   'icon' => 'images/icon-set/icon_prio_normal.svg', 'system' => true), //_('normal')
                    array('id' => Tasks_Model_Priority::HIGH,   'value' => 'high',     'icon' => 'images/icon-set/icon_prio_high.svg', 'system' => true), //_('high')
                    array('id' => Tasks_Model_Priority::URGENT, 'value' => 'urgent',   'icon' => 'images/icon-set/icon_prio_urgent.svg', 'system' => true), //_('urgent')
                ),
                'default' => Tasks_Model_Priority::NORMAL,
            )
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Tasks';
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __construct() {}
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */    
    private function __clone() {}
    
    /**
     * Returns instance of Tinebase_Config
     *
     * @return Tinebase_Config
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
