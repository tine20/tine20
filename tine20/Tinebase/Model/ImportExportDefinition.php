<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Tinebase_Model_ImportExportDefinition
 * 
 * @package     Tinebase
 * @subpackage  Import
 *
 * @property string id
 * @property string application_id
 * @property string model
 * @property string name
 * @property string label
 * @property string description
 * @property string type
 * @property string plugin
 * @property string plugin_options
 * @property array  plugin_options_json
 * @property array  plugin_options_definition
 * @property string filename
 * @property bool   favorite
 * @property string icon_class
 * @property int    order
 */
class Tinebase_Model_ImportExportDefinition extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'ImportExportDefinition';

    const FLDS_LABEL = 'label';
    const FLDS_NAME = 'name';
    const FLDS_MODEL = 'model';
    const FLDS_APPLICATION_ID = 'application_id';
    const FLDS_DESCRIPTION = 'description';
    const FLDS_TYPE = 'type';
    const FLDS_FAVORITE = 'favorite';
    const FLDS_ORDER = 'order';
    const FLDS_ICON_CLASS = 'icon_class';
    const FLDS_PLUGIN = 'plugin';
    const FLDS_SCOPE = 'scope';
    const FLDS_PLUGIN_OPTIONS = 'plugin_options';
    const FLDS_PLUGIN_OPTIONS_JSON = 'plugin_options_json';
    const FLDS_PLUGIN_OPTIONS_DEFINITION = 'plugin_options_definition';
    const FLDS_FORMAT = 'format';
    const FLDS_FILENAME = 'filename';
    const FLDS_FILTER = 'filter';

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
    protected $_application = 'Tinebase';

    /**
     * hidden from frontend
     */
    const SCOPE_HIDDEN = 'hidden';

    /**
     * only (?) export multiple records
     */
    const SCOPE_MULTI = 'multi';

    /**
     * only (?) export single records
     */
    const SCOPE_SINGLE = 'single';

    /**
     * independent from current selection
     */
    const SCOPE_REPORT = 'report';

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
        'recordName'        => 'ImportExportDefinition',
        'recordsName'       => 'ImportExportDefinitions', // ngettext('ImportExportDefinition', 'ImportExportDefinitions', n)
        'titleProperty'     => 'name',
        'hasRelations'      => false,
        'hasCustomFields'   => false,
        'hasNotes'          => false,
        'hasTags'           => false,
        'hasXProps'         => false,
        // this will add a notes property which we shouldn't have...
        'modlogActive'      => true,
        'hasAttachments'    => false,
        'createModule'      => false,
        'exposeHttpApi'     => false,
        'exposeJsonApi'     => true,
        'copyEditAction'    => true,


        'appName'           => 'Tinebase',
        self::MODEL_NAME    => self::MODEL_NAME_PART,
        'idProperty'        => 'id',

        'table' => [
            'name' => 'importexport_definition',
        ],

        self::FIELDS        => [
            self::FLDS_APPLICATION_ID => [
                self::TYPE => 'application',
                self::LENGTH => 255,
                'nullable' => false,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'],
                self::LABEL => 'Application', // _('Application')
            ],
            self::FLDS_MODEL => [
                self::LABEL=> 'Model', // _('Model')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'],
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
            ],
            self::FLDS_NAME => [
                self::LABEL => 'Name', // _('Name')
                self::LENGTH => 40,
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'],
                self::TYPE => self::TYPE_STRING,
                self::QUERY_FILTER => true,
            ],
            self::FLDS_LABEL => [
                self::LABEL => 'Label', // _('Label')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
            ],
            self::FLDS_DESCRIPTION => [
                self::LABEL => 'Description', // _('Description')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
            ],
            self::FLDS_TYPE => [
                self::LABEL => 'Type', // _('Type')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY  => false,
                    'presence'                      => 'required',
                    // TODO add constants here
                    // TODO what is a "letter"?
                    ['InArray', ['import', 'export', 'letter']]],
                self::TYPE => self::TYPE_STRING,
            ],
            self::FLDS_FAVORITE => [
                self::LABEL => 'Favorite', // _('Favorite')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true,    'default' => true],
                self::TYPE => self::TYPE_BOOLEAN,
            ],
            self::FLDS_ORDER => [
                self::LABEL => 'Order', // _('Order')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required', 'default' => 0],
                self::TYPE => self::TYPE_INTEGER,
            ],
            self::FLDS_ICON_CLASS => [
                self::LABEL => 'Icon Class', // _('Icon Class')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
                self::SHY => true,
            ],
            self::FLDS_PLUGIN => [
                self::LABEL => 'Plugin', // _('Plugin')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => false,   'presence' => 'required'],
                self::TYPE => self::TYPE_STRING,
            ],
            self::FLDS_SCOPE => [
                self::LABEL => 'Scope', // _('Scope')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
                self::SHY => true,
            ],
            self::FLDS_PLUGIN_OPTIONS => [
                self::LABEL => 'Plugin Options', // _('Plugin Options')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => 'xml',
            ],
            self::FLDS_PLUGIN_OPTIONS_JSON => [
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_VIRTUAL,
                self::SHY => true,
            ],
            self::FLDS_PLUGIN_OPTIONS_DEFINITION => [
                self::LABEL => 'Plugin Options Definition', // _('Plugin Options Definition')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_VIRTUAL,
                self::SHY => true,
            ],
            self::FLDS_FORMAT => [
                self::LABEL => 'Format', // _('Format')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
            ],
            self::FLDS_FILENAME => [
                self::LABEL => 'Filename', // _('Filename')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_STRING,
            ],
            self::FLDS_FILTER => [
                self::LABEL => 'Filter', // _('Filter')
                self::VALIDATORS => [Zend_Filter_Input::ALLOW_EMPTY => true],
                self::TYPE => self::TYPE_TEXT,
            ],
        ]
    ];

    /**
     * get defined filter
     *
     * @TODO: implement
     * - add filterData property
     * - add persistendFilter property
     * - rethink: what to return when no filter is defined? empty filter?
     * - rethink: overwrite or combine filters / have option for that?
     *
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public function getFilter()
    {
        $filter = json_decode($this->{self::FLDS_FILTER}, true);
        return Tinebase_Model_Filter_FilterGroup::getFilterForModel($this->model, is_array($filter) ? $filter : []);
    }
}
