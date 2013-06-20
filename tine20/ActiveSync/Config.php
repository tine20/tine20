<?php
/**
 * @package     ActiveSync
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * ActiveSync config class
 * 
 * @package     ActiveSync
 * @subpackage  Config
 */
class ActiveSync_Config extends Tinebase_Config_Abstract
{
    /**
     * fields for contact record duplicate check
     * 
     * @var string
     */
    const DEFAULT_POLICY = 'defaultPolicy';

    /**
     * DISABLE_ACCESS_LOG
     *
     * @var string
     */
    const DISABLE_ACCESS_LOG = 'disableaccesslog';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::DEFAULT_POLICY => array(
        //_('Default policy for new devices')
            'label'                 => 'Default policy for new devices',
        //_('Enter the id of the policy to apply to newly created devices.')
            'description'           => 'Enter the id of the policy to apply to newly created devices.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => TRUE,
            'setBySetupModule'      => FALSE,
        ),
        self::DISABLE_ACCESS_LOG => array(
        //_('Disable Access Log')
            'label'                 => 'Disable Access Log creation',
        //_('Disable ActiveSync Access Log creation.')
            'description'           => 'Disable ActiveSync Access Log creation.',
            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => TRUE,
            'default'               => FALSE,
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'ActiveSync';
    
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
     * don't clone. Use the singleton.
     */
    private function __clone() {}
    
    /**
     * Returns instance of ActiveSync_Config
     *
     * @return ActiveSync_Config
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
