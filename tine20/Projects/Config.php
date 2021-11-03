<?php

/**
 * @package     Projects
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2021 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var string
     */
    public const APP_NAME = 'Projects';

    /**
     * Project attendee role
     *
     * @var string
     */
    public const PROJECT_ATTENDEE_ROLE = 'projectAttendeeRole';

    /**
     * Projects Scope keyfield config
     *
     * @var string
     */
    public const PROJECT_SCOPE = 'projectScope';

    /**
     * Projects Status
     * 
     * @var string
     */
    public const PROJECT_STATUS = 'projectStatus';

    /**
     * Projects Type keyfield config
     *
     * @var string
     */
    public const PROJECT_TYPE = 'projectType';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::PROJECT_ATTENDEE_ROLE => array(
            //_('Available Project Attendee Role')
            self::LABEL => 'Available Project Attendee Role',
            //_('Possible Project attendee roles. Please note that additional project attendee roles might impact other Projects systems on export or synchronisation.')
            self::DESCRIPTION => 'Possible Project attendee roles. Please note that additional project attendee roles might impact other Projects systems on export or synchronisation.',
            self::TYPE => 'keyFieldConfig',
            self::OPTIONS => array('recordModel' => 'Projects_Model_AttendeeRole'),
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::DEFAULT_STR => array(
                self::RECORDS => array(
                    array('id' => 'COWORKER', 'value' => 'Coworker', 'icon' => 'images/oxygen/16x16/apps/system-users.png', 'system' => true), //_('Coworker')
                    array('id' => 'RESPONSIBLE', 'value' => 'Responsible', 'icon' => 'images/oxygen/16x16/apps/preferences-desktop-user.png', 'system' => true), //_('Responsible')
                ),
                self::DEFAULT_STR => 'COWORKER'
            )
        ),
        self::PROJECT_SCOPE => [
            //_('Available Project Scopes')
            self::LABEL => 'Available Project Scopes',
            self::DESCRIPTION => 'Available Project Scopes',
            self::TYPE => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::DEFAULT_STR => [
                self::RECORDS => [
                    ['id' => 'INTERNAL', 'value' => 'Internal'], //_('Internal')
                    ['id' => 'EXTERNAL', 'value' => 'External'], //_('External')
                ],
            ]
        ],
        self::PROJECT_STATUS => array(
            //_('Available Project Status')
            self::LABEL => 'Available Project Status',
            //_('Possible Project status. Please note that additional project status might impact other Projects systems on export or synchronisation.')
            self::DESCRIPTION => 'Possible Project status. Please note that additional project status might impact other Projects systems on export or synchronisation.',
            self::TYPE => 'keyFieldConfig',
            self::OPTIONS => array('recordModel' => 'Projects_Model_Status'),
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::DEFAULT_STR => array(
                self::RECORDS => array(
                    array('id' => 'NEEDS-ACTION', 'value' => 'On hold', 'is_open' => 1, 'icon' => 'images/icon-set/icon_invite.svg', 'system' => true),  //_('On hold')
                    array('id' => 'COMPLETED', 'value' => 'Completed', 'is_open' => 0, 'icon' => 'images/icon-set/icon_ok.svg', 'system' => true),  //_('Completed')
                    array('id' => 'CANCELLED', 'value' => 'Cancelled', 'is_open' => 0, 'icon' => 'images/icon-set/icon_stop.svg', 'system' => true),  //_('Cancelled')
                    array('id' => 'IN-PROCESS', 'value' => 'In process', 'is_open' => 1, 'icon' => 'images/icon-set/icon_reload.svg', 'system' => true),  //_('In process')
                ),
                self::DEFAULT_STR => 'IN-PROCESS'
            )
        ),
        self::PROJECT_TYPE => [
            //_('Available Project Types')
            self::LABEL => 'Available Project Types',
            self::DESCRIPTION => 'Available Project Types',
            self::TYPE => self::TYPE_KEYFIELD_CONFIG,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE => true,
            self::DEFAULT_STR => [
                self::RECORDS => [
                    ['id' => 'CAMPAIGN', 'value' => 'Campaign'], //_('Campaign')
                    ['id' => 'ACTIVITY', 'value' => 'Activity'], //_('Activity')
                    ['id' => 'CONTRACT', 'value' => 'Contract'], //_('Contract')
                    ['id' => 'PROJECT', 'value' => 'Project'], //_('Project')
                ],
                self::DEFAULT_STR => 'PROJECT'
            ]
        ],
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
