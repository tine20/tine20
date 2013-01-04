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
     * Vacation Status
     * @var string
     */
    const VACATION_STATUS = 'vacationStatus';

    /**
     * Sickness Status
     * @var string
     */
    const SICKNESS_STATUS = 'sicknessStatus';
    
    /**
     * Default Feast Calendar (used for tailoring datepicker)
     * @var string
     */
    const DEFAULT_FEAST_CALENDAR = 'defaultFeastCalendar';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::FREETIME_TYPE => array(
            //_('Freetime Type')
            'label'                 => 'Freetime Type',
            //_('Possible free time definitions')
            'description'           => 'Possible free time definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeType'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'VACATION'
        ),
        self::VACATION_STATUS => array(
            //_('Vacation Status')
            'label'                 => 'Vacation Status',
            //_('Possible vacation status definitions')
            'description'           => 'Possible vacation status definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeStatus'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'REQUESTED'
        ),
        self::SICKNESS_STATUS => array(
            //_('Sickness Status')
            'label'                 => 'Sickness Status',
            //_('Possible sickness status definitions')
            'description'           => 'Possible sickness status definitions',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'HumanResources_Model_FreeTimeStatus'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'EXCUSED'
        ),
        self::DEFAULT_FEAST_CALENDAR => array(
            // _('Default Feast Calendar')
            'label'                 => 'Default Feast Calendar',
            // _('Here you can define the default feast calendar used to set feast days and other free days in datepicker')
            'description'           => 'Here you can define the default feast calendar used to set feast days and other free days in datepicker',
            'type'                  => 'container',
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
    private function __construct() {
    }

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __clone() {
    }

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
