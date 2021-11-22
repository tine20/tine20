<?php
/**
 * Tine 2.0

 * @package     Sales
 * @subpackage  Address
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold Address data
 *
 * @package     Sales
 * @subpackage  Address
 */
class Sales_Model_Address extends Tinebase_Record_NewAbstract
{
    /**
     * holds the configuration object (must be set in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject;

    public const FLD_CUSTOMER_ID = 'customer_id';
    public const FLD_NAME = 'name';
    public const FLD_EMAIL = 'email';
    public const FLD_STREET = 'street';
    public const FLD_POBOX = 'pobox';
    public const FLD_POSTALCODE = 'postalcode';
    public const FLD_LOCALITY = 'locality';
    public const FLD_REGION = 'region';
    public const FLD_COUNTRYNAME = 'countryname';
    public const FLD_PREFIX1 = 'prefix1';
    public const FLD_PREFIX2 = 'prefix2';
    public const FLD_CUSTOM1 = 'custom1';
    public const FLD_TYPE = 'type';
    public const FLD_FULLTEXT = 'fulltext';
    
    public const TYPE_POSTAL = 'postal';
    public const TYPE_BILLING = 'billing';
    public const TYPE_DELIVERY = 'delivery';
    
    public const MODEL_NAME_PART = 'Address';
    public const TABLE_NAME = 'sales_addresses';
                        
    
    /**
     * Holds the model configuration
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION   => 3,
        self::RECORD_NAME   => 'Address', // ngettext('Address', 'Addresss', n)
        self::RECORDS_NAME  => 'Addresss', // gettext('GENDER_Address')
        self::HAS_RELATIONS => true,
        self::HAS_CUSTOM_FIELDS => true,
        self::HAS_NOTES => false,
        self::HAS_TAGS => false,
        self::MODLOG_ACTIVE => true,
        self::CONTAINER_PROPERTY    => NULL,
        self::CREATE_MODULE => FALSE,
        self::IS_DEPENDENT  => TRUE,
        self::TITLE_PROPERTY => self::FLD_FULLTEXT,
        self::APP_NAME => Sales_Config::APP_NAME,
        self::MODEL_NAME => self::MODEL_NAME_PART,
        self::EXPOSE_JSON_API => true,
        'resolveRelated'  => TRUE,
        'defaultFilter'   => 'query',
        'resolveVFGlobally' => TRUE,

        self::TABLE => [
            self::NAME => self::TABLE_NAME,
        ],

        self::FIELDS          => [
            self::FLD_CUSTOMER_ID       => [
                self::LABEL      => 'Customer',    // _('Customer')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => FALSE],
                self::TYPE       => self::TYPE_RECORD,
                'sortable'   => FALSE,
                self::CONFIG => [
                    self::APP_NAME     => 'Sales',
                    self::MODEL_NAME   => 'Customer',
                    'idProperty'  => 'id',
                    'isParent'    => TRUE
                ]
            ],
            self::FLD_NAME => [
                self::LABEL => 'Name', // _('Name')
                self::NULLABLE => true,
                self::TYPE => self::TYPE_STRING,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => true,
            ],
            self::FLD_EMAIL => [
                self::LABEL => 'Email', // _('Email')
                self::NULLABLE => true,
                self::TYPE => self::TYPE_STRING,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => true,
            ],
            self::FLD_STREET => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Street', //_('Street')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => TRUE,
                self::NULLABLE => TRUE,
            ],
            self::FLD_POBOX => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Postbox', //_('Postbox')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => TRUE,
                self::NULLABLE => TRUE,
            ],
            self::FLD_POSTALCODE => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Postalcode', //_('Postalcode')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => TRUE,
                self::NULLABLE => TRUE,
            ],
            self::FLD_LOCALITY => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Locality', //_('Locality')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => TRUE,
                self::NULLABLE => TRUE,
            ],
            self::FLD_REGION => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => 'Region', //_('Region')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => TRUE,
                self::NULLABLE => TRUE,
            ],
            self::FLD_COUNTRYNAME => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL   => 'Country', //_('Country')
                self::DEFAULT_VAL => 'DE',
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => TRUE,
                self::NULLABLE => TRUE,
            ],
            self::FLD_PREFIX1 => [
                self::LABEL   => 'Prefix', //_('Prefix')
                self::NULLABLE => TRUE,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_PREFIX2 => [
                self::LABEL   => 'Additional Prefix', //_('Additional Prefix')
                self::NULLABLE => TRUE,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_CUSTOM1 => [
                self::LABEL => 'Number Debit', //_('Number Debit')
                self::TYPE => self::TYPE_STRING,
                self::NULLABLE => TRUE,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER => TRUE
            ],
            self::FLD_TYPE => [
                self::TYPE => self::TYPE_STRING,
                self::LABEL => NULL,
                self::DEFAULT_VAL => 'postal',
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false],
                self::QUERY_FILTER => TRUE
            ],

            self::FLD_FULLTEXT => [
                'config' => [
                    'duplicateOmit' => TRUE,
                    self::LABEL   => NULL
                ],
                self::TYPE => self::TYPE_VIRTUAL,
            ],
        ]
    ];

    public function hydrateFromBackend(array &$data)
    {
        $data = Sales_Controller_Address::getInstance()->resolveVirtualFields($data);

        parent::hydrateFromBackend($data);
    }
}
