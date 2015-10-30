<?php
/**
 * @package     ExampleApplication
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * ExampleApplication config class
 * 
 * @package     ExampleApplication
 * @subpackage  Config
 */
class ExampleApplication_Config extends Tinebase_Config_Abstract
{
    /**
     * ExampleApplication Status
     * 
     * @var string
     */
    const EXAMPLE_STATUS = 'exampleStatus';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::EXAMPLE_STATUS => array(
                                   //_('Status Available')
            'label'                 => 'Status Available',
                                   //_('Possible status. Please note that additional status might impact other ExampleApplication systems on export or syncronisation.')
            'description'           => 'Possible status. Please note that additional status might impact other ExampleApplication systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'ExampleApplication_Model_Status'),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
            'default'               => array(
                'records' => array(
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Completed')
                    array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Cancelled')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true), //_('In process')
                ),
                'default' => 'IN-PROCESS'
            )
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'ExampleApplication';
    
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
