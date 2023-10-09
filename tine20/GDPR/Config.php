<?php
/**
 * @package     GDPR
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2023 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * GDPR config class
 * 
 * @package     GDPR
 * @subpackage  Config
 */
class GDPR_Config extends Tinebase_Config_Abstract
{
    /**
     * the Tine2.0 Application name
     *
     * @var string
     */
    const APP_NAME = 'GDPR';

    /**
     * Is data provenance for ADB contacts mandatory
     * 
     * @var string
     */
    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY = 'dataProvenanceADBContactMandatory';

    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY_YES     = 'yes';
    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY_NO      = 'no';
    const ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT = 'default';

    /**
     * The default data provenance for ADB contacts
     *
     * @var string
     */
    const DEFAULT_ADB_CONTACT_DATA_PROVENANCE = 'defaultADBContactDataProvenance';

    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = [
        self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY => [
                                        //_('Data provenance for ADB contacts mandatory')
            self::LABEL                 => 'Data provenance for ADB contacts mandatory',
            //_('Whether the data provenance for ADB contacts is mandatory, not mandatory or empty provenances will be set to a default')
            self::DESCRIPTION           => 'Sets whether the data provenance for ADB contacts is mandatory or not.',
            self::TYPE                  => self::TYPE_KEYFIELD,
            self::OPTIONS               => [
                'records'                   => [
                    ['id' => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_YES,       'value' => 'yes'], //_('yes')
                    ['id' => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_NO,        'value' => 'no'], //_('no')
                    ['id' => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT,   'value' => 'default'], //_('default')
                ],
            ],
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => self::ADB_CONTACT_DATA_PROVENANCE_MANDATORY_DEFAULT,
        ],
        self::DEFAULT_ADB_CONTACT_DATA_PROVENANCE => [
            //_('Default data provenance for ADB contacts')
            self::LABEL                 => 'Default data provenance for ADB contacts',
            //_('Default data provenance for ADB contacts')
            self::DESCRIPTION           => 'Default data provenance for ADB contacts',
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => GDPR_Config::APP_NAME,
                self::MODEL_NAME            => GDPR_Model_DataProvenance::MODEL_NAME_PART,
            ],
            // @TODO: remove TYPE_RECORD_CONTROLLER and derive it from modelName!
            self::TYPE_RECORD_CONTROLLER=> GDPR_Controller_DataProvenance::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT_STR           => '',
        ],
    ];
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = self::APP_NAME;
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_Config
     */
    private static $_instance;
    
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
