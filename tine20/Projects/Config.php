<?php
/**
 * @package     Projects
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Projects config class
 * 
 * @package     Projects
 * @subpackage  Config
 */
class Projects_Config extends Tinebase_Config_Abstract
{
    /**
     * Projects Status
     * 
     * @var string
     */
    const PROJECT_STATUS = 'projectStatus';
    
    /**
     * Project attendee role
     * 
     * @var string
     */
    const PROJECT_ATTENDEE_ROLE = 'projectAttendeeRole';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::PROJECT_STATUS => array(
                                   //_('Available Project Status')
            'label'                 => 'Available Project Status',
                                   //_('Possible Project status. Please note that additional project status might impact other Projects systems on export or syncronisation.')
            'description'           => 'Possible Project status. Please note that additional project status might impact other Projects systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Projects_Model_Status'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'NEEDS-ACTION', 'value' => 'On hold',     'is_open' => 1, 'icon' => 'images/icon-set/icon_invite.svg', 'system' => true),  //_('On hold')
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/icon-set/icon_ok.svg',                   'system' => true),  //_('Completed')
                    array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/icon-set/icon_stop.svg',        'system' => true),  //_('Cancelled')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/icon-set/icon_reload.svg',         'system' => true),  //_('In process')
                ),
                'default' => 'IN-PROCESS'
            )
        ),
        self::PROJECT_ATTENDEE_ROLE => array(
                                   //_('Available Project Attendee Role')
            'label'                 => 'Available Project Attendee Role',
                                   //_('Possible Project attendee roles. Please note that additional project attendee roles might impact other Projects systems on export or syncronisation.')
            'description'           => 'Possible Project attendee roles. Please note that additional project attendee roles might impact other Projects systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Projects_Model_AttendeeRole'),
            'clientRegistryInclude' => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'COWORKER',    'value' => 'Coworker',    'icon' => 'images/oxygen/16x16/apps/system-users.png',               'system' => true), //_('Coworker')
                    array('id' => 'RESPONSIBLE', 'value' => 'Responsible', 'icon' => 'images/oxygen/16x16/apps/preferences-desktop-user.png',   'system' => true), //_('Responsible')
                ),
                'default' => 'COWORKER'
            )
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Projects';
    
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
