<?php
/**
 * @package     SimpleFAQ
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * SimpleFAQ config class
 * 
 * @package     SimpleFAQ
 * @subpackage  Config
 * 
 * @todo add config settings here
 */
class SimpleFAQ_Config extends Tinebase_Config_Abstract
{
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'SimpleFAQ';
    
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
