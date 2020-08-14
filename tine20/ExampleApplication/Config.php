<?php
/**
 * @package     ExampleApplication
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * ExampleApplication config class
 * 
 * @package     ExampleApplication
 * @subpackage  Config
 */
class ExampleApplication_Config extends Tinebase_Config_Abstract
{
    use Tinebase_Controller_SingletonTrait;

    const APP_NAME = 'ExampleApplication';

    /**
     * ExampleApplication Status
     * 
     * @var string
     */
    const EXAMPLE_STATUS = 'exampleStatus';

    const EXAMPLE_REASON = 'exampleReason';

    const EXAMPLE_FEATURE = 'exampleFeature';

    const EXAMPLE_STRING = 'exampleString';

    const EXAMPLE_RECORD = 'exampleRecord';

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
        self::EXAMPLE_RECORD        => [
            self::LABEL                 => 'Example Label',
            self::DESCRIPTION           => 'explain some stuff here',
            self::TYPE                  => self::TYPE_RECORD,
            self::OPTIONS               => [
                self::APPLICATION_NAME      => self::APP_NAME,
                self::MODEL_NAME            => ExampleApplication_Model_ExampleRecord::MODEL_NAME_PART,
            ],
            self::SETBYADMINMODULE      => true,
        ],
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
                            self::DEFAULT_STR        => 'localhost'
                        ),
                        self::PORT              => array(
                            self::LABEL              => 'Example SMTP Port',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_INT,
                            self::DEFAULT_STR        => 123
                        ),
                        self::SSL              => array(
                            self::LABEL              => 'Example SMTP SSL usage',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_BOOL,
                            self::DEFAULT_STR        => true
                        ),
                    ),
                    self::DEFAULT_STR => array(),
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
                            self::DEFAULT_STR        => 'foreignhost'
                        ),
                        self::PORT              => array(
                            self::LABEL              => 'Example IMAP Port',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_INT,
                            self::DEFAULT_STR        => 346
                        ),
                        self::SSL              => array(
                            self::LABEL              => 'Example SMTP SSL usage',
                            self::DESCRIPTION        => 'explain some stuff here',
                            self::TYPE               => self::TYPE_BOOL,
                            self::DEFAULT_STR        => false
                        ),
                    ),
                    self::DEFAULT_STR => array(),
                )
            ),
            self::DEFAULT_STR => array(),
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
            self::DEFAULT_STR           => array(
                'records' => array(
                    array('id' => 'COMPLETED',    'value' => 'Completed',   'is_open' => 0, 'icon' => 'images/icon-set/icon_ok.svg',     'system' => true), //_('Completed')
                    array('id' => 'CANCELLED',    'value' => 'Cancelled',   'is_open' => 0, 'icon' => 'images/icon-set/icon_stop.svg',   'system' => true), //_('Cancelled')
                    array('id' => 'IN-PROCESS',   'value' => 'In process',  'is_open' => 1, 'icon' => 'images/icon-set/icon_reload.svg', 'system' => true), //_('In process')
                ),
                self::DEFAULT_STR => 'IN-PROCESS'
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
            self::DEFAULT_STR           => array(
                'records' => array(
                    array('id' => 'COMPLETED:CHANGE',           'value' => 'Change'), //_('Change')
                    array('id' => 'COMPLETED:DOCU',             'value' => 'Documentation'), //_('Documentation')
                    array('id' => 'CANCELLED:REQCHANGE',        'value' => 'Requirement Changed'), //_('Requirement Changed')
                    array('id' => 'CANCELLED:NOTPOSSIBLE',      'value' => 'Not Possible'), //_('Not Possible')
                    array('id' => 'IN-PROCESS:IMPLEMENTATION',  'value' => 'Implementation'), //_('Implementation')
                    array('id' => 'IN-PROCESS:REVIEW',          'value' => 'Review'), //_('Review')
                    array('id' => 'IN-PROCESS:INTEGRATION',     'value' => 'Integration'), //_('Integration')
                ),
                self::DEFAULT_STR => array('COMPLETED:CHANGE', 'CANCELLED:REQCHANGE', 'IN-PROCESS:IMPLEMENTATION'),
            )
        ),

        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in Example Application.')
            self::DESCRIPTION           => 'Enabled Features in Example Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => TRUE,
            self::CONTENT               => [
                self::EXAMPLE_FEATURE       => [
                    self::LABEL                 => 'Invoices Module', //_('Invoices Module')
                    self::DESCRIPTION           => 'Invoices Module',
                    self::TYPE                  => self::TYPE_BOOL,
                    self::DEFAULT_STR           => true,
                ],
            ],
            self::DEFAULT_STR => [],
        ],

        self::EXAMPLE_STRING => array(
            //_('Example String')
            self::LABEL              => 'Example String',
            //_('Just an example string for test purpose')
            self::DESCRIPTION        => 'Just an example string for test purpose',
            self::TYPE               => 'string',
            self::DEFAULT_STR        => self::EXAMPLE_STRING,
        )
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'ExampleApplication';
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::getProperties()
     */
    public static function getProperties()
    {
        return self::$_properties;
    }
}
