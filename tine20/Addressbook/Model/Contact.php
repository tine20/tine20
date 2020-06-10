<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contact data
 * 
 * @package     Addressbook
 * @subpackage  Model
 *
 * @property    string $account_id                 id of associated user
 * @property    string $adr_one_countryname        name of the country the contact lives in
 * @property    string $adr_one_locality           locality of the contact
 * @property    string $adr_one_postalcode         postalcode belonging to the locality
 * @property    string $adr_one_region             region the contact lives in
 * @property    string $adr_one_street             street where the contact lives
 * @property    string $adr_one_street2            street2 where contact lives
 * @property    string $adr_one_lon
 * @property    string $adr_one_lat
 * @property    string $adr_two_countryname        second home/country where the contact lives
 * @property    string $adr_two_locality           second locality of the contact
 * @property    string $adr_two_postalcode         ostalcode belonging to second locality
 * @property    string $adr_two_region             second region the contact lives in
 * @property    string $adr_two_street             second street where the contact lives
 * @property    string $adr_two_street2            second street2 where the contact lives
 * @property    string $adr_two_lon
 * @property    string $adr_two_lat
 * @property    string $assistent                  name of the assistent of the contact
 * @property    datetime $bday                     date of birth of contact
 * @property    integer $container_id              id of container
 * @property    string $email                      the email address of the contact
 * @property    string $email_home                 the private email address of the contact
 * @property    string $jpegphoto                    photo of the contact
 * @property    string $n_family                   surname of the contact
 * @property    string $n_fileas                   display surname, name
 * @property    string $n_fn                       the full name
 * @property    string $n_given                    forename of the contact
 * @property    string $n_middle                   middle name of the contact
 * @property    string $note                       notes of the contact
 * @property    string $n_prefix
 * @property    string $n_suffix
 * @property    string $org_name                   name of the company the contact works at
 * @property    string $org_unit
 * @property    string $role                       type of role of the contact
 * @property    string $tel_assistent              phone number of the assistent
 * @property    string $tel_car
 * @property    string $tel_cell                   mobile phone number
 * @property    string $tel_cell_private           private mobile number
 * @property    string $tel_fax                    number for calling the fax
 * @property    string $tel_fax_home               private fax number
 * @property    string $tel_home                   telephone number of contact's home
 * @property    string $tel_pager                  contact's pager number
 * @property    string $tel_work                   contact's office phone number
 * @property    string $title                      special title of the contact
 * @property    string $type                       type of contact
 * @property    string $url                        url of the contact
 * @property    string $salutation                 Salutation
 * @property    string $url_home                   private url of the contact
 * @property    integer $preferred_address         defines which is the preferred address of a contact, 0: business, 1: private
 */
class Addressbook_Model_Contact extends Tinebase_Record_NewAbstract
{
    /**
     * const to describe contact of current account id independent
     * 
     * @var string
     */
    const CURRENTCONTACT = 'currentContact';
    
    /**
     * contact type: contact
     * 
     * @var string
     */
    const CONTACTTYPE_CONTACT = 'contact';
    
    /**
     * contact type: user
     * 
     * @var string
     */
    const CONTACTTYPE_USER = 'user';

    /**
     * small contact photo size
     *
     * @var integer
     */
    const SMALL_PHOTO_SIZE = 36000;

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    public static $doResolveAttenderCleanUp = true;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION       => 26,
        'containerName'     => 'Addressbook',
        'containersName'    => 'Addressbooks', // ngettext('Addressbook', 'Addressbooks', n)
        'recordName'        => 'Contact',
        'recordsName'       => 'Contacts', // ngettext('Contact', 'Contacts', n)
        'hasRelations'      => true,
        'copyRelations'     => false,
        'hasCustomFields'   => true,
        'hasSystemCustomFields' => true,
        'hasNotes'          => true,
        'hasTags'           => true,
        'modlogActive'      => true,
        'hasAttachments'    => true,
        'createModule'      => true,
        'exposeHttpApi'     => true,
        'exposeJsonApi'     => true,
        'containerProperty' => 'container_id',
        'multipleEdit'      => true,

