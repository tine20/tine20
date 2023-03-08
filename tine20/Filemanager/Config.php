<?php
/**
 * @package     Filemanager
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2016-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Filemanager config class
 * 
 * @package     Filemanager
 * @subpackage  Config
 */
class Filemanager_Config extends Tinebase_Config_Abstract
{
    const APP_NAME = 'Filemanager';
    const PUBLIC_DOWNLOAD_URL = 'publicDownloadUrl';
    const PUBLIC_DOWNLOAD_DEFAULT_VALID_TIME = 'publicDownloadDefaultValidTime';

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
        ),
        self::PUBLIC_DOWNLOAD_DEFAULT_VALID_TIME => [
            //_('Public Download Default Valid Time')
            'label'                 => 'Public Download Default Valid Time',
            //_('Public download fefault valid time, unit is day')
            'description'           => 'Public Download Default Valid Time, unit is day',
            self::TYPE                  => self::TYPE_INT,
            self::DEFAULT_STR           => 30, // 30 days
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ]
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
