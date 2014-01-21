<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Configuration
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Tinebase_ModelConfiguration
 *
 * @package     Tinebase
 * @subpackage  Configuration
 *
 */
class Tinebase_ModelConfiguration {

    /**
     * this holds (caches) the availibility info of applications globally
     * 
     * @var array
     */
    static protected $_availableApplications = array('Tinebase' => TRUE);

    /**
     * the id property
     *
     * @var string
     */
    protected $_idProperty = 'id';
    
    // legacy
    protected $_identifier;

    /**
     * Human readable name of the record
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 1);
     *
     * @var string
     */
    protected $_recordName = NULL;

    /**
     * Human readable name of multiple records
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 2);
     *
     * @var string
     */
    protected $_recordsName = NULL;

    /**
     * The property of the container, if any
     *
     * @var string
     */
    protected $_containerProperty = NULL;
    
    /**
     * The property of the title, if any
     *
     * @var string
     */
    protected $_titleProperty = 'title';
    
    /**
     * Human readable name of the container
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 2);
     *
     * @var string
     */
    protected $_containerName = NULL;

    /**
     * Human readable name of multiple containers
     * add plural translation information in comments like:
     * // ngettext('Record Name', 'Records Name', 2);
     *
     * @var string
     */
    protected $_containersName = NULL;

    /**
     * If this is true, the record has relations
     *
     * @var boolean
     */
    protected $_hasRelations = NULL;

    /**
     * If this is true, the record has customfields
     *
     * @var boolean
     */
    protected $_hasCustomFields = NULL;

    /**
     * If this is true, the record has notes
     *
     * @var boolean
     */
    protected $_hasNotes = NULL;

    /**
     * If this is true, the record has tags
     *
     * @var boolean
     */
    protected $_hasTags = NULL;

    /**
     * If this is true, the record has file attachments
     *
     * @var boolean
     */
    protected $_hasAttachments = NULL;
    
    /**
     * If this is true, a modlog will be created
     *
     * @var boolean
     */
    protected $_modlogActive = NULL;

    /**
     * If this is true, multiple edit of records of this model is possible.
     *
     * @var boolean
     */
    protected $_multipleEdit = NULL;

    /**
     * If multiple edit requires a special right, it's defined here
     *
     * @var string
     */
    protected $_multipleEditRequiredRight = NULL;

    /**
     * Group name of this model (will create a parent node in the modulepanel with this name)
     * add translation information in comments like: // _('Group')
     *
     * @var string
     */
    protected $_moduleGroup = NULL;

    /**
     * Set the default Filter (defaults to query)
     *
     * @var string
     */
    protected $_defaultFilter = 'query';

    /**
     * Set the default sort info for the gridpanel (Tine.widgets.grid.GridPanel.defaultSortInfo)
     * set as array('field' => 'title', 'direction' => 'DESC')
     *
     * @var array
     */
    protected $_defaultSortInfo = NULL;

    /**
     * Defines the right to see this model
     *
     * @var string
     */
    protected $_requiredRight = NULL;

    /**
     * no containers
     * 
     * @var boolean
     */
    protected $_singularContainerMode = NULL;

