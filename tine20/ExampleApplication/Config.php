<?php
/**
 * @package     ExampleApplication
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
            self::LABEL              => 'Example Mail Config',
            self::DESCRIPTION        => 'explain some stuff here',
            self::TYPE               => self::TYPE_OBJECT,
            self::CLASSNAME          => Tinebase_Config_Struct::class,
            self::CONTENT            => array(
                self::SMTP              => array(
                    self::LABEL              => 'Example SMTP Config',
                    self::DESCRIPTION        => 'explain some stuff here',
                    self::TYPE               => self::TYPE_OBJECT,
                    self::CLASSNAME          => Tinebase_Config_Struct::class,
                    self::CONTENT            => array(
                        self::HOST              => array(
                            self::LABEL              => 'Example SMTP Host',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_STRING,
                            self::DEFAULT            => 'localhost'
                        ),
                        self::PORT              => array(
                            self::LABEL              => 'Example SMTP Port',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_INT,
                            self::DEFAULT            => 123
                        ),
                        self::SSL              => array(
                            self::LABEL              => 'Example SMTP SSL usage',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_BOOL,
                            self::DEFAULT            => true
                        ),
                    ),
                    self::DEFAULT => array(),
                ),
                self::IMAP              => array(
                    self::LABEL              => 'Example IMAP Config',
                    self::DESCRIPTION        => 'explain some stuff here',
                    self::TYPE               => self::TYPE_OBJECT,
                    self::CLASSNAME          => Tinebase_Config_Struct::class,
                    self::CONTENT            => array(
                        self::HOST              => array(
                            self::LABEL              => 'Example IMAP Host',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_STRING,
                            self::DEFAULT            => 'foreignhost'
                        ),
                        self::PORT              => array(
                            self::LABEL              => 'Example IMAP Port',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_INT,
                            self::DEFAULT            => 346
                        ),
                        self::SSL              => array(
                            self::LABEL              => 'Example SMTP SSL usage',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_BOOL,
                            self::DEFAULT            => false
                        ),
                    ),
                    self::DEFAULT => array(),
                )
            ),
            self::DEFAULT => array(),
        ),

        self::EXAMPLE_STATUS => array(
                                   //_('Status Available')
            self::LABEL              => 'Status Available',
                                   //_('Possible status. Please note that additional status might impact other ExampleApplication systems on export or syncronisation.')
            self::DESCRIPTION        => 'Possible status. Please note that additional status might impact other ExampleApplication systems on export or syncronisation.',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => array('recordModel' => ExampleApplication_Model_Status::class),
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT            => array(
                'records' => array(
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/ok.png',                   'system' => true), //_('Completed')
                    array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/oxygen/16x16/actions/dialog-cancel.png',        'system' => true), //_('Cancelled')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/oxygen/16x16/actions/view-refresh.png',         'system' => true), //_('In process')
                ),
                self::DEFAULT => 'IN-PROCESS'
            )
        ),

        self::EXAMPLE_REASON => array(
            //_('Reasons Available')
            self::LABEL              => 'Reasons Available',
            //_('Possible status reasons.')
            self::DESCRIPTION        => 'Possible status reasons.',
            self::TYPE               => self::TYPE_KEYFIELD_CONFIG,
            self::OPTIONS               => array(
                'parentField'     => 'status'
            ),
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => false,
            self::DEFAULT            => array(
                'records' => array(
                    array('id' => 'COMPLETED:CHANGE',           'value' => 'Change'), //_('Change')
                    array('id' => 'COMPLETED:DOCU',             'value' => 'Documentation'), //_('Documentation')
                    array('id' => 'CANCELLED:REQCHANGE',        'value' => 'Requirement Changed'), //_('Requirement Changed')
                    array('id' => 'CANCELLED:NOTPOSSIBLE',      'value' => 'Not Possible'), //_('Not Possible')
                    array('id' => 'IN-PROCESS:IMPLEMENTATION',  'value' => 'Implementation'), //_('Implementation')
                    array('id' => 'IN-PROCESS:REVIEW',          'value' => 'Review'), //_('Review')
                    array('id' => 'IN-PROCESS:INTEGRATION',     'value' => 'Integration'), //_('Integration')
                ),
                self::DEFAULT => array('COMPLETED:CHANGE', 'CANCELLED:REQCHANGE', 'IN-PROCESS:IMPLEMENTATION'),
            )
        ),

        self::ENABLED_FEATURES => array(
            //_('Enabled Features')
            self::LABEL              => 'Enabled Features',
            //_('Enabled Features in Sales Application.')
            self::DESCRIPTION        => 'Enabled Features in Example Application.',
            self::TYPE               => 'object',
            self::CLASSNAME          => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::CONTENT            => array(
                self::EXAMPLE_FEATURE => array(
                    self::LABEL      => 'Invoices Module', //_('Invoices Module')
                    self::DESCRIPTION=> 'Invoices Module',
                    self::TYPE       => 'boolean',
                    self::DEFAULT    => true,
                ),
            ),
            self::DEFAULT            => array(),
        ),

        self::EXAMPLE_STRING => array(
            //_('Example String')
            self::LABEL              => 'Example String',
            //_('Just an example string for test purpose')
            self::DESCRIPTION        => 'Just an example string for test purpose',
            self::TYPE               => 'string',
            self::DEFAULT            => self::EXAMPLE_STRING,
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

    public static function destroyInstance()
    {
        self::$_instance = null;
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
