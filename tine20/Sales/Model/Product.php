<?php

/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Product
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold product data
 * 
 * @package     Sales
 * @subpackage  Product
 */
class Sales_Model_Product extends Tinebase_Record_NewAbstract
{
    public const FLD_ACCOUNTABLE = 'accountable';
    public const FLD_CATEGORY = 'category';
    public const FLD_DESCRIPTION = 'description';
    public const FLD_GTIN = 'gtin';
    public const FLD_IS_ACTIVE = 'is_active';
    public const FLD_LIFESPAN_END = 'lifespan_end';
    public const FLD_LIFESPAN_START = 'lifespan_start';
    public const FLD_MANUFACTURER = 'manufacturer';
    public const FLD_NAME = 'name';
    public const FLD_NUMBER = 'number';
    public const FLD_PURCHASEPRICE = 'purchaseprice';
    public const FLD_SALESPRICE = 'salesprice';

    public const MODEL_NAME_PART = 'Product';
    public const TABLE_NAME = 'sales_products';

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION => 7,
        self::MODLOG_ACTIVE => true,

        self::APP_NAME => Sales_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,

        self::RECORD_NAME => 'Product',
        self::RECORDS_NAME => 'Products', // ngettext('Product', 'Products', n)
        self::TITLE_PROPERTY => self::FLD_NAME,

        self::HAS_ATTACHMENTS => true,
        self::HAS_CUSTOM_FIELDS => true,
        self::HAS_NOTES => false,
        self::HAS_RELATIONS => true,
        self::HAS_TAGS => true,

        self::EXPOSE_HTTP_API => true,
        self::EXPOSE_JSON_API => true,
        self::CREATE_MODULE => true,

        self::DEFAULT_SORT_INFO => ['field' => 'number', 'direction' => 'DESC'],

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
            self::INDEXES       => [
                self::FLD_DESCRIPTION => [
                    self::COLUMNS               => [self::FLD_DESCRIPTION],
                    self::FLAGS                 => [self::TYPE_FULLTEXT],
                ],
            ],
        ],

        self::FIELDS => [
            self::FLD_NUMBER => [
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
                self::LABEL => 'Number', // _('Number')
                self::LENGTH => 64,
            ],
            self::FLD_NAME => [
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
                self::LABEL => 'Name', // _('Name')
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::PRESENCE => Zend_Filter_Input::PRESENCE_REQUIRED
                ]
            ],
            self::FLD_DESCRIPTION => [
                self::TYPE => self::TYPE_FULLTEXT,
                self::QUERY_FILTER => true,
                self::LABEL => 'Description', // _('Description')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_PURCHASEPRICE => [
                self::TYPE => self::TYPE_MONEY,
                self::LABEL => 'Purchaseprice', // _('Purchaseprice')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
                self::DEFAULT_VAL => 0,
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => 0]
            ],
            self::FLD_SALESPRICE => [
                self::TYPE => self::TYPE_MONEY,
                self::LABEL => 'Salesprice', // _('Salesprice')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => false,
                    Zend_Filter_Input::DEFAULT_VALUE => 0
                ],
                self::DEFAULT_VAL => 0,
                self::INPUT_FILTERS => [Zend_Filter_Empty::class => 0]
            ],
            self::FLD_CATEGORY => [
                self::TYPE => self::TYPE_KEY_FIELD,
                self::LABEL => 'Category', // _('Category')
                self::DEFAULT_VAL => 'DEFAULT',
                self::NAME => Sales_Config::PRODUCT_CATEGORY,
                self::NULLABLE => true,
            ],
            self::FLD_MANUFACTURER => [
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
                self::LABEL => 'Manufacturer', // _('Manufacturer')
                self::NULLABLE => true,
                self::LENGTH => 255,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            // TODO should be a keyfield or record
            self::FLD_ACCOUNTABLE => [
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
                self::LABEL => 'Accountable', // _('Accountable')
                self::NULLABLE => true,
                self::LENGTH => 40,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_GTIN => [
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
                self::LABEL => 'GTIN', // _('GTIN')
                self::NULLABLE => true,
                self::LENGTH => 64,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_LIFESPAN_START => [
                self::TYPE => self::TYPE_DATETIME,
                self::LABEL => 'Lifespan start', // _('Lifespan start')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_LIFESPAN_END => [
                self::TYPE => self::TYPE_DATETIME,
                self::LABEL => 'Lifespan end', // _('Lifespan end')
                self::NULLABLE => true,
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                ]
            ],
            self::FLD_IS_ACTIVE => [
                self::TYPE => self::TYPE_BOOLEAN,
                self::LABEL => 'Is active', // _('Is active')
                self::VALIDATORS => [
                    Zend_Filter_Input::ALLOW_EMPTY => true,
                    Zend_Filter_Input::DEFAULT_VALUE => true
                ],
                self::DEFAULT_VAL => true,
            ],
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