    /**
     * Holds the field definitions in an associative array where the key
     * corresponds to the db-table name. Possible definitions and their defaults:
     *
     * - validators: Use Zend Input Filters to validate the values.
     *       @type: Array, @default: array(Zend_Filter_Input::ALLOW_EMPTY => true)
     *
     * - label: The human readable label of the field. If this is set to null, this won't be shown in the auto FE Grid or EditDialog.
     *       Add translation information in comments like: // _('Title')
     *       @type: String, @default: NULL
     *
     * - default: The default Value of the field.
     *       Add translation information in comments like: // _('New Car')
     *       @type: as defined (see DEFAULT MAPPING), @default: NULL
     *
     * - duplicateCheckGroup: All Fields having the same group will be combined for duplicate
     *       check. If no group is given, no duplicate check will be done.
     *       @type: String, @default: NULL
     *
     * - type: The type of the Value
     *       @type: String, @default: "string"
     *
     * - specialType: Defines the type closer
     *       @type: String, @default: NULL
     *
     * - filterDefinition: Overwrites the default filter used for this field
     *       Definition knows all from Tinebase_Model_Filter_FilterGroup._filterModel
     *       @type: Array, @default: array('filter' => 'Tinebase_Model_Filter_Text') or the default used for this type (see DEFAULT MAPPING)
     * 
     * - inputFilters: zend input filters to use for this field
     *       @type: Array, use array(<InPutFilterClassName> => <constructorData>, ...)
     * 
     * - queryFilter: If this is set to true, this field will be used by the "query" filter
     *       @type: Boolean, @default: NULL
     *
     * - duplicateOmit: Will neither be shown nor handled on duplicate resolving
     *       @type: Boolean, @default: NULL
     *
     * - copyOmit: If this is set to true, the field won't be used on copy the record
     *       @type: Boolean, @default: NULL
     *
     * - readOnly: If this is set to true, the field can't be updated and will be shown as readOnly in the frontend
     *       @type: Boolean, @default: NULL
     *
     * - disabled: If this is set to true, the field can't be updated and will be shown as readOnly in the frontend
     *       @type: Boolean, @default: NULL
     *
     * - group: Add this field to a group. Each group will be shown as a separate FieldSet of the
     *       EditDialog and group in the DuplicateResolveGridPanel. If any field of this model
     *       has a group set, FieldSets will be created and fields without a group set will be
     *       added to a group with the same name as the RecordName.
     *       Add translation information in comments like: // _('Group')
     *       @type: String, @default: NULL
     *
     * - dateFormat: If type is a date, the format can be overwritten here
     *       @type: String, @default: NULL or the default used for this type (see DEFAULT MAPPING)
     *
     * - shy: If this is set to true, the row for this field won't be shown in the grid, but can be activated
     * 
     * - sortable: If this is set to false, no sort by this field is possible in the gridpanel, defaults to true
     * 
     *   // TODO: generalize, currently only in ContractGridPanel, take it from there:
     * - showInDetailsPanel: auto show in details panel if any is defined in the js gridpanel class
     * 
     * - useGlobalTranslation: if set, the global translation is used
     * 
     * DEFAULT MAPPING:
     *
     * Field-Type  specialType   Human-Type          SQL-Type JS-Type                       PHP-Type          PHP-Filter                  dateFormat    JS-FilterType
     *
     * date                      Date                datetime date                          Tinebase_DateTime Tinebase_Model_Filter_Date  ISO8601Short
     * datetime                  Date with time      datetime date                          Tinebase_DateTime Tinebase_Model_Filter_Date  ISO8601Long
     * time                      Time                datetime date                          Tinebase_DateTime Tinebase_Model_Filter_Date  ISO8601Time
     * string                    Text                varchar  string                        string            Tinebase_Model_Filter_Text
     * text                      Text with lnbr.     text     string                        string            Tinebase_Model_Filter_Text
     * boolean                   Boolean             boolean  bool                          bool              Tinebase_Model_Filter_Bool
     * integer                   Integer             integer  integer                       int               Tinebase_Model_Filter_Int                 number
     * integer     bytes         Bytes               integer  integer                       int               Tinebase_Model_Filter_Int
     * integer     usMoney       Dollar in Cent      integer  integer                       int               Tinebase_Model_Filter_Int
     * integer     euMoney       Euro in Cent        integer  integer                       int               Tinebase_Model_Filter_Int
     * integer     seconds       Seconds             integer  integer                       int               Tinebase_Model_Filter_Int
     * integer     minutes       Minutes             integer  integer                       int               Tinebase_Model_Filter_Int
     * float                     Float               float    float                         float             Tinebase_Model_Filter_Int
     * container                 Container           string   Tine.Tinebase.Model.Container Tinebase_Model_Container                                    tine.widget.container.filtermodel
     * tag tinebase.tag
     * user                      User                string                                 Tinebase_Model_Filter_User
     * virtual:
     * 
     * Field Type "virtual" has a config property which holds the field configuration.
     * An additional property is "function". If this property is set, the given function
     * will be called to resolve the field in Tinebase_Convert_Json.
     * If an array with two values is given, the first value will be handled as a class,
     * the second one would be handled as a statically callable method.
     * if the array is an associative one with one key and one value, 
     * the key will be used for the classname of a singleton (callable by getInstance),
     * the value will be used as method name.
     * 
     * * record/foreign (legacy) 1:1 - Relation      text     Tine.<APP>.Model.<MODEL>      Tinebase_Record_Abstract  Tinebase_Model_Filter_ForeignId   Tine.widgets.grid.ForeignRecordFilter
     * * records                 1:n - Relation      -        Array of Record.data Objects  Tinebase_Record_RecordSet -                                 -
     * * relation                m:m - Relation      -        Tinebase.Model.Relation       Tinebase_Model_Relation   Tinebase_Model_Filter_Relation
     * * keyfield                String              string   <as defined>                  string            Tinebase_Model_Filter_Text
     *
     * * Accepts additional parameter: 'config' => array with these keys:
     *     - @string appName    (the name of the application of the referenced record/s)
     *     - @string modelName  (the name of the model of the referenced record/s)
     *
     *   Config for 'record' and 'records' accepts also these keys: (optionally if record class name doesn't fit the convention, will be autogenerated, if not set)
     *     - @string recordClassName 
     *     - @string controllerClassName
     *     
     *   Config for 'records' accepts also these keys:
     *     - @string refIdField (the field of the foreign record referencing the idProperty of the own record)
     *     - @array  paging     (accepts the parameters as Tinebase_Model_Pagination does)
     *     - @string filterClassName
     *     - @array  addFilters define them as array like Tinebase_Model_Filter_FilterGroup
     * 
     * record accepts keys additionally
     *     - @string isParent   set this to true if the field is the parent property of an dependent record. This field will be hidden in an edit dialog nested grid
     * 
     * <code>
     *
     * array(
     *     'title' => array(
     *         'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
     *         'label' => NULL,
     *         'duplicateCheckGroup' => 'number'
     *     ),
     *     'number' => ...
     * )
     *
     * </code>
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * holds all field definitions of type records
     *
     * @var array
    */
    protected $_recordsFields = NULL;

