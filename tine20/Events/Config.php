<?php
/**
 * @package     Events
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Events config class
 * 
 * @package     Events
 * @subpackage  Config
 */
class Events_Config extends Tinebase_Config_Abstract
{
    /**
     * Planned Status
     * 
     * @var string
     */
    const PLANNED_STATUS = 'plannedStatus';
    
    /**
     * Default Events Calendar
     * @var string
     */
    const DEFAULT_EVENTS_CALENDAR = 'defaultEventsCalendar';
    
     /** Action type
     *
     * @var string
     */
    const ACTION_TYPE = 'actionType';
    
    /**
     * Project type
     *
     * @var string
     */
    const PROJECT_TYPE = 'projectType';
    
    /**
     * Target Groups
     *
     * @var string
     */
    const TARGET_GROUPS = 'targetGroups';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::PLANNED_STATUS => array(
                                   //_('Is planned')
            'label'                 => 'Is planned',
                                   //_('Is the event planned?')
            'description'           => 'Is the event planned?',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Events_Model_Status'),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => false,
            'default'               => array(
                'records' => array(
                    array('id' => 'SET',    'value' => 'Set'), //_('Set')
                    array('id' => 'OPTIONAL',    'value' => 'Optional'), //_('Optional')
                ),
                'default' => 'SET'
            )
        ),
        self::ACTION_TYPE => array(
            //_('Action')
            'label'                 => 'Action',
            //_('Type of action')
            'description'           => 'Type of action',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Events_Model_Status'),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
            'default'               => array(
                'records' => array(
                    array('id' => 'VERANSTALTUNG',    'value' => 'Veranstaltung',   'color' => '#0000FF'),
                    array('id' => 'BAU',    'value' => 'Bau',   'color' => '#FF0000'),
                    array('id' => 'DREHAUFNAHMEN',    'value' => 'Dreharbeiten',    'color' => '#00FF00'),
                ),
                'default' => 'VERANSTALTUNG'
            )
        ),
        self::PROJECT_TYPE => array(
            //_('Project type')
            'label'                 => 'Project type',
            //_('Type of project')
            'description'           => 'Type of project',
            'type'                  => 'keyFieldConfig',
            'options'               => array(
                'parentField'     => 'action'
            ),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
            'default'               => array(
                'records' => array(
                    array('id' => 'DREHAUFNAHMEN:AUFNAHMEN',           'value' => 'Aufnahmen'),
                    array('id' => 'DREHAUFNAHMEN:PROBE',             'value' => 'Probe'),
                    array('id' => 'VERANSTALTUNG:SEMINAR',             'value' => 'Seminar'),
                    array('id' => 'BAU:UMBAU',             'value' => 'Umbau'),
                ),
                'default' => array('VERANSTALTUNG:SEMINAR', 'DREHAUFNAHMEN:AUFNAHMEN', 'BAU:UMBAU'),
            )
        ),
        self::TARGET_GROUPS => array(
            //_('Target Groups')
            'label'                 => 'Target Groups',
            //_('Target Groups')
            'description'           => 'Target Groups',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Events_Model_Status'),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
            'default'               => array(
                'records' => array(
                    array('id' => 'ALLE',    'value' => 'Alle'),
                ),
                'default' => 'ALLE'
            )
        )
    );
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Events';
    
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
