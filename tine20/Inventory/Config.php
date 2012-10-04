<?php
/**
 * @package     Inventory
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Inventory config class
 * 
 * @package     Inventory
 * @subpackage  Config
 */
class Inventory_Config extends Tinebase_Config_Abstract
{
    /**
     * Inventory Status
     * 
     * @var string
     */
    const INVENTORY_STATUS = 'inventoryStatus';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::INVENTORY_STATUS => array(
                                   //_('Inventory Status Available')
            'label'                 => 'Inventory Status Available',
                                   //_('Possible status.')
            'description'           => 'Possible status.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Inventory_Model_Status'),
            'clientRegistryInclude' => TRUE,
            'default'               => 'UNKNOWN'
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Inventory';
    
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