    /**
     * holds all field definitions of type record (foreignId fields)
     *
     * @var array
     */
    protected $_recordFields  = NULL;

    /**
     * holds virtual field definitions used for non-persistent fields getting calculated on each call of the record
     * no backend property will be build, no filters etc. will exist. they must be filled in frontend json
     * 
     * @var array
     */
    protected $_virtualFields = NULL;
    
    /**
     * maps fieldgroup keys to their names
     * Add translation information in comments like: // _('Banking Information')
     * 
     * array(
     *     'banking' => 'Banking Information',    // _('Banking Information')
     *     'private' => 'Private Information',    // _('Private Information')
     *     )
     * 
     * @var array
     */
    
    protected $_fieldGroups = NULL;
    /**
     * here you can define one right (Tinebase_Acl_Rights_Abstract) for each field
     * group ('group'-property of a field definition of this._fields), the user must
     * have to see/edit this group, otherwise the fields of the edit dialog will be disabled/readOnly
     *
     * array(
     *     'private' => array(
     *         'see'  => HumanResources_Acl_Rights::SEE_PRIVATE,
     *         'edit' => HumanResources_Acl_Rights::EDIT_PRIVATE,
     *     ),
     *     'banking' => array(
     *         'see'  => HumanResources_Acl_Rights::SEE_BANKING,
     *         'edit' => HumanResources_Acl_Rights::EDIT_BANKING,
     *     )
     * );
     *
     * @var array
     */
    protected $_fieldGroupRights = array();

    /**
     * every field group will be nested into a fieldset, here you can define the defaults (Ext.Container.defaults)
     *
     * @var array
    */
    protected $_fieldGroupFeDefaults = array();

    protected $_createModule = FALSE;
    
    /*
     * auto set by the constructor
    */

    /**
     * If any field has a group, this will be set to true (autoset by the constructor)
     *
     * @var boolean
    */
    protected $_useGroups = FALSE;

    /**
     * the application this configuration belongs to (if the class has the name "Calendar_Model_Event", this will be resolved to "Calendar")
     *
     * @var string
     */
    protected $_appName = NULL;    // this should be used everytime, everywhere
    // legacy
    protected $_application = NULL;
    protected $_applicationName = NULL;
    /**
     * the name of the model (if the class has the name "Calendar_Model_Event", this will be resolved to "Event")
     *
     * @var string
     */
    protected $_modelName = NULL;

    /**
     * holds the keys of all fields
     *
     * @var array
     */
    protected $_fieldKeys = array();

