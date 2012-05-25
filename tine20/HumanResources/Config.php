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