        'titleProperty'     => 'n_fn',
        'appName'           => 'Addressbook',
        'modelName'         => 'Contact',
        self::TABLE         => [
            self::NAME          => 'addressbook',
            self::INDEXES       => [
                'cat_id'                    => [
                    self::COLUMNS               => ['cat_id'],
                ],
                'container_id_index'        => [
                    self::COLUMNS               => ['container_id'],
                ],
                'type'                      => [
                    self::COLUMNS               => ['type'],
                ],
                'n_given_n_family'          => [
                    self::COLUMNS               => ['n_given', 'n_family'],
                ],
                'n_fileas'                  => [
                    self::COLUMNS               => ['n_fileas'],
                ],
                'n_family_n_given'          => [
                    self::COLUMNS               => ['n_family', 'n_given'],
                ],
                'note'                      => [
                    self::COLUMNS               => ['note'],
                    self::FLAGS                 => ['fulltext'],
                ],
            ],
        ],

        'associations' => [
            \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE => [
                'container_id_fk' => [
                    'targetEntity' => Tinebase_Model_Container::class,
                    'fieldName' => 'container_id',
                    'joinColumns' => [[
                        'name' => 'container_id',
                        'referencedColumnName'  => 'id'
                    ]],
                ],
            ],
        ],

        'filterModel'       => [
            'id'                => [
                'filter'            => Addressbook_Model_ContactIdFilter::class,
                'options'           => [
                    'idProperty'        => 'id',
                    'modelName'         => 'Addressbook_Model_Contact'
                ]
            ],
            'showDisabled'      => [
                'filter'            => Addressbook_Model_ContactHiddenFilter::class,
                'title'             => 'Show Disabled', // _('Show Disabled') // TODO is this right?
                'options'           => [
                    'requiredCols'      => ['account_id' => 'accounts.id'],
                ],
                'jsConfig'          => ['filtertype' => 'addressbook.contactshowDisabled'] // TODO later with FE fix it
            ],
            'path'              => [
                'filter'            => Tinebase_Model_Filter_Path::class,
                'title'             => 'Path', // _('Path') // TODO is this right?
                'options'           => [],
                'jsConfig'          => ['filtertype' => 'addressbook.contactpath'] // TODO later with FE fix it
            ],
            'list'              => [
                'filter'            => Addressbook_Model_ListMemberFilter::class,
                'title'             => 'List Member', // _('List Member') // TODO is this right?
                'options'           => [],
                'jsConfig'          => ['filtertype' => 'addressbook.contactlist'] // TODO later with FE fix it
            ],
            'list_role_id'      => [
                'filter'            => Addressbook_Model_ListRoleMemberFilter::class,
                'title'             => 'List Role Member', // _('List Role Member') // TODO is this right?
                'options'           => [],
                'jsConfig'          => ['filtertype' => 'addressbook.contactlistroleid'] // TODO later with FE fix it
            ],
            'telephone'         => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'title'             => 'Telephone', // _('Telephone') // TODO is this right?
                'options'           => [
                    'fields'            => [
                        'tel_assistent',
                        'tel_car',
                        'tel_cell',
                        'tel_cell_private',
                        'tel_fax',
                        'tel_fax_home',
                        'tel_home',
                        'tel_other',
                        'tel_pager',
                        'tel_prefer',
                        'tel_work'
                    ]
                ],
                'jsConfig'          => ['filtertype' => 'addressbook.contacttelephone'] // TODO later with FE fix it
            ],
            'telephone_normalized' => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'title'             => 'Telephone Normalized', // _('Telephone Normalized') // TODO is this right?
                'options'           => [
                    'fields'            => [
                        'tel_assistent_normalized',
                        'tel_car_normalized',
                        'tel_cell_normalized',
                        'tel_cell_private_normalized',
                        'tel_fax_normalized',
                        'tel_fax_home_normalized',
                        'tel_home_normalized',
                        'tel_other_normalized',
                        'tel_pager_normalized',
                        'tel_prefer_normalized',
                        'tel_work_normalized'
                    ]
                ],
                'jsConfig'          => ['filtertype' => 'addressbook.contacttelephoneNormalized'] // TODO later with FE fix it
            ],
            'email_query'       => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'title'             => 'Email', // _('Email')
                'options'           => [
                    'fields'            => [
                        'email',
                        'email_home',
                    ]
                ],
                'jsConfig'          => ['filtertype' => 'addressbook.contactemail'] // TODO later with FE fix it
            ],
            'name_email_query'       => [
                'filter'            => Tinebase_Model_Filter_Query::class,
                'title'             => 'Name/Email', // _('Name/Email')
                'options'           => [
                    'fields'            => [
                        'n_family',
                        'n_given',
                        'n_middle',
                        'org_name',
                        'email',
                        'email_home',
                    ]
                ],
            ],
        ],

        self::FIELDS        => [
            'account_id'                    => [
                self::TYPE                      => self::TYPE_STRING, // self::TYPE_USER....
                self::IS_VIRTUAL                => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => null
                ],
            ],
            'adr_one_countryname'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Country', // _('Country')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToUpper::class],
            ],
            'adr_one_locality'              => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'City', // _('City')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'adr_one_postalcode'            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Postalcode', // _('Postalcode')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_region'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Region', // _('Region')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_street'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street', // _('Street')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_street2'               => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street 2', // _('Street 2')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_lon'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Longitude', // _('Longitude')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
            ],
            'adr_one_lat'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Latitude', // _('Latitude')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
            ],
            'adr_two_countryname'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Country (private)', // _('Country (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToUpper::class],
            ],
            'adr_two_locality'              => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'City (private)', // _('City (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_postalcode'            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Postalcode (private)', // _('Postalcode (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_region'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Region (private)', // _('Region (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_street'                => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street (private)', // _('Street (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_street2'               => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Street 2 (private)', // _('Street 2 (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_lon'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Longitude (private)', // _('Longitude (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
            ],
            'adr_two_lat'                   => [
                self::TYPE                      => self::TYPE_FLOAT,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Latitude (private)', // _('Latitude (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => null],
            ],
            'assistent'                     => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Assistent', // _('Assistent')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'bday'                          => [
                self::TYPE                      => 'datetime',
                self::NULLABLE                  => true,
                self::LABEL                     => 'Birthday', // _('Birthday')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'calendar_uri'                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Calendar URI', // _('Calendar URI')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'email'                         => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Email', // _('Email')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToLower::class],
                self::QUERY_FILTER              => true,
            ],
            'email_home'                    => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Email (private)', // _('Email (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class, Zend_Filter_StringToLower::class],
                self::QUERY_FILTER              => true,
            ],
            'freebusy_uri'                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Free/Busy URI', // _('Free/Busy URI')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'geo'                           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 32,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Geo', // _('Geo')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'groups'                        => [
                self::TYPE                      => 'virtual',
                self::LABEL                     => 'Groups', // _('Groups')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::OMIT_MOD_LOG              => true,
            ],
            'industry'                      => [
                self::TYPE                      => self::TYPE_STRING, // TODO make a record out of it?
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Industry', // _('Industry')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::FILTER_DEFINITION         => [
                    self::FILTER                    => Tinebase_Model_Filter_ForeignId::class,
                    self::OPTIONS                   => [
                        self::FILTER_GROUP              => Addressbook_Model_IndustryFilter::class,
                        self::CONTROLLER                => Addressbook_Controller_Industry::class
                    ]
                ]
            ],
            'jpegphoto'                     => [
                self::TYPE                      => self::TYPE_VIRTUAL,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
                self::OMIT_MOD_LOG              => true,
                self::SYSTEM                    => true,
            ],
            'note'                          => [
                self::TYPE                      => self::TYPE_FULLTEXT,
                self::LENGTH                    => 2147483647, // mysql longtext, really?!?
                self::NULLABLE                  => true,
                self::LABEL                     => 'Note', // _('Note')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'n_family'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Last Name', // _('Last Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'n_fileas'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Display Name', // _('Display Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_fn'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Full Name', // _('Full Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_given'                       => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'First Name', // _('First Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'n_middle'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Middle Name', // _('Middle Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_prefix'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Title', // _('Title')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_suffix'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Suffix', // _('Suffix')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_short'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Short Name', // _('Short Name')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'org_name'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Company', // _('Company')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'org_unit'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Unit', // _('Unit')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::QUERY_FILTER              => true,
            ],
            'paths'                         => [
                'type'                          => 'records',
                self::IS_VIRTUAL                => true,
                'noResolve'                     => true,
                'config'                        => [
                    'appName' => 'Tinebase',
                    'modelName' => 'Path',
                    'recordClassName' => Tinebase_Model_Path::class,
                    'controllerClassName' => Tinebase_Record_Path::class,
                    'filterClassName' => Tinebase_Model_PathFilter::class,
                ],
                'label'                         => 'Paths', // _('Paths')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'preferred_address'             => [
                self::TYPE                      => self::TYPE_INTEGER,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Preferred Address', // _('Preferred Address')
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => 0
                ],
                self::INPUT_FILTERS             => [Zend_Filter_Empty::class => 0],
            ],
            'pubkey'                        => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 2147483647, // mysql longtext, really?!?
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [],
            ],
            'role'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Job Role', // _('Job Role')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'room'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Room', // _('Room')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'salutation'                    => [
                self::TYPE                      => self::TYPE_KEY_FIELD,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Salutation', // _('Salutation')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::NAME                      => Addressbook_Config::CONTACT_SALUTATION,
            ],
            'syncBackendIds'                => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 16000,
                self::NULLABLE                  => true,
                self::LABEL                     => 'syncBackendIds', // _('syncBackendIds')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [],
                self::SYSTEM                    => true
            ],
            'tel_assistent'                 => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::SYSTEM                    => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_car'                       => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Car phone', // _('Car phone')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_cell'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Mobile', // _('Mobile')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_cell_private'              => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Mobile (private)', // _('Mobile (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_fax'                       => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Fax', // _('Fax')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_fax_home'                  => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Fax (private)', // _('Fax (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_home'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Phone (private)', // _('Phone (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_pager'                     => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Pager', // _('Pager')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_work'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Phone', // _('Phone')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_other'                     => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_prefer'                    => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_assistent_normalized'      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_car_normalized'            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_cell_normalized'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_cell_private_normalized'   => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_fax_normalized'            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_fax_home_normalized'       => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_home_normalized'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_pager_normalized'          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_work_normalized'           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_other_normalized'          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_prefer_normalized'         => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'title'                         => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 86,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Job Title', // _('Job Title')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'type'                          => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::LABEL                     => 'Type', // _('Type')
                self::SYSTEM                    => true,
                self::VALIDATORS                => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => self::CONTACTTYPE_CONTACT,
                    ['InArray', [self::CONTACTTYPE_USER, self::CONTACTTYPE_CONTACT]]
                ],
                self::DEFAULT_VAL                  => 'contact', // TODO check if this works!=?!?

            ],
            'tz'                            => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 8,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Timezone', // _('Timezone')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'url'                           => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::NULLABLE                  => true,
                self::LABEL                     => 'Web', // _('Web')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class],
            ],
            'url_home'                      => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 128,
                self::NULLABLE                  => true,
                self::LABEL                     => 'URL (private)', // _('URL (private)')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::INPUT_FILTERS             => [Zend_Filter_StringTrim::class],
            ],


            // do we want to remove those?
            'label'                         => [
                self::TYPE                      => self::TYPE_TEXT,
                self::LENGTH                    => 2147483647, // mysql longtext, really?!?
                self::NULLABLE                  => true,
                self::LABEL                     => 'Label', // _('Label')
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'cat_id'                        => [
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 255,
                self::NULLABLE                  => true,
                self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],

        ],

        self::DB_COLUMNS                => [
            'tid'                           => [
                'fieldName'                     => 'tid',
                self::TYPE                      => self::TYPE_STRING,
                self::LENGTH                    => 1,
                self::NULLABLE                  => true,
                //self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL               => 'n', // TODO check if this works!=?!?
            ],
            'private'                       => [
                'fieldName'                     => 'private',
                self::TYPE                      => self::TYPE_BOOLEAN,
                self::LENGTH                    => 1,
                self::NULLABLE                  => true,
                //self::VALIDATORS                => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::DEFAULT_VAL               => 0, // TODO check if this works!=?!?
            ],
        ],
    ];


    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format: 
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     * 
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User'        => array('created_by', 'last_modified_by'),
        'Addressbook_Model_Industry' => array('industry'),
        'recursive'                  => array('attachments' => 'Tinebase_Model_Tree_Node'),
        'Addressbook_Model_List' => array('groups'),
    );

    /**
     * name of fields which require manage accounts to be updated
     *
     * @var array list of fields which require manage accounts to be updated
     */
    protected static $_manageAccountsFields = array(
        'email',
        'n_fileas',
        'n_fn',
        'n_given',
        'n_family',
    );

    /**
     * @return array
     */
    static public function getManageAccountFields()
    {
        return self::$_manageAccountsFields;
    }

    /**
     * returns prefered email address of given contact
     * 
     * @return string
     */
    public function getPreferredEmailAddress()
    {
        // prefer work mail over private mail till we have prefs for this
        return $this->email ? $this->email : $this->email_home;
    }
    
    /**
     * @see Tinebase_Record_Abstract::setFromArray
     *
     * @param array $_data            the new data to set
     */
    public function setFromArray(array &$_data)
    {
        $this->_resolveAutoValues($_data);
        parent::setFromArray($_data);
    }

    public function hydrateFromBackend(array &$_data)
    {
        $this->_resolveAutoValues($_data);
        parent::hydrateFromBackend($_data);
    }
    /**
     * Resolves the auto values n_fn and n_fileas
     * @param array $_data
     */
    protected function _resolveAutoValues(array &$_data)
    {
        if (! (isset($_data['org_name']) || array_key_exists('org_name', $_data))) {
            // we might want to set it to null instead?
            $_data['org_name'] = '';
        }

        // try to guess name from n_fileas
        // TODO: n_fn
        if (empty($_data['org_name']) && empty($_data['n_family'])) {
            if (! empty($_data['n_fileas'])) {
                $names = preg_split('/\s*,\s*/', $_data['n_fileas']);
                $_data['n_family'] = $names[0];
                if (empty($_data['n_given'])&& isset($names[1])) {
                    $_data['n_given'] = $names[1];
                }
            }
        }
        
        // always update fileas and fn
        $_data['n_fileas'] = (!empty($_data['n_family'])) ? $_data['n_family']
            : ((! empty($_data['org_name'])) ? $_data['org_name']
            : ((isset($_data['n_fileas'])) ? $_data['n_fileas'] : ''));

        if (!empty($_data['n_given'])) {
            $_data['n_fileas'] .= ', ' . $_data['n_given'];
        }

        $_data['n_fn'] = (!empty($_data['n_family'])) ? $_data['n_family']
            : ((! empty($_data['org_name'])) ? $_data['org_name']
            : ((isset($_data['n_fn'])) ? $_data['n_fn'] : ''));

        if (!empty($_data['n_given'])) {
            $_data['n_fn'] = $_data['n_given'] . ' ' . $_data['n_fn'];
        }

        // truncate some values if too long
        // TODO add generic code for this? maybe it should be configurable
        foreach ([
            'n_fn' => 255,
            'n_family' => 255,
            'n_fileas' => 255,
            'org_name' => 255,
            'n_given' => 86,
            'n_middle' => 86,
            'n_prefix' => 86,
            'n_suffix' => 86,
            'n_short' => 86,
         ] as $field => $allowedLength) {
            if (isset($_data[$field]) && mb_strlen($_data[$field]) > $allowedLength) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Field has been truncated: '
                        . $field . ' original data: ' . $_data[$field]);
                $_data[$field] = mb_substr($_data[$field], 0, $allowedLength);
            }
        }
    }
    
    /**
     * Overwrites the __set Method from Tinebase_Record_Abstract
     * Also sets n_fn and n_fileas when org_name, n_given or n_family should be set
     * @see Tinebase_Record_Abstract::__set()
     * @param string $_name of property
     * @param mixed $_value of property
     */
    public function __set($_name, $_value) {
        
        switch ($_name) {
            case 'n_given':
                $resolved = array('n_given' => $_value, 'n_family' => $this->__get('n_family'), 'org_name' => $this->__get('org_name'));
                $this->_resolveAutoValues($resolved);
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
                break;
            case 'n_family':
                $resolved = array('n_family' => $_value, 'n_given' => $this->__get('n_given'), 'org_name' => $this->__get('org_name'));
                $this->_resolveAutoValues($resolved);
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
                break;
            case 'org_name':
                $resolved = array('org_name' => $_value, 'n_given' => $this->__get('n_given'), 'n_family' => $this->__get('n_family'));
                $this->_resolveAutoValues($resolved);
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
                break;
            default:
                // normalize telephone numbers
                if (strpos($_name, 'tel_') === 0 && strpos($_name, '_normalized') === false) {
                    parent::__set($_name . '_normalized', (empty($_value)? $_value : static::normalizeTelephoneNum($_value)));
                }
                break;
        }
        
        parent::__set($_name, $_value);
    }

    /**
     * normalizes telephone numbers and eventually adds missing country part (using configured default country code)
     * result will be of format +y[y][y]xxxxxxx (only digits, y country code)
     *
     * @param  string $telNumber
     * @return string|null
     */
    public static function normalizeTelephoneNum($telNumber, $additionalAllowedChars = '')
    {
        $telNumber = preg_replace('/[^\d\+()' . $additionalAllowedChars . ']/u', '', $telNumber);

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $numberFormat = $phoneUtil->parse($telNumber, Addressbook_Config::getInstance()
                ->{Addressbook_Config::DEFAULT_TEL_COUNTRY_CODE});
            return $phoneUtil->format($numberFormat, \libphonenumber\PhoneNumberFormat::E164);
        } catch (Exception $e) {}

        return null;
    }

    public function getMsisdn()
    {
        foreach (['tel_cell_normalized', 'tel_cell_private_normalized','tel_car_normalized'] as $property) {
            if (strlen($this->{$property}) > 0) {
                return substr(ltrim(ltrim($this->{$property}, '0'), '+'), 0, 15);
            }
        }
    }

    /**
     * fills a contact from json data
     *
     * @param array $_data record data
     * @return void
     * 
     * @todo timezone conversion for birthdays?
     * @todo move this to Addressbook_Convert_Contact_Json
     */
    protected function _setFromJson(array &$_data)
    {
        $this->_setContactImage($_data);
        
        // unset if empty
        // @todo is this still needed?
        if (empty($_data['id'])) {
            unset($_data['id']);
        }
    }
    
    /**
     * set contact image
     * 
     * @param array $_data
     */
    protected function _setContactImage(&$_data)
    {
        if (! isset($_data['jpegphoto']) || $_data['jpegphoto'] === '') {
            return;
        }
        
        $imageParams = Tinebase_ImageHelper::parseImageLink($_data['jpegphoto']);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' image params:' . print_r($imageParams, true));
        if ($imageParams['isNewImage']) {
            try {
                $_data['jpegphoto'] = Tinebase_ImageHelper::getImageData($imageParams);
            } catch(Tinebase_Exception_UnexpectedValue $teuv) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not add contact image: ' . $teuv->getMessage());
                unset($_data['jpegphoto']);
            }
        } else {
            unset($_data['jpegphoto']);
        }
    }

    /**
     * set small contact image
     *
     * @param $newPhotoBlob
     * @param $maxSize
     */
    public function setSmallContactImage($newPhotoBlob, $maxSize = self::SMALL_PHOTO_SIZE)
    {
        if ($this->getId()) {
            try {
                $currentPhoto = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId())->getBlob('image/jpeg', $maxSize);
            } catch (Exception $e) {
                // no current photo
            }
        }

        if (isset($currentPhoto) && $currentPhoto == $newPhotoBlob) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__
                . " Photo did not change -> preserving current photo");
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__
                . " Setting new contact photo (" . strlen($newPhotoBlob) . "KB)");
            $this->jpegphoto = $newPhotoBlob;
        }
    }

    /**
     * return small contact image for sync
     *
     * @param $maxSize
     *
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getSmallContactImage($maxSize = self::SMALL_PHOTO_SIZE)
    {
        $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId());
        return $image->getBlob('image/jpeg', $maxSize);
    }

    /**
     * get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->n_fn;
    }

    /**
     * returns an array containing the parent neighbours relation objects or record(s) (ids) in the key 'parents'
     * and containing the children neighbours in the key 'children'
     *
     * @return array
     */
    public function getPathNeighbours()
    {
        $listController = Addressbook_Controller_List::getInstance();
        $oldAclCheck = $listController->doContainerACLChecks(false);

        $lists = $listController->search(new Addressbook_Model_ListFilter(array(
            array('field' => 'contact',     'operator' => 'equals', 'value' => $this->getId())
        )));

        $listMemberRoles = $listController->getMemberRolesBackend()->search(new Addressbook_Model_ListMemberRoleFilter(array(
            array('field' => 'contact_id',  'operator' => 'equals', 'value' => $this->getId())
        )));

        /** @var Addressbook_Model_ListMemberRole $listMemberRole */
        foreach($listMemberRoles as $listMemberRole) {
            $lists->removeById($listMemberRole->list_id);
        }

        $result = parent::getPathNeighbours();
        $result['parents'] = array_merge($result['parents'], $lists->asArray(), $listMemberRoles->asArray());

        $listController->doContainerACLChecks($oldAclCheck);

        return $result;
    }

    /**
     * @return bool
     */
    public static function generatesPaths()
    {
        return true;
    }

    /**
     * moved here from vevent converter -> @TODO improve me
     *
     * @param $fullName
     * @return array
     */
    public static function splitName($fullName)
    {
        if (preg_match('/(?P<firstName>\S*) (?P<lastNameName>\S*)/', $fullName, $matches)) {
            $firstName = $matches['firstName'];
            $lastName  = $matches['lastNameName'];
        } else {
            $firstName = null;
            $lastName  = $fullName;
        }

        return [
            'n_given' => $firstName,
            'n_family' => $lastName
        ];
    }

    public function resolveAttenderCleanUp()
    {
        if (!static::$doResolveAttenderCleanUp) {
            return;
        }

        $this->_data = array_intersect_key($this->_data, [
            'id'          => true,
            'note'        => true,
            'email'       => true,
            'n_family'    => true,
            'n_given'     => true,
            'n_fileas'    => true,
            'n_fn'        => true,
            'n_short'     => true,
            'account_id'  => true,
        ]);
    }

    public function unsetFieldsBeforeConvertingToJson()
    {
        parent::unsetFieldsBeforeConvertingToJson();

        unset($this->jpegphoto);
    }
}