    /**
     * holds the time fields
     *
     * @var array
    */
    protected $_timeFields = array();

    /**
     * holds the fields which will be omitted in the modlog
     *
     * @var array
    */
    protected $_modlogOmitFields = array();

    /**
     * these fields will just be readOnly
     *
     * @var array
    */
    protected $_readOnlyFields = array();

    /**
     * holds the date and datetime fields
     *
     * @var array
    */
    protected $_datetimeFields = array();

    /**
     * holds the alarm datetime fields
     *
     * @var array
    */
    protected $_alarmDateTimeField = array();

    /**
     * The calculated filters for this model (auto set)
     *
     * @var array
    */
    protected $_filterModel = array();

    /**
     * holds the validators for the model (auto set)
    */
    protected $_validators = array();

    /**
     * holds validators which will be instanciated on model construction
     *
     * @var array
    */
    protected $_ownValidators = array();
    
    /**
     * if a record is dependent to another, this is true
     * 
     * @var boolean
     */
    protected $_isDependent = FALSE;
    
    /**
     * input filters (will be set by field configuration)
     * 
     * @var array
     */
    protected $_filters;
    
    /**
     * Holds the default Data for the model (autoset from field config)
     *
     * @var array
    */
    protected $_defaultData = array();

    /**
     * holds the fields / groups to check for duplicates (will be auto set by field configuration)
    */
    protected $_duplicateCheckFields = NULL;

    /**
     * properties to collect for the filters (_appName and _modelName are set in the filter)
     *
     * @var array
     */
    protected $_filterProperties = array('_filterModel', '_defaultFilter', '_modelName', '_applicationName');

    /**
     * properties to collect for the model
     *
     * @var array
    */
    protected $_modelProperties = array(
        '_identifier', '_timeFields', '_datetimeFields', '_alarmDateTimeField', '_validators', '_modlogOmitFields',
        '_application', '_readOnlyFields', '_filters'
    );

    /**
     * properties to collect for the frontend
     *
     * @var array
    */
    protected $_frontendProperties = array(
        'containerProperty', 'containersName', 'containerName', 'defaultSortInfo', 'fieldKeys', 'filterModel',
        'defaultFilter', 'requiredRight', 'singularContainerMode', 'fields', 'defaultData', 'titleProperty',
        'useGroups', 'fieldGroupFeDefaults', 'fieldGroupRights', 'multipleEdit', 'multipleEditRequiredRight',
        'recordName', 'recordsName', 'appName', 'modelName', 'createModule', 'virtualFields', 'group', 'isDependent',
        'hasCustomFields', 'modlogActive', 'hasAttachments', 'idProperty'
    );

    /**
     * the module group (will be on the same leaf of the content type tree panel)
     * 
     * @var string
     */
    protected $_group = NULL;
    
    /**
     * the backend properties holding the collected properties
     *
     * @var array
    */
    protected $_modelConfiguration = NULL;

    /**
     * holds the collected values for the frontendconfig (autoset on first call of getFrontendConfiguration)
     * 
     * @var array
     */
    protected $_frontendConfiguration = NULL;
    
    /**
     * the backend properties holding the collected properties
     *
     * @var array
     */
    protected $_filterConfiguration = NULL;

    /*
     * mappings
     */

    /**
     * This defines the filters use for all known types
     * @var array
     */
    protected $_filterModelMapping = array(
        'date'     => 'Tinebase_Model_Filter_Date',
        'datetime' => 'Tinebase_Model_Filter_Date',
        'time'     => 'Tinebase_Model_Filter_Date',
        'string'   => 'Tinebase_Model_Filter_Text',
        'text'     => 'Tinebase_Model_Filter_Text',
        'boolean'  => 'Tinebase_Model_Filter_Bool',
        'integer'  => 'Tinebase_Model_Filter_Int',
        'float'    => 'Tinebase_Model_Filter_Int',
        'record'   => 'Tinebase_Model_Filter_ForeignId',
        'relation' => 'Tinebase_Model_Filter_Relation',

        'keyfield'  => 'Tinebase_Model_Filter_Text',
        'container' => 'Tinebase_Model_Filter_Container',
        'tag'       => 'Tinebase_Model_Filter_Tag',
        'user'      => 'Tinebase_Model_Filter_User',
    );

