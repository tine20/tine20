<?php
/**
 * @package     ExampleApplication
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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

    const EXAMPLE_REASON = 'exampleReason';

    const EXAMPLE_FEATURE = 'exampleFeature';

    const EXAMPLE_STRING = 'exampleString';

    const EXAMPLE_MAILCONFIG = 'exampleMailConfig';
    const SMTP = 'smtp';
    const IMAP = 'imap';
    const HOST = 'host';
    const PORT = 'port';
    const SSL = 'ssl';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::EXAMPLE_MAILCONFIG => array(
            'label'                 => 'Example Mail Config',
            'description'           => 'explain some stuff here',
            'type'                  => Tinebase_Config_Abstract::TYPE_OBJECT,
            'class'                 => 'Tinebase_Config_Struct',
            'content'               => array(
                self::SMTP              => array(
                    'label'                 => 'Example SMTP Config',
                    'description'           => 'explain some stuff here',
                    'type'                  => Tinebase_Config_Abstract::TYPE_OBJECT,
                    'class'                 => 'Tinebase_Config_Struct',
                    'content'               => array(
                        self::HOST              => array(
                            'label'                 => 'Example SMTP Host',
                            'description'           => 'explain some stuff here',
                            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
                            'default'               => 'localhost'
                        ),
                        self::PORT              => array(
                            'label'                 => 'Example SMTP Port',
                            'description'           => 'explain some stuff here',
                            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
                            'default'               => 123
                        ),
                        self::SSL              => array(
                            'label'                 => 'Example SMTP SSL usage',
                            'description'           => 'explain some stuff here',
                            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
                            'default'               => true
                        ),
                    ),
                    'default' => array(),
                ),
                self::IMAP              => array(
                    'label'                 => 'Example IMAP Config',
                    'description'           => 'explain some stuff here',
                    'type'                  => Tinebase_Config_Abstract::TYPE_OBJECT,
                    'class'                 => 'Tinebase_Config_Struct',
                    'content'               => array(
                        self::HOST              => array(
                            'label'                 => 'Example IMAP Host',
                            'description'           => 'explain some stuff here',
                            'type'                  => Tinebase_Config_Abstract::TYPE_STRING,
                            'default'               => 'foreignhost'
                        ),
                        self::PORT              => array(
                            'label'                 => 'Example IMAP Port',
                            'description'           => 'explain some stuff here',
                            'type'                  => Tinebase_Config_Abstract::TYPE_INT,
                            'default'               => 346
                        ),
                        self::SSL              => array(
                            'label'                 => 'Example SMTP SSL usage',
                            'description'           => 'explain some stuff here',
                            'type'                  => Tinebase_Config_Abstract::TYPE_BOOL,
                            'default'               => false
                        ),
                    ),
                    'default' => array(),
                )
            ),
            'default' => array(),
        ),

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

        self::EXAMPLE_REASON => array(
            //_('Reasons Available')
            'label'                 => 'Reasons Available',
            //_('Possible status reasons.')
            'description'           => 'Possible status reasons.',
            'type'                  => 'keyFieldConfig',
            'options'               => array(
                'parentField'     => 'status'
            ),
            'clientRegistryInclude' => true,
            'setByAdminModule'      => true,
            'setBySetupModule'      => false,
            'default'               => array(
                'records' => array(
                    array('id' => 'COMPLETED:CHANGE',           'value' => 'Change'), //_('Change')
                    array('id' => 'COMPLETED:DOCU',             'value' => 'Documentation'), //_('Documentation')
                    array('id' => 'CANCELLED:REQCHANGE',        'value' => 'Requirement Changed'), //_('Requirement Changed')
                    array('id' => 'CANCELLED:NOTPOSSIBLE',      'value' => 'Not Possible'), //_('Not Possible')
                    array('id' => 'IN-PROCESS:IMPLEMENTATION',  'value' => 'Implementation'), //_('Implementation')
                    array('id' => 'IN-PROCESS:REVIEW',          'value' => 'Review'), //_('Review')
                    array('id' => 'IN-PROCESS:INTEGRATION',     'value' => 'Integration'), //_('Integration')
                ),
                'default' => array('COMPLETED:CHANGE', 'CANCELLED:REQCHANGE', 'IN-PROCESS:IMPLEMENTATION'),
            )
        ),

        self::ENABLED_FEATURES => array(
            //_('Enabled Features')
            'label'                 => 'Enabled Features',
            //_('Enabled Features in Sales Application.')
            'description'           => 'Enabled Features in Example Application.',
            'type'                  => 'object',
            'class'                 => 'Tinebase_Config_Struct',
            'clientRegistryInclude' => TRUE,
            'content'               => array(
                self::EXAMPLE_FEATURE => array(
                    'label'         => 'Invoices Module', //_('Invoices Module')
                    'description'   => 'Invoices Module',
                    'type'          => 'boolean',
                    'default'       => true,
                ),
            ),
            'default'               => array(),
        ),

        self::EXAMPLE_STRING => array(
            //_('Example String')
            'label'                 => 'Example String',
            //_('Just an example string for test purpose')
            'description'           => 'Just an example string for test purpose',
            'type'                  => 'string',
            'default'               => self::EXAMPLE_STRING,
        )
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
