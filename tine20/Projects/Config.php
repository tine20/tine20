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
                                   //_('Project Status Available')
            'label'                 => 'Project Status Available',
                                   //_('Possible Project status. Please note that additional project status might impact other Projects systems on export or syncronisation.')
            'description'           => 'Possible Project status. Please note that additional project status might impact other Projects systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Projects_Model_Status'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'IN-PROCESS'
        ),
        self::PROJECT_ATTENDEE_ROLE => array(
                                   //_('Project Attendee Role Available')
            'label'                 => 'Project Attendee Role Available',
                                   //_('Possible Project attendee roles. Please note that additional project attendee roles might impact other Projects systems on export or syncronisation.')
            'description'           => 'Possible Project attendee roles. Please note that additional project attendee roles might impact other Projects systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Projects_Model_AttendeeRole'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'COWORKER'
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