    /**
     * This maps field types to own validators, which will be instanciated in the constructor.
     *
     * @var array
    */
    protected $_inputFilterDefaultMapping = array(
        'text'     => array('Tinebase_Model_InputFilter_CrlfConvert'),
    );

    /**
     * This maps field types to their default validators, just zendfw validators can be used here.
     * For using own validators, use _ownValidatorMapping instead. If no validator is given,
     * "array(Zend_Filter_Input::ALLOW_EMPTY => true)" will be used
     *
     * @var array
    */
    protected $_validatorMapping = array(
        'record'    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'relation'  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
    );

    /**
     * the constructor (must be called by the singleton pattern)
     *
     * @var array $modelClassConfiguration
     * @throws Tinebase_Exception_Record_DefinitionFailure
     */
    public function __construct($modelClassConfiguration)
    {
        if (! $modelClassConfiguration) {
            throw new Tinebase_Exception('The model class configuration must be submitted!');
        }

        $this->_appName     = $this->_application = $this->_applicationName = $modelClassConfiguration['appName'];
        
        // add appName to available applications 
        self::$_availableApplications[$this->_appName] = TRUE;
        
        $this->_modelName   = $modelClassConfiguration['modelName'];
        $this->_idProperty  = $this->_identifier = (isset($modelClassConfiguration['idProperty']) || array_key_exists('idProperty', $modelClassConfiguration)) ? $modelClassConfiguration['idProperty'] : 'id';

        // some cruid validating
        foreach ($modelClassConfiguration as $propertyName => $propertyValue) {
            $this->{'_' . $propertyName} = $propertyValue;
        }
        
        $this->_filters = array();
        $this->_fields[$this->_idProperty] = array('label' => NULL, 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true));

