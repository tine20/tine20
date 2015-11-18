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
            'default'               => array(
                'records' => array(
                    array('id' => 'NEEDS-ACTION', 'value' => 'No response', 'is_open' => 1,  'icon' => 'images/oxygen/16x16/actions/mail-mark-unread-new.png', 'system' => true), //_('No response')
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0,  'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Completed')
                    array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0,  'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Cancelled')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1,  'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true), //_('In process')
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
                    array('id' => 'LOW',     'value' => 'low',        'icon' => 'images/oxygen/16x16/actions/go-down.png', 'system' => true), //_('low')
                    array('id' => 'NORMAL', 'value' => 'normal',   'icon' => 'images/oxygen/16x16/actions/go-next.png', 'system' => true), //_('normal')
                    array('id' => 'HIGH',   'value' => 'high',     'icon' => 'images/oxygen/16x16/actions/go-up.png',   'system' => true), //_('high')
                    array('id' => 'URGENT', 'value' => 'urgent',   'icon' => 'images/oxygen/16x16/emblems/emblem-important.png', 'system' => true), //_('urgent')
                ),
                'default' => 'NORMAL'
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
