<?php
/**
 * @package     HumanResources
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * HumanResources config class
 * 
 * @package     HumanResources
 * @subpackage  Config
 */
class HumanResources_Config extends Tinebase_Config_Abstract
{
    /**
     * FreeTime Type
     * @var string
     */
    const FREETIME_TYPE = 'freetimeType';
    
    /**
     * FreeTime Status
     * @var string
     */
    const FREETIME_STATUS = 'freetimeStatus';
    
    /**
     * Default Feast Calendar (used for tailoring datepicker)
     * @var string
     */
    const DEFAULT_FEAST_CALENDAR = 'defaultFeastCalendar';
    
    /**
     * Default Feast Manager
     * @var string
     */
    const DEFAULT_VACATION_MANAGER = 'defaultVacationManager';
    
    /**
     * Default Sickness Manager
     * @var string
     */
    const DEFAULT_SICKNESS_MANAGER = 'defaultSicknessManager';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::FREETIME_TYPE => array(
                                   //_('Freetime Type')
            'label'                 => 'Freetime Type',
                                   //_('Possible free time definitions.')
            'description'           => 'Possible free time definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeType'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'VACATION'
        ),
        self::FREETIME_STATUS => array(
                                   //_('Freetime Status')
            'label'                 => 'Freetime Status',
                                   //_('Possible free time status definitions.')
            'description'           => 'Possible free time status definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeStatus'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'REQUESTED'
        ),
        self::DEFAULT_FEAST_CALENDAR => array(
            'label'                 => 'Default Feast Calendar',
            'description'           => 'Here you can define the default feast calendar used to set feast days and other free days in datepicker',
            'type'                  => 'container',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
        ),
        self::DEFAULT_VACATION_MANAGER => array(
            'label'                 => 'Default Vacation Manager',
            'description'           => 'Here you can define a vacation manager used per default',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
        ),
        self::DEFAULT_SICKNESS_MANAGER => array(
            'label'                 => 'Default Sickness Manager',
            'description'           => 'Here you can define a sickness manager used per default',
            'type'                  => 'string',
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'HumanResources';
    
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