        if ($this->_hasCustomFields) {
            $this->_fields['customfields'] = array('label' => NULL, 'type' => 'custom', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
        }

        if ($this->_hasRelations) {
            $this->_fields['relations'] = array('label' => NULL, 'type' => 'relation', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
        }

        if ($this->_containerProperty) {
            $this->_fields[$this->_containerProperty] = array(
                'label'            => $this->_containerName,
                'shy'              => true,
                'type'             => 'container',
                'validators'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'filterDefinition' => array(
                    'filter'  => $this->_filterModelMapping['container'],
                    'options' => array('applicationName' => $this->_appName)
                )
            );
        } else {
            $this->_singularContainerMode = true;
        }

        // quick hack ('key')
        if ($this->_hasTags) {
            $this->_fields['tags'] = array(
                'label' => 'Tags',
                'sortable' => false,
                'type' => 'tag', 
                'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL), 
                'useGlobalTranslation' => TRUE,
                'filterDefinition' => array(
                    'key'     => 'tag',
                    'filter'  => $this->_filterModelMapping['tag'],
                    'options' => array(
                           'idProperty' => $this->_idProperty,
                           'applicationName' => $this->_appName
                    )
                )
            );
        }

        if ($this->_hasAttachments) {
            $this->_fields['attachments'] = array(
                'label' => NULL,
                'type'  => 'attachments'
            );
        }
        
        
        if ($this->_modlogActive) {
            // notes are needed if modlog is active
            $this->_fields['notes']              = array('label' => NULL,                 'type' => 'note',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL), 'useGlobalTranslation' => TRUE);
            $this->_fields['created_by']         = array('label' => 'Created By',         'type' => 'user',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE);
            $this->_fields['creation_time']      = array('label' => 'Creation Time',      'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE);
            $this->_fields['last_modified_by']   = array('label' => 'Last Modified By',   'type' => 'user',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE);
            $this->_fields['last_modified_time'] = array('label' => 'Last Modified Time', 'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE);
            $this->_fields['seq']                = array('label' => NULL,                 'type' => 'integer',  'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'shy' => true, 'useGlobalTranslation' => TRUE);
            
            // don't show deleted information
            $this->_fields['deleted_by']         = array('label' => NULL, 'type' => 'user',     'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'useGlobalTranslation' => TRUE);
            $this->_fields['deleted_time']       = array('label' => NULL, 'type' => 'datetime', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'useGlobalTranslation' => TRUE);
            $this->_fields['is_deleted']         = array('label' => NULL, 'type' => 'boolean',  'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true), 'useGlobalTranslation' => TRUE);

        } elseif ($this->_hasNotes) {
            $this->_fields['notes'] = array('label' => NULL, 'type' => 'note', 'validators' => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL));
        }
        
        // holds the filters used for the query-filter, if any
        $queryFilters = array();
        
        foreach ($this->_fields as $fieldKey => &$fieldDef) {
            // set default type to string, if no type is given
            if (! (isset($fieldDef['type']) || array_key_exists('type', $fieldDef))) {
                $fieldDef['type'] = 'string';
            }
            
            // don't handle field if app is not available
            if ((isset($fieldDef['config']) || array_key_exists('config', $fieldDef)) && ($fieldDef['type'] == 'record' || $fieldDef['type'] == 'records') && (! $this->_isAvailable($fieldDef['config']))) {
                $fieldDef['type'] = 'string';
                $fieldDef['label'] = NULL;
                continue;
            }
            // the property name
            $fieldDef['key'] = $fieldKey;

            // if any field has a group set, enable grouping globally
            if (! $this->_useGroups && (isset($fieldDef['group']) || array_key_exists('group', $fieldDef))) {
                $this->_useGroups = TRUE;
            }

            if ($fieldDef['type'] == 'virtual') {
                $fieldDef = $fieldDef['config'];
                $fieldDef['sortable'] = FALSE;
                $this->_virtualFields[] = $fieldDef;
                continue;
            }
            
            // set default value
            // TODO: implement complex default values
            if ((isset($fieldDef['default']) || array_key_exists('default', $fieldDef))) {
//                 // allows dynamic default values
//                 if (is_array($fieldDef['default'])) {
//                     switch ($fieldDef['type']) {
//                         case 'time':
//                         case 'date':
//                         case 'datetime':
//                         default:
//                             throw new Tinebase_Exception_NotImplemented($_message);
//                     }
//                 } else {
                    $this->_defaultData[$fieldKey] = $fieldDef['default'];
                    
//                 }
            }

            // TODO: Split this up in multiple functions
            // TODO: Refactor: key 'tag' should be 'tags' in filter definition / quick hack
            // also see ticket 8944 (https://forge.tine20.org/mantisbt/view.php?id=8944)
            
            // set filter model
            if ((isset($fieldDef['filterDefinition']) || array_key_exists('filterDefinition', $fieldDef))) {
                // use filter from definition
                $key = isset($fieldDef['filterDefinition']['key']) ? $fieldDef['filterDefinition']['key'] : $fieldKey;
                $this->_filterModel[$key] = $fieldDef['filterDefinition'];
            } else if ((isset($this->_filterModelMapping[$fieldDef['type']]) || array_key_exists($fieldDef['type'], $this->_filterModelMapping))) {
                // if no filterDefinition is given, try to use the default one
                $this->_filterModel[$fieldKey] = array('filter' => $this->_filterModelMapping[$fieldDef['type']]);
                if ((isset($fieldDef['config']) || array_key_exists('config', $fieldDef))) {
                    $this->_filterModel[$fieldKey]['options'] = $fieldDef['config'];
                    
                    // set id filter controller
                    if ($fieldDef['type'] == 'record') {
                        $this->_filterModel[$fieldKey]['options']['filtergroup'] = $fieldDef['config']['appName'] . '_Model_' . $fieldDef['config']['modelName'] . 'Filter';
                        $this->_filterModel[$fieldKey]['options']['controller']  = $fieldDef['config']['appName'] . '_Controller_' . $fieldDef['config']['modelName'];
                    }
                    
                }
            }
            
            if ((isset($fieldDef['queryFilter']) || array_key_exists('queryFilter', $fieldDef))) {
                $queryFilters[] = $fieldKey;
            }

            // set validators
            if ((isset($fieldDef['validators']) || array_key_exists('validators', $fieldDef))) {
                // use _validators from definition
                $this->_validators[$fieldKey] = $fieldDef['validators'];
            } else if ((isset($this->_validatorMapping[$fieldDef['type']]) || array_key_exists($fieldDef['type'], $this->_validatorMapping))) {
                // if no validatorsDefinition is given, try to use the default one
                $fieldDef['validators'] = $this->_validators[$fieldKey] = $this->_validatorMapping[$fieldDef['type']];
            } else {
                $fieldDef['validators'] = $this->_validators[$fieldKey] = array(Zend_Filter_Input::ALLOW_EMPTY => true);
            }

            // set input filters, append defined if any or use defaults from _inputFilterDefaultMapping 
            if ((isset($fieldDef['inputFilters']) || array_key_exists('inputFilters', $fieldDef))) {
                foreach ($fieldDef['inputFilters'] as $if => $val) { 
                    $this->_filters[$fieldKey][] = $if ? new $if($val) : new $val();
                }
            } else if ((isset($this->_inputFilterDefaultMapping[$fieldDef['type']]) || array_key_exists($fieldDef['type'], $this->_inputFilterDefaultMapping))) {
                foreach ($this->_inputFilterDefaultMapping[$fieldDef['type']] as $if => $val) {
                    $this->_filters[$fieldKey][] = $if ? new $if($val) : new $val();
                }
            }
            
            // add field to modlog omit, if configured and modlog is used
            if ($this->_modlogActive && (isset($fieldDef['modlogOmit']) || array_key_exists('modlogOmit', $fieldDef))) {
                $this->_modlogOmitFields[] = $fieldKey;
            }
            
            $this->_populateProperties($fieldKey, $fieldDef);
        }
        
        // set some default filters
        if (count($queryFilters)) {
            $this->_filterModel['query'] = array('label' => 'Quick Search', 'field' => 'query', 'filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => $queryFilters), 'useGlobalTranslation' => true);
        }
        $this->_filterModel[$this->_idProperty] = array('filter' => 'Tinebase_Model_Filter_Id', 'options' => array('idProperty' => $this->_idProperty, 'modelName' => $this->_appName . '_Model_' . $this->_modelName));
        $this->_fieldKeys = array_keys($this->_fields);
    }
    
    /**
     * populate model config properties
     * 
     * @param string $fieldKey
     * @param array $fieldDef
     */
    protected function _populateProperties($fieldKey, $fieldDef)
    {
        switch ($fieldDef['type']) {
            case 'string':
            case 'text':
            case 'integer':
            case 'float':
            case 'boolean':
                break;
            case 'container':
                break;
            case 'date':
            case 'datetime':
                // add to alarm fields
                if ((isset($fieldDef['alarm']) || array_key_exists('alarm', $fieldDef))) {
                    $this->_alarmDateTimeField = $fieldKey;
                }
                // add to datetime fields
                $this->_datetimeFields[] = $fieldKey;
                break;
            case 'time':
                // add to timefields
                $this->_timeFields[] = $fieldKey;
                break;
            case 'user':
                $fieldDef['config'] = array(
                    'refIdField'              => 'id',
                    'appName'                 => 'Addressbook',
                    'modelName'               => 'Contact',
                    'recordClassName'         => 'Addressbook_Model_Contact',
                    'controllerClassName'     => 'Addressbook_Controller_Contact',
                    'filterClassName'         => 'Addressbook_Model_ContactFilter',
                    'addFilters' => array(
                        array('field' => 'type', 'operator' => 'equals', 'value' => 'user')
                    )
                );
                $this->_recordFields[$fieldKey] = $fieldDef;
                break;
            case 'record':
                $this->_filterModel[$fieldKey]['options']['controller']  = $this->_getPhpClassName($this->_filterModel[$fieldKey]['options'], 'Controller');
                $this->_filterModel[$fieldKey]['options']['filtergroup'] = $this->_getPhpClassName($this->_filterModel[$fieldKey]['options'], 'Model') . 'Filter';
            case 'records':
                $fieldDef['config']['recordClassName']     = (isset($fieldDef['config']['recordClassName']) || array_key_exists('recordClassName', $fieldDef['config']))     ? $fieldDef['config']['recordClassName']     : $this->_getPhpClassName($fieldDef['config']);
                $fieldDef['config']['controllerClassName'] = (isset($fieldDef['config']['controllerClassName']) || array_key_exists('controllerClassName', $fieldDef['config'])) ? $fieldDef['config']['controllerClassName'] : $this->_getPhpClassName($fieldDef['config'], 'Controller');
                $fieldDef['config']['filterClassName']     = (isset($fieldDef['config']['filterClassName']) || array_key_exists('filterClassName', $fieldDef['config']))     ? $fieldDef['config']['filterClassName']     : $this->_getPhpClassName($fieldDef['config']) . 'Filter';
                if ($fieldDef['type'] == 'record') {
                    $this->_recordFields[$fieldKey] = $fieldDef;
                } else {
                    $fieldDef['config']['dependentRecords'] = (isset($fieldDef['config']['dependentRecords']) || array_key_exists('dependentRecords', $fieldDef['config'])) ? $fieldDef['config']['dependentRecords'] : FALSE;
                    $this->_recordsFields[$fieldKey] = $fieldDef;
                }
                break;
            case 'custom':
                try {
                    // prepend table name to id prop because of ambiguous ids
                    // TODO find a better way to get table name, maybe we should put it in the modelconfig?
                    $backend = Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName)->getBackend();
                    $tableName = $backend->getTableName();
                    $this->_filterModel['customfield'] = array(
                        'filter' => 'Tinebase_Model_Filter_CustomField', 
                        'options' => array(
                            'idProperty' => $tableName . '.' . $this->_idProperty
                        )
                    );
                } catch (Exception $e) {
                    // no customfield filter available (yet?)
                    Tinebase_Exception::log($e);
                }
                break;
            default:
                break;
        }
    }
    
    /**
     * gets phpClassName by field definition['config']
     *
     * @param array $_fieldConfig
     * @param string $_type
     * @return string
     */
    protected function _getPhpClassName($_fieldConfig, $_type = 'Model')
    {
        return $_fieldConfig['appName'] . '_' . $_type . '_' . $_fieldConfig['modelName'];
    }
    
    /**
     * checks if app and model is available for the user at record and records fields
     * later this can be used to use field acl
     * 
     * @param array $_fieldConfig the field configuration
     */
    protected function _isAvailable($_fieldConfig)
    {
        if (! (isset(self::$_availableApplications[$_fieldConfig['appName']]) || array_key_exists($_fieldConfig['appName'], self::$_availableApplications))) {
            self::$_availableApplications[$_fieldConfig['appName']] = Tinebase_Application::getInstance()->isInstalled($_fieldConfig['appName'], TRUE);
        }
        return self::$_availableApplications[$_fieldConfig['appName']];
    }
    
    /**
     * returns the filterconfiguration needed in the filtergroup for this model
     * 
     * @return array
     */
    public function getFilterModel()
    {
        // add calculated values to filter configuration
        if (! $this->_filterConfiguration) {
            foreach ($this->_filterProperties as $prop) {
                $this->_filterConfiguration[$prop] = $this->{$prop};
            }
            // @todo: remove this as in the filtergroup
            $this->_filterConfiguration['_className'] = $this->_appName . '_Model_' . $this->_modelName . 'Filter';
        }
        return $this->_filterConfiguration;
    }

    /**
     * returns the properties needed for the model
     * 
     * @return array
     */
    public function toArray()
    {
        if (! $this->_modelConfiguration) {
            // add calculated values to model configuration
            foreach ($this->_modelProperties as $prop) {
                $this->_modelConfiguration[$prop] = $this->{$prop};
            }
        }
        
        return $this->_modelConfiguration;
    }

    /**
     * returns the frontend configuration for creating js models, filters, defaults and some other js stubs.
     * this will be included in the registry.
     * Look at Tinebase/js/ApplicationStarter.js
     */
    public function getFrontendConfiguration()
    {
        if (! $this->_frontendConfiguration) {
            
            $this->_frontendConfiguration = array();
            
            // add calculated values to frontend configuration
            foreach ($this->_frontendProperties as $prop) {
                $this->_frontendConfiguration[$prop] = $this->{'_' . $prop};
            }
        }
        return $this->_frontendConfiguration;
    }

    /**
     * returns default data for this model
     * 
     * @return array
     */
    public function getDefaultData()
    {
        return $this->_defaultData;
    }

    /**
     * returns the field configuration of the model
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * get protected property
     *
     * @param string name of property
     * @throws Tinebase_Exception_UnexpectedValue
     * @return mixed value of property
     */
    public function __get($_property)
    {
        if (! property_exists($this,  '_' . $_property)) {
            throw new Tinebase_Exception_UnexpectedValue('Property does not exist: ' . $_property);
        }
        return $this->{'_' . $_property};
    }
}
