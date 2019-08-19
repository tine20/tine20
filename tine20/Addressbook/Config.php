<?php
/**
 * @package     Addressbook
 * @subpackage  Config
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Addressbook config class
 * 
 * @package     Addressbook
 * @subpackage  Config
 */
class Addressbook_Config extends Tinebase_Config_Abstract
{

    /**
     * contact nominatim during contact import
     *
     * @var string
     */
    const CONTACT_IMPORT_NOMINATIM = 'contactImportNominatim';

    /**
     * fields for contact record duplicate check
     * 
     * @var string
     */
    const CONTACT_DUP_FIELDS = 'contactDupFields';

    /**
     * contact hidden
     *
     * @var string
     */
    const CONTACT_HIDDEN_CRITERIA = 'contactHiddenCriteria';

    /**
     * fields for contact salutations
     * 
     * @var string
     */
    const CONTACT_SALUTATION = 'contactSalutation';
    
    /**
     * fields for list type
     *
     * @var string
     */
    const LIST_TYPE = 'listType';
    
    /**
     * config for address parsing rules file
     * 
     * @var string
     */
    const CONTACT_ADDRESS_PARSE_RULES_FILE = 'parseRulesFile';

    /**
     * FEATURE_CONTACT_EVENT_LIST
     *
     * @var string
     */
    const FEATURE_CONTACT_EVENT_LIST = 'featureContactEventList';

    /**
     * FEATURE_MAILINGLIST
     *
     * @var string
     */
    const FEATURE_MAILINGLIST = 'featureMailinglist';

    /**
     * FEATURE_LIST_VIEW
     *
     * @var string
     */
    const FEATURE_LIST_VIEW = 'featureListView';

    /**
     * FEATURE_INDUSTRY
     *
     * @var string
     */
    const FEATURE_INDUSTRY = 'featureIndustry';

    /**
     * FEATURE_SHORT_NAME
     *
     * @var string
     */
    const FEATURE_SHORT_NAME = 'featureShortName';

    /**
     * FEATURE_RESOURCES
     *
     * @var string
     */
    const FEATURE_RESOURCES = 'featureResources';

    /**
     * FEATURE_STRUCTUREPANEL
     *
     * @var string
     */
    const FEATURE_STRUCTUREPANEL = 'featureStructurePanel';

    /**
     * Filter configuration for site record pickers
     * @var string
     */
    const SITE_FILTER = 'siteFilter';

    /**
     * config for the syncBackends
     *
     * @var string
     */
    const SYNC_BACKENDS = 'syncBackends';

    const DEFAULT_TEL_COUNTRY_CODE = 'defaultTelCountryCode';

