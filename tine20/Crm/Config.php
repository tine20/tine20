<?php
/**
 * @package     Crm
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Crm config class
 * 
 * @package     Crm
 * @subpackage  Config
 */
class Crm_Config extends Tinebase_Config_Abstract
{
    /**
     * lead import feature
     *
     * @var string
     */
    const FEATURE_LEAD_IMPORT = 'featureLeadImport';

    /**
     * fields for lead record duplicate check
     *
     * @var string
     */
    const LEAD_DUP_FIELDS = 'leadDupFields';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        /**
         * enabled Crm features
         */
        self::ENABLED_FEATURES => array(
            //_('Enabled Features')
            'label'                 => 'Enabled Features',
            //_('Enabled Features in Crm Application.')
            'description'           => 'Enabled Features in Crm Application.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'content'               => array(
                self::FEATURE_LEAD_IMPORT => array(
                    'label'         => 'Lead Import', //_('Lead Import')
                    'description'   => 'Lead Import',
                ),
            ),
            'default'               => array(
                self::FEATURE_LEAD_IMPORT => true,
            ),
        ),
        self::LEAD_DUP_FIELDS => array(
            //_('Lead duplicate check fields')
            'label'                 => 'Lead duplicate check fields',
            //_('These fields are checked when a new lead is created. If a record with the same data in the fields is found, a duplicate exception is thrown.')
            'description'           => 'These fields are checked when a new lead is created. If a record with the same data in the fields is found, a duplicate exception is thrown.',
            'type'                  => 'array',
            'contents'              => 'array',
            'clientRegistryInclude' => TRUE,
            'default'               => array('lead_name'),
        ),
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Crm';
    
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
