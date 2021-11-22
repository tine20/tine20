<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold sub product mapping data
 *
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_SubProductMapping extends Tinebase_Record_NewAbstract
{
    public const MODEL_NAME_PART = 'SubProductMapping';
    public const TABLE_NAME = 'sales_subproductmapping';

    public const FLD_SORTING = 'sorting';
    public const FLD_SHORTCUT = 'shortcut';
    public const FLD_PARENT_ID = 'parent_id';
    public const FLD_PRODUCT_ID = 'product_id';
    public const FLD_QUANTITY = 'quantity';
    public const FLD_VARIABLE_POSITION_FLAG = 'variable_position_flag';


    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION                   => 1,
        self::MODLOG_ACTIVE             => true,

        self::APP_NAME                  => Sales_Config::APP_NAME,
        self::MODEL_NAME                => self::MODEL_NAME_PART,

        self::RECORD_NAME               => 'Subproduct', // gettext('GENDER_Subproduct')
        self::RECORDS_NAME              => 'Subproducts', // ngettext('Subproduct', 'Subproducts', n)
        self::TITLE_PROPERTY            => self::FLD_SHORTCUT,

        self::HAS_DELETED_TIME_UNIQUE   => true,
        //self::HAS_ATTACHMENTS => true,
        //self::HAS_CUSTOM_FIELDS => true,
        //self::HAS_NOTES => false,
        //self::HAS_RELATIONS => true,
        //self::HAS_TAGS => true,

        //self::EXPOSE_HTTP_API => true,
        //self::EXPOSE_JSON_API => true,
        //self::CREATE_MODULE => true,

        self::TABLE                     => [
            self::NAME                      => self::TABLE_NAME,
            self::INDEXES                   => [
                self::FLD_PRODUCT_ID            => [
                    self::COLUMNS                   => [self::FLD_PRODUCT_ID],
                ],
            ],
            self::UNIQUE_CONSTRAINTS        => [
                self::FLD_PARENT_ID             => [
                    self::COLUMNS                   => [
                        self::FLD_PARENT_ID,
                        self::FLD_SHORTCUT,
                        self::FLD_DELETED_TIME,
                    ],
                ],
            ],
        ],

        self::ASSOCIATIONS              => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_PARENT_ID         => [
                    self::TARGET_ENTITY         => Sales_Model_Product::class,
                    self::FIELD_NAME            => self::FLD_PARENT_ID,
                    self::JOIN_COLUMNS          => [[
                        self::NAME                  => self::FLD_PARENT_ID,
                        self::REFERENCED_COLUMN_NAME=> 'id',
                    ]],
                    self::ON_DELETE             => self::CASCADE,
                ],
                self::FLD_PRODUCT_ID        => [
                    self::TARGET_ENTITY         => Sales_Model_Product::class,
                    self::FIELD_NAME            => self::FLD_PRODUCT_ID,
                    self::JOIN_COLUMNS          => [[
                        self::NAME                  => self::FLD_PRODUCT_ID,
                        self::REFERENCED_COLUMN_NAME=> 'id',
                    ]],
                    self::ON_DELETE             => self::CASCADE,
                ],
            ],
        ],

        self::FIELDS                    => [
            self::FLD_PRODUCT_ID            => [
                self::LABEL                     => 'Product', // _('Product')
                self::TYPE                      => self::TYPE_RECORD,
                self::CONFIG => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Product::MODEL_NAME_PART,
                    'additionalFilters'         => [['field' => Sales_Model_Product::FLD_UNFOLD_TYPE, 'operator' => 'equals', 'value' => null]]
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ]
            ],
            self::FLD_PARENT_ID             => [
                self::TYPE                      => self::TYPE_RECORD,
                self::DISABLED                  => true,
                self::CONFIG => [
                    self::APP_NAME              => Sales_Config::APP_NAME,
                    self::MODEL_NAME            => Sales_Model_Product::MODEL_NAME_PART,
                ],
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ]
            ],
            self::FLD_SHORTCUT              => [
                self::LABEL                     => 'Shortcut', // _('Shortcut')
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 25,
//                self::VALIDATORS                => [
//                    Zend_Filter_Input::ALLOW_EMPTY  => true,
//                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
//                ]
            ],
            self::FLD_VARIABLE_POSITION_FLAG         => [
                self::LABEL => 'Variable Position Flag', // _('Variable Position Flag')
                self::TYPE => self::TYPE_KEY_FIELD,
                self::DEFAULT_VAL => 'NONE',
                self::NAME => Sales_Config::VARIABLE_POSITION_FLAG,
            ],
            self::FLD_QUANTITY              => [
                self::LABEL                     => 'Amount', // _('Amount')
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 1,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY  => false,
                    Zend_Filter_Input::PRESENCE     => Zend_Filter_Input::PRESENCE_REQUIRED
                ]
            ],
            self::FLD_SORTING => [
                self::LABEL                     => 'Sorting', // _('Sorting')
                self::TYPE                      => self::TYPE_INTEGER,
                self::DEFAULT_VAL               => 10000,
                self::NULLABLE                  => true,
            ]
        ]
    ];

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;
}
