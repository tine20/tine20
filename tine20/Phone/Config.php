<?php
/**
 * @package     Phone
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Phone config class
 * 
 * @package     Phone
 * @subpackage  Config
 */
class Phone_Config extends Tinebase_Config_Abstract
{
    /**
     * @var string
     */
    const LOCAL_PREFIX = 'localPrefix';

    /**
     * @var string
     */
    const AREA_CODE = 'areaCode';

    /**
     * @var string
     */
    const LOCAL_CALL_REGEX = 'localCallRegex';

    /**
     * @var string
     */
    const OWN_NUMBER_PREFIX = 'ownNumberPrefix';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::LOCAL_PREFIX => array(
                                    //_('Local prefix for outgoing calls')
            'label'                 => 'Local prefix for outgoing calls',
                                    //_('Local prefix for outgoing calls')
            'description'           => 'Local prefix for outgoing calls',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'default'               => '0'
        ),

        self::AREA_CODE => array(
                                    //_('telephone area code')
            'label'                 => 'telephone area code',
                                    //_('telephone area code')
            'description'           => 'telephone area code',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
        ),

        self::LOCAL_CALL_REGEX => array(
                                    //_('regex for local calls')
            'label'                 => 'regex for local calls',
                                    //_('Identifies if a calling number is a local call without leading area code.')
            'description'           => 'Identifies if a calling number is a local call without leading area code.',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
            'default'               => '/^[^0]/'
        ),

        self::OWN_NUMBER_PREFIX => array(
                                    //_('own telephone number')
            'label'                 => 'own telephone number',
                                    //_('Own telephone number to prefix to internal calls.')
            'description'           => 'Own telephone number to prefix to internal calls.',
            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Phone';
    
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
