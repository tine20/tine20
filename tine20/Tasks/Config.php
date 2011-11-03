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
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::TASK_STATUS => array(
                                   //_('Tasks Status Available')
            'label'                 => 'Tasks Status Available',
                                   //_('Possible tasks status. Please note that additional attendee status might impact other Tasks systems on export or syncronisation.')
            'description'           => 'Possible tasks status. Please note that additional attendee status might impact other Tasks systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Tasks_Model_Status'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'NEEDS-ACTION'
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
