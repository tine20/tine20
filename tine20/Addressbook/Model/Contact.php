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
class Addressbook_Model_Contact extends Tinebase_Record_Abstract
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
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Addressbook';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        'containerName'     => 'Addressbook',
        'containersName'    => 'Addressbooks', // ngettext('Addressbook', 'Addressbooks', n)
        'recordName'        => 'Contact',
        'recordsName'       => 'Contacts', // ngettext('Contact', 'Contacts', n)
        'hasRelations'      => true,
        'copyRelations'     => false,
        'hasCustomFields'   => true,
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
        'table'             => array(
            'name'              => 'addressbook',
        ),

        'filterModel'       => [
            'id'                => [
                'filter'            => 'Addressbook_Model_ContactIdFilter',
                'options'           => [
                    'idProperty'        => 'id',
                    'modelName'         => 'Addressbook_Model_Contact'
                ]
            ],
            'showDisabled'      => [
                'filter'            => 'Addressbook_Model_ContactHiddenFilter',
                'title'             => 'Show Disabled', // _('Show Disabled') // TODO is this right?
                'options'           => [
                    'requiredCols'      => ['account_id' => 'accounts.id'],
                ],
                'jsConfig'          => ['filtertype' => 'addressbook.contactshowDisabled'] // TODO later with FE fix it
            ],
            'path'              => [
                'filter'            => 'Tinebase_Model_Filter_Path',
                'title'             => 'Path', // _('Path') // TODO is this right?
                'options'           => [],
                'jsConfig'          => ['filtertype' => 'addressbook.contactpath'] // TODO later with FE fix it
            ],
            'list'              => [
                'filter'            => 'Addressbook_Model_ListMemberFilter',
                'title'             => 'List Member', // _('List Member') // TODO is this right?
                'options'           => [],
                'jsConfig'          => ['filtertype' => 'addressbook.contactlist'] // TODO later with FE fix it
            ],
            'list_role_id'      => [
                'filter'            => 'Addressbook_Model_ListRoleMemberFilter',
                'title'             => 'List Role Member', // _('List Role Member') // TODO is this right?
                'options'           => [],
                'jsConfig'          => ['filtertype' => 'addressbook.contactlistroleid'] // TODO later with FE fix it
            ],
            'telephone'         => [
                'filter'            => 'Tinebase_Model_Filter_Query',
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
                'filter'            => 'Tinebase_Model_Filter_Query',
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
                'filter'            => 'Tinebase_Model_Filter_Query',
                'title'             => 'Email', // _('Email') // TODO is this right?
                'options'           => [
                    'fields'            => [
                        'email',
                        'email_home',
                    ]
                ],
                'jsConfig'          => ['filtertype' => 'addressbook.contactemail'] // TODO later with FE fix it
            ],
        ],

        /*
         * TODO what about that? no bday filter? why? see todo comment in contactFilter file!
         * current state: we have a DATETIME filter for bday, that what the modelconfig does
         * it makes things automatically
        //'bday'               => array('filter' => 'Tinebase_Model_Filter_Date'),
         */

        'fields'            => [
            'account_id'                    => [
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => null
                ],
            ],
            'adr_one_countryname'           => [
                'label'                         => 'Country', // _('Country')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class, Zend_Filter_StringToUpper::class],
            ],
            'adr_one_locality'              => [
                'label'                         => 'City', // _('City')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'queryFilter'                   => true,
            ],
            'adr_one_postalcode'            => [
                'label'                         => 'Postalcode', // _('Postalcode')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_region'                => [
                'label'                         => 'Region', // _('Region')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_street'                => [
                'label'                         => 'Street', // _('Street')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_street2'               => [
                'label'                         => 'Street 2', // _('Street 2')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_one_lon'                   => [
                'type'                          => 'float',
                'label'                         => 'Longitude', // _('Longitude')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_Empty::class => null],
            ],
            'adr_one_lat'                   => [
                'type'                          => 'float',
                'label'                         => 'Latitude', // _('Latitude')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_Empty::class => null],
            ],
            'adr_two_countryname'           => [
                'label'                         => 'Country (private)', // _('Country (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class, Zend_Filter_StringToUpper::class],
            ],
            'adr_two_locality'              => [
                'label'                         => 'City (private)', // _('City (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_postalcode'            => [
                'label'                         => 'Postalcode (private)', // _('Postalcode (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_region'                => [
                'label'                         => 'Region (private)', // _('Region (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_street'                => [
                'label'                         => 'Street (private)', // _('Street (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_street2'               => [
                'label'                         => 'Street 2 (private)', // _('Street 2 (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'adr_two_lon'                   => [
                'type'                          => 'float',
                'label'                         => 'Longitude (private)', // _('Longitude (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_Empty::class => null],
            ],
            'adr_two_lat'                   => [
                'type'                          => 'float',
                'label'                         => 'Latitude (private)', // _('Latitude (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_Empty::class => null],
            ],
            'assistent'                     => [
                'label'                         => 'Assistent', // _('Assistent')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'bday'                          => [
                'type'                          => 'datetime',
                'label'                         => 'Birthday', // _('Birthday')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'calendar_uri'                  => [
                'label'                         => 'Calendar URI', // _('Calendar URI')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'email'                         => [
                'label'                         => 'Email', // _('Email')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class, Zend_Filter_StringToLower::class],
                'queryFilter'                   => true,
            ],
            'email_home'                    => [
                'label'                         => 'Email (private)', // _('Email (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class, Zend_Filter_StringToLower::class],
                'queryFilter'                   => true,
            ],
            'freebusy_uri'                  => [
                'label'                         => 'Free/Busy URI', // _('Free/Busy URI')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'geo'                           => [
                'label'                         => 'Geo', // _('Geo')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'groups'                        => [
                'type'                          => 'virtual',
                'label'                         => 'Groups', // _('Groups')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'modlogOmit'                    => true,
            ],
            'industry'                      => [
                'label'                         => 'Industry', // _('Industry')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'filterDefinition'              => [
                    'filter'                        => Tinebase_Model_Filter_ForeignId::class,
                    'options'                       => [
                        'filtergroup'                   => Addressbook_Model_IndustryFilter::class,
                        'controller'                    => Addressbook_Controller_Industry::class
                    ]
                ]
            ],
            'jpegphoto'                     => [
                // this must not be of type 'text' => crlf filter must not be applied
                // => default type = string
                // TODO the SQL field is of course not varchar(255)... so...
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                ],
                'modlogOmit'                    => true,
                'system'                        => true
            ],
            'note'                          => [
                'type'                          => 'fulltext',
                'label'                         => 'Note', // _('Note')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'queryFilter'                   => true,
            ],
            'n_family'                      => [
                'label'                         => 'Last Name', // _('Last Name')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'queryFilter'                   => true,
            ],
            'n_fileas'                      => [
                'label'                         => 'Display Name', // _('Display Name')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_fn'                          => [
                'label'                         => 'Full Name', // _('Full Name')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_given'                       => [
                'label'                         => 'First Name', // _('First Name')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'queryFilter'                   => true,
            ],
            'n_middle'                      => [
                'label'                         => 'Middle Name', // _('Middle Name')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_prefix'                      => [
                'label'                         => 'Title', // _('Title')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_suffix'                      => [
                'label'                         => 'Suffix', // _('Suffix')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'n_short'                      => [
                'label'                         => 'Short Name', // _('Short Name')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'org_name'                      => [
                'label'                         => 'Company', // _('Company')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'queryFilter'                   => true,
            ],
            'org_unit'                      => [
                'label'                         => 'Unit', // _('Unit')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'queryFilter'                   => true,
            ],
            'paths'                         => [
                'type'                          => 'virtual',
                'label'                         => 'Paths', // _('Paths')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'preferred_address'             => [
                'label'                         => 'Preferred Address', // _('Preferred Address')
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => 0
                ],
                'inputFilters'                  => [Zend_Filter_Empty::class => 0],
            ],
            'pubkey'                        => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'role'                          => [
                'label'                         => 'Job Role', // _('Job Role')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'room'                          => [
                'label'                         => 'Room', // _('Room')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'salutation'                    => [
                'type'                          => 'keyfield',
                'label'                         => 'Salutation', // _('Salutation')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'name'                          => Addressbook_Config::CONTACT_SALUTATION,
            ],
            'syncBackendIds'                => [
                'label'                         => 'syncBackendIds', // _('syncBackendIds')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_assistent'                 => [
                'system'                        => true,
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_car'                       => [
                'label'                         => 'Car phone', // _('Car phone')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_cell'                      => [
                'label'                         => 'Mobile', // _('Mobile')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_cell_private'              => [
                'label'                         => 'Mobile (private)', // _('Mobile (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_fax'                       => [
                'label'                         => 'Fax', // _('Fax')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_fax_home'                  => [
                'label'                         => 'Fax (private)', // _('Fax (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_home'                      => [
                'label'                         => 'Phone (private)', // _('Phone (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_pager'                     => [
                'label'                         => 'Pager', // _('Pager')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_work'                      => [
                'label'                         => 'Phone', // _('Phone')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'tel_other'                     => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_prefer'                    => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_assistent_normalized'      => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_car_normalized'            => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_cell_normalized'           => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_cell_private_normalized'   => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_fax_normalized'            => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_fax_home_normalized'       => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_home_normalized'           => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_pager_normalized'          => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_work_normalized'           => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_other_normalized'          => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'tel_prefer_normalized'         => [
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'system'                        => true
            ],
            'title'                         => [
                'label'                         => 'Job Title', // _('Job Title')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'type'                          => [
                'label'                         => 'Type', // _('Type')
                'system'                        => true,
                'validators'                    => [
                    Zend_Filter_Input::ALLOW_EMPTY      => true,
                    Zend_Filter_Input::DEFAULT_VALUE    => self::CONTACTTYPE_CONTACT,
                    ['InArray', [self::CONTACTTYPE_USER, self::CONTACTTYPE_CONTACT]]
                ],
            ],
            'tz'                            => [
                'label'                         => 'Timezone', // _('Timezone')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
            ],
            'url'                           => [
                'label'                         => 'Web', // _('Web')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class],
            ],
            'url_home'                      => [
                'label'                         => 'URL (private)', // _('URL (private)')
                'validators'                    => [Zend_Filter_Input::ALLOW_EMPTY => true],
                'inputFilters'                  => [Zend_Filter_StringTrim::class],
            ],
        ]];


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
    public function setFromArray(array $_data)
    {
        $_data = $this->_resolveAutoValues($_data);
        parent::setFromArray($_data);
    }
    
    /**
     * Resolves the auto values n_fn and n_fileas
     * @param array $_data
     * @return array $_data
     */
    protected function _resolveAutoValues(array $_data)
    {
        if (! (isset($_data['org_name']) || array_key_exists('org_name', $_data))) {
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
            'n_fileas' => 255,
            'org_name' => 255,
            'n_given' => 64,
            'n_middle' => 64,
            'n_prefix' => 64,
            'n_suffix' => 64,
            'n_short' => 64,
         ] as $field => $allowedLength) {
            if (isset($_data[$field]) && mb_strlen($_data[$field]) > $allowedLength) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Field has been truncated: '
                        . $field . ' original data: ' . $_data[$field]);
                $_data[$field] = mb_substr($_data[$field], 0, $allowedLength);
            }
        }

        return $_data;
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
                $resolved = $this->_resolveAutoValues(array('n_given' => $_value, 'n_family' => $this->__get('n_family'), 'org_name' => $this->__get('org_name')));
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
                break;
            case 'n_family':
                $resolved = $this->_resolveAutoValues(array('n_family' => $_value, 'n_given' => $this->__get('n_given'), 'org_name' => $this->__get('org_name')));
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
                break;
            case 'org_name':
                $resolved = $this->_resolveAutoValues(array('org_name' => $_value, 'n_given' => $this->__get('n_given'), 'n_family' => $this->__get('n_family')));
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
        $this->_properties = array_intersect_key($this->_properties, [
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
}