    /**
     * (FEATURE_LIST_VIEW-PHPdoc)
     * @see tine20/Tinebase/Config/Definition::$_properties
     */
    protected static $_properties = array(
        self::ENABLED_FEATURES => [
            //_('Enabled Features')
            self::LABEL                 => 'Enabled Features',
            //_('Enabled Features in Addressbook Application.')
            self::DESCRIPTION           => 'Enabled Features in Addressbook Application.',
            self::TYPE                  => self::TYPE_OBJECT,
            self::CLASSNAME             => Tinebase_Config_Struct::class,
            self::CLIENTREGISTRYINCLUDE => true,
            self::CONTENT               => [
                self::FEATURE_CONTACT_EVENT_LIST => [
                    self::LABEL                     => 'Addressbook Contact Event List',
                    //_('Addressbook Contact Event List')
                    self::DESCRIPTION               => 'Shows contact events in edit dialog tab panel',
                    //_('Shows contact events in edit dialog tab panel')
                    self::TYPE                      => self::TYPE_BOOL,
                    self::DEFAULT_STR               => true,
                ],
                self::FEATURE_LIST_VIEW          => [
                    self::LABEL                     => 'Addressbook List View',
                    //_('Addressbook List View')
                    self::DESCRIPTION               => 'Shows an additional view for lists inside the addressbook',
                    //_('Shows an additional view for lists inside the addressbook')
                    self::TYPE                      => self::TYPE_BOOL,
                    self::DEFAULT_STR               => true,
                ],
                self::FEATURE_INDUSTRY           => [
                    self::LABEL                     => 'Addressbook Industries',
                    //_('Addressbook Industries')
                    self::DESCRIPTION               => 'Add Industry field to Adressbook',
                    //_('Add Industry field to Adressbook')
                    self::TYPE                      => self::TYPE_BOOL,
                    self::DEFAULT_STR               => true,
                ],
                self::FEATURE_MAILINGLIST           => [
                    self::LABEL                     => 'Mailinglists',
                    //_('Mailinglists')
                    self::DESCRIPTION               => 'Make group and lists to mailinglists',
                    //_('Make group and lists to mailinglists')
                    self::TYPE                      => self::TYPE_BOOL,
                    self::DEFAULT_STR               => false,
                ],
                self::FEATURE_SHORT_NAME           => [
                    self::LABEL                     => 'Addressbook Short Names',
                    //_('Addressbook Short Names')
                    self::DESCRIPTION               => 'Add Short Name field to Adressbook',
                    //_('Add Short Name field to Adressbook')
                    self::TYPE                      => self::TYPE_BOOL,
                    self::DEFAULT_STR               => false,
                ],
                self::FEATURE_RESOURCES          => [
                    self::LABEL                     => 'Manage resources in Addressbook',
                    // _('Manage resources in Addressbook')
                    self::DESCRIPTION               => 'Show the resources grid also inside the Addressbook',
                    // _('Show the resources grid also inside the Addressbook')
                    self::TYPE                      => self::TYPE_BOOL,
                    self::DEFAULT_STR               => true,
                ],
                self::FEATURE_STRUCTUREPANEL     => [
                    self::LABEL                     => 'Show Structure Panel in Addressbook',
                    // _('Show Structure Panel in Addressbook')
                    self::DESCRIPTION               => 'Visualize relations of records',
                    // _('Visualize relations of records')
                    self::TYPE                      => self::TYPE_BOOL,
                    self::DEFAULT_STR               => true,
                ],
            ],
            self::DEFAULT_STR => [],
        ],
        self::CONTACT_DUP_FIELDS => array(
                                   //_('Contact duplicate check fields')
            'label'                 => 'Contact duplicate check fields',
                                   //_('These fields are checked when a new contact is created. If a record with the same data in the fields is found, a duplicate exception is thrown.')
            'description'           => 'These fields are checked when a new contact is created. If a record with the same data in the fields is found, a duplicate exception is thrown.',
            'type'                  => 'array',
            'contents'              => 'array',
            'clientRegistryInclude' => TRUE,
        // @todo make default work
            'default'               => array(               // array of alternatives
                array('n_given', 'n_family', 'org_name'),   // all fields must match
                array('email'),                             // single field that needs to match
            ),
        ),
        /**
         * possible values: disabled, expired, never
         *
         * TODO only allow some values
         */
        self::CONTACT_HIDDEN_CRITERIA => array(
            //_('Contact Hidden Criteria')
            'label'                 => 'Contact Hidden Criteria',
            //_('The contact is hidden if it is ... (one of: disabled, expired or never)')
            'description'           => 'The contact is hidden if it is ... (one of: disabled, expired or never)',
            'type'                  => 'string',
            'clientRegistryInclude' => false,
            'default'               => 'disabled'
        ),
        self::CONTACT_SALUTATION => array(
        //_('Contact salutations available')
            'label'                 => 'Contact salutations available',
        //_('Possible contact salutations. Please note that additional values might impact other Addressbook systems on export or syncronisation.')
            'description'           => 'Possible contact salutations. Please note that additional values might impact other Addressbook systems on export or syncronisation.',
            'type'                  => 'keyFieldConfig',
            'options'               => array('recordModel' => 'Addressbook_Model_Salutation'),
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
            'default'               => array(
                'records' => array(
                    array('id' => 'MR',      'value' => 'Mr',      'gender' => Addressbook_Model_Salutation::GENDER_MALE,   'image' => 'images/icon-set/icon_man.svg',    'system' => true), //_('Mr')
                    array('id' => 'MS',      'value' => 'Ms',      'gender' => Addressbook_Model_Salutation::GENDER_FEMALE, 'image' => 'images/icon-set/icon_woman.svg',  'system' => true), //_('Ms')
                    array('id' => 'COMPANY', 'value' => 'Company', 'gender' => Addressbook_Model_Salutation::GENDER_OTHER,  'image' => 'images/icon-set/icon_company.svg','system' => true), //_('Company')
                ),
//                'default' => 'MR'
            )
        ),
        self::CONTACT_ADDRESS_PARSE_RULES_FILE => array(
        //_('Parsing rules for addresses')
            'label'                 => 'Parsing rules for addresses',
        //_('Path to a XML file with address parsing rules.')
            'description'           => 'Path to a XML file with address parsing rules.',
            'type'                  => 'string',
            'clientRegistryInclude' => FALSE,
            'setByAdminModule'      => FALSE,
            'setBySetupModule'      => FALSE,
        ),
        self::LIST_TYPE => array(
                //_('List types available')
                'label'                 => 'List types available',
                //_('List types available.')
                'description'           => 'List types available.',
                'type'                  => 'keyFieldConfig',
                'clientRegistryInclude' => TRUE,
                'setByAdminModule'      => true,
                'default'               => array(
                    'records' => array(
                        array('id' => 'DEPARTMENT',    'value' => 'Department'), //_('Department')
                        array('id' => 'MAILINGLIST',    'value' => 'Mailing list'), //_('Mailing list')
                    ),
            )
        ),
        self::CONTACT_IMPORT_NOMINATIM => array(
            //_('Use Nominatim during contact import')
            'label'                 => 'Use Nominatim during contact import',
            'description'           => 'Use Nominatim during contact import',
            'type'                  => 'bool',
            'default'               => false,
            'clientRegistryInclude' => false,
            'setByAdminModule'      => true,
            'setBySetupModule'      => true,
        ),
        self::SITE_FILTER => array(
            // _('Site Filter')
            'label'                 => 'Site Filter',
            // _('Filter configuration for site record pickers. Sites can be a special type of contacts/groups for example defined by this filter.')
            'description'           => 'Filter configuration for site record pickers. Sites can be a special type of contacts/groups for example defined by this filter.',
            'type'                  => Tinebase_Config_Abstract::TYPE_ARRAY,
            'clientRegistryInclude' => TRUE,
            'setByAdminModule'      => TRUE,
        ),
        self::SYNC_BACKENDS => array(
            //_('Sync Backends')
            'label'                 => 'Sync Backends',
            //_('Sync Backends')
            'description'           => 'Sync Backends',
            'type'                  => 'array',
            'clientRegistryInclude' => false,
            'setByAdminModule'      => true,
            'default'               => array()
        ),
        self::DEFAULT_TEL_COUNTRY_CODE => [
            //_('Default telephone country code')
            self::LABEL                 => 'Default telephone country code',
            //_('Default telephone country code')
            self::DESCRIPTION           => 'Default telephone country code',
            self::TYPE                  => self::TYPE_STRING,
            self::DEFAULT_STR           => 'DE',
            self::CLIENTREGISTRYINCLUDE => true,
            self::SETBYADMINMODULE      => true,
            self::SETBYSETUPMODULE      => true,
        ],
    );
    
    /**
     * (non-PHPdoc)
     * @see tine20/Tinebase/Config/Abstract::$_appName
     */
    protected $_appName = 'Addressbook';
    
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
