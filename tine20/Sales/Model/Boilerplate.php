<?php

/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Model for Boilerplates
 *
 * @package     Sales
 * @subpackage  Model
 *
 * @property string $name
 * @property string $listId
 */
class Sales_Model_Boilerplate extends Tinebase_Record_NewAbstract
{
    public const FLD_MODEL = 'model';
    public const FLD_NAME = 'name';
    public const FLD_FROM = 'from';
    public const FLD_UNTIL = 'until';
    public const FLD_DOCUMENT_CATEGORY = 'documentCategory';
    public const FLD_CUSTOMER = 'customer';
    public const FLD_DOCUMENT = 'document';
    public const FLD_BOILERPLATE = 'boilerplate';

    public const MODEL_NAME_PART = 'Boilerplate';
    public const TABLE_NAME = 'sales_boilerplate';
    
    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 1,
        self::MODLOG_ACTIVE => true,
        self::IS_DEPENDENT => true,

        self::APP_NAME => Sales_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => self::MODEL_NAME_PART,
        self::RECORDS_NAME => 'Boilerplates', // ngettext('Boilerplate', 'Boilerplates', n)
        self::TITLE_PROPERTY => self::FLD_NAME,

        self::HAS_RELATIONS => false,
        self::HAS_ATTACHMENTS => false,
        self::HAS_NOTES => false,
        self::HAS_TAGS => false,

        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => false,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::FIELDS => [
            self::FLD_MODEL => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 40,
                self::QUERY_FILTER => true,
                self::LABEL => 'Model', // _('Model')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_NAME => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 255,
                self::QUERY_FILTER => true,
                self::LABEL => 'Name', // _('Name')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ],
            self::FLD_FROM => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'From', // _('From')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                // filter config BEFORE_OR_IS_NULL
            ],
            self::FLD_UNTIL => [
                self::TYPE => self::TYPE_DATE,
                self::LABEL => 'Until', // _('Until')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                // filter config AFTER_OR_IS_NULL
            ],
            self::FLD_DOCUMENT_CATEGORY => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Document Category', // _('Document Category')
                self::DEFAULT_VAL => 'DEFAULT',
                self::NAME => Sales_Config::DOCUMENT_CATEGORY,
                self::NULLABLE => true,
            ],
            self::FLD_CUSTOMER => [
                self::TYPE => self::TYPE_RECORD,
                self::QUERY_FILTER => true,
                self::LABEL => 'Customer', // _('Customer')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null],
                'config' => array(
                    'appName'     => 'Sales',
                    'modelName'   => 'Customer',
                    'idProperty'  => 'id'
                )
            ],

            // denormalize like products, customer, address, etc.
            self::FLD_DOCUMENT => [
                self::TYPE => self::TYPE_STRING,
                self::LENGTH => 40,
                self::QUERY_FILTER => true,
                self::LABEL => 'Document', // _('Document')
                self::NULLABLE => true,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => null]
            ],
            self::FLD_BOILERPLATE        => [
                self::LABEL             => 'Boilerplate', //_('Boilerplate')
                self::TYPE          => self::TYPE_TEXT,
                self::LENGTH        => \Doctrine\DBAL\Platforms\MySqlPlatform::LENGTH_LIMIT_MEDIUMTEXT,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ],
            ]
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = null;
}
