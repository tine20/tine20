<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  ModelConfiguration
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_ModelConfiguration_Const provides constants
 *
 * @package     Tinebase
 * @subpackage  ModelConfiguration
 */

class Tinebase_ModelConfiguration_Const {
    const ADD_FILTERS = 'addFilters';
    const APPLICATION = 'application';
    const APP_NAME = 'appName';
    const ASSOCIATIONS = 'associations';
    const AVAILABLE_MODELS = 'availableModels';

    /**
     * additional boxLabel for checkboxes
     */
    const BOX_LABEL = 'boxLabel';
    
    const CONFIG = 'config';
    const CONTROLLER = 'controller';
    const CONTROLLER_CLASS_NAME = 'controllerClassName';
    const CONTROLLER_HOOK_BEFORE_UPDATE = 'controllerHookBeforeUpdate';
    const CONVERTERS = 'converters';
    const COLUMNS = 'columns';
    const COPY_OMIT = 'copyOmit';
    const COPY_RELATIONS = 'copyRelations';
    const CREATE_MODULE = 'createModule';
    const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';

    const DB_COLUMNS = 'dbColumns';
    /**
     * default sort info
     *
     * example: ['field' => 'number', 'direction' => 'DESC']
     */
    const DEFAULT_SORT_INFO = 'defaultSortInfo';
    const DEFAULT_VAL = 'default';
    const DEGREE = 'degree';
    const DEPENDENT_RECORDS = 'dependentRecords';
    /**
     * UI ONLY - If this is set to true, the field can't be updated and will not be shown in the frontend
     */
    const DISABLED = 'disabled';
    const DOCTRINE_IGNORE = 'doctrineIgnore';

    const EXPORT = 'export';
    const EXPOSE_HTTP_API = 'exposeHttpApi';
    const EXPOSE_JSON_API = 'exposeJsonApi';

    const FIELDS = 'fields';
    const FIELD_NAME = 'fieldName';
    const FILTER = 'filter';
    const FILTER_CLASS_NAME = 'filterClassName';
    const FILTER_DEFINITION = 'filterDefinition';
    const FILTER_GROUP = 'filtergroup';
    const FILTER_OPTIONS = 'filterOptions';
    const FLAGS = 'flags';
    const FLD_CONTAINER_ID = 'container_id';
    const FLD_DELETED_TIME = 'deleted_time';
    const FUNCTION = 'function';

    const GRANTS_MODEL = 'grantsModel';

    const HAS_ATTACHMENTS = 'hasAttachments';
    const HAS_CUSTOM_FIELDS = 'hasCustomFields';
    const HAS_DELETED_TIME_UNIQUE = 'hasDeletedTimeUnique';
    const HAS_NOTES = 'hasNotes';
    const CONTAINER_PROPERTY = 'containerProperty';
    const HAS_PERSONAL_CONTAINER = 'hasPersonalContainer';
    const CONTAINER_NAME = 'containerName';
    const CONTAINERS_NAME = 'containersName';
    const HAS_RELATIONS = 'hasRelations';
    const HAS_SYSTEM_CUSTOM_FIELDS = 'hasSystemCustomFields';
    const HAS_TAGS = 'hasTags';
    const HAS_XPROPS = 'hasXProps';

    const ID = 'id';
    const ID_GENERATOR_TYPE = 'idGeneratorType';
    const INDEXES = 'indexes';
    const INPUT_FILTERS = 'inputFilters';
    const IS_DEPENDENT = 'isDependent';
    const IS_VIRTUAL = 'isVirtual';

    const JOIN_COLUMNS = 'joinColumns';

    const LABEL = 'label';
    const LENGTH = 'length';

    const MODEL_NAME = 'modelName';
    const MODLOG_ACTIVE = 'modlogActive';

    const NAME = 'name';
    const NULLABLE = 'nullable';

    const OMIT_MOD_LOG = 'modlogOmit';
    const ON_DELETE = 'onDelete';
    const ON_UPDATE = 'onUpdate';
    const OPTIONS = 'options';
    // used for example by system customfields. Tells the receiving model, that this property originates from a different app
    // relevant for translation, keyfields, etc.
    const OWNING_APP = 'owningApp';

    const PAGING = 'paging';
    const PERSISTENT = 'persistent';

    const QUERY_FILTER = 'queryFilter';

    /**
     * If this is set to true, the field can't be updated in BE and will be shown as readOnly in the frontend
     */
    const READ_ONLY = 'readOnly';
    const REFERENCED_COLUMN_NAME = 'referencedColumnName';
    const REF_ID_FIELD = 'refIdField';
    const REF_MODEL_FIELD = 'refModelField';
    const RECORD_CLASS_NAME = 'recordClassName';
    const RECORD_NAME = 'recordName';
    const RECORDS_NAME = 'recordsName';
    const RESOLVE_DELETED = 'resolveDeleted';
    const RUN_CONVERT_TO_RECORD_FROM_JSON = 'runConvertToRecordFromJson';

    /**
     * frontends do not show this field in grids per default
     */
    const SHY = 'shy';
    const SINGULAR_CONTAINER_MODE = 'singularContainerMode';
    const SPECIAL_TYPE = 'specialType';
    const SPECIAL_TYPE_DURATION_SEC = 'durationSec';
    const SPECIAL_TYPE_PASSWORD = 'password';
    const STORAGE = 'storage';
    const SUPPORTED_FORMATS = 'supportedFormats';
    const SYSTEM = 'system';

    const TABLE = 'table';
    const TARGET_ENTITY = 'targetEntity';
    const TITLE_PROPERTY = 'titleProperty';
    const TOOLTIP = 'tooltip';
    const TYPE = 'type';
    const TYPE_ATTACHMENTS = 'attachments';
    const TYPE_BIGINT = 'bigint';
    const TYPE_BLOB = 'blob';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_CONTAINER = 'container';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE = 'date';
    const TYPE_DYNAMIC_RECORD = 'dynamicRecord';
    const TYPE_FLOAT = 'float';
    const TYPE_FULLTEXT = 'fulltext';

    /**
     * Colour in the web standard hexadecimal format (#000000 to #FFFFFF)
     */
    const TYPE_HEX_COLOR = 'hexcolor';

    const TYPE_INTEGER = 'integer';
    const TYPE_JSON = 'json';
    const TYPE_KEY_FIELD = 'keyfield';
    const TYPE_LABEL = 'label';
    const TYPE_MODEL = 'model';
    const TYPE_MONEY = 'money';
    const TYPE_NOTE = 'note';
    const TYPE_NUMBERABLE_INT = 'numberableInt';
    const TYPE_NUMBERABLE_STRING = 'numberableStr';
    const TYPE_RECORD = 'record';
    const TYPE_RECORDS = 'records';
    const TYPE_RELATION = 'relation';
    const TYPE_RELATIONS = 'relations';
    const TYPE_STRICTFULLTEXT = 'strictFulltext';
    const TYPE_STRING = 'string';
    const TYPE_STRING_AUTOCOMPLETE = 'stringAutocomplete';
    const TYPE_TAG = 'tag';
    const TYPE_TEXT = 'text';
    const TYPE_TIME = 'time';
    const TYPE_USER = 'user';
    const TYPE_VIRTUAL = 'virtual';

    const UNIQUE_CONSTRAINTS = 'uniqueConstraints';
    const UNSIGNED = 'unsigned';
    const UI_CONFIG = 'uiconfig';

    const VALIDATORS = 'validators';
    const VERSION = 'version';
}
