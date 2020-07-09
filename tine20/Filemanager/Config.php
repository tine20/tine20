<?php
/**
 * @package     Filemanager
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Filemanager config class
 * 
 * @package     Filemanager
 * @subpackage  Config
 */
class Filemanager_Config extends Tinebase_Config_Abstract
{
    const PUBLIC_DOWNLOAD_URL = 'publicDownloadUrl';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::PUBLIC_DOWNLOAD_URL => array(
                                   //_('Public Download URL')
            'label'                 => 'Public Download URL',
                                   //_('Deliver anonymous downloads from another URL. Make sure the rewrite rules are adjusted accordingly.')
            'description'           => 'Deliver anonymous downloads from another URL. Make sure the rewrite rules are adjusted accordingly.',
            'type'                  => 'string',
            'clientRegistryInclude' => true,
            'setByAdminModule'      => false,
            'setBySetupModule'      => true,
        )
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Filemanager';
    
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
