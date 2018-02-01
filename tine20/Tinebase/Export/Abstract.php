<?php
/**
 * Tinebase Abstract export class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase Abstract export class
 *
 * @package     Tinebase
 * @subpackage    Export
 *
 * 
 * @todo remove: Commonly used export translation strings:
 * _('Exporttime')
 */
abstract class Tinebase_Export_Abstract implements Tinebase_Record_IteratableInterface
{
    const ROW_TYPE_RECORD = 'record';
    const ROW_TYPE_GENERIC_HEADER = 'genericHeader';
    const ROW_TYPE_GROUP_HEADER = 'groupHeader';

    /**
     * default export definition name
     *
     * @var string
     */
    protected $_defaultExportname = 'default';

    /**
     * the record controller
     *
     * @var Tinebase_Controller_Record_Abstract
     */
    protected $_controller = null;

    /**
     * translation object
     *
     * @var Zend_Translate
     */
    protected $_translate;

    /**
     * locale object
     *
     * @var Zend_Locale
     */
    protected $_locale;

    /**
     * export config
     *
     * @var Zend_Config_Xml
     */
    protected $_config = array();

    /**
     * @var string application name of this export class
     */
    protected $_applicationName = null;

    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = null;

    /**
     * filter to generate export for
     *
     * @var Tinebase_Model_Filter_FilterGroup
     */
    protected $_filter = null;

    /**
     * sort records by this field (array keys: sort / dir / ...)
     *
     * @var array
     * @see Tinebase_Model_Pagination
     */
    protected $_sortInfo = array();

    /**
     * preference key if users can have different export configs
     *
     * @var string
     */
    protected $_prefKey = null;

    /**
     * format strings
     *
     * @var string
     */
    protected $_format = null;

    /**
     * custom field names for this model
     *
     * @var array
     */
    protected $_customFieldNames = null;

    /**
     * user fields to resolve
     *
     * @var array
     */
    protected $_userFields = array('created_by', 'last_modified_by', 'account_id');

    /**
     * first iteration (helper to write generic headings, etc.)
     *
     * @var boolean
     */
    protected $_firstIteration = true;

    /**
     * helper to determine if we are done with record processing
     *
     * @var bool
     */
    protected $_iterationDone = false;

    /**
     * just dump all properties of the records to _writeValue (through _getValue($field, $record) of course)
     *
     * @var boolean
     */
    protected $_dumpRecords = true;

    /**
     * write a generic header based on the properties of a record created from _modelName
     *
     * @var boolean
     */
    protected $_writeGenericHeader = true;

    /**
     * class cache for field config from _config->columns->column
     *
     * @var array
     */
    protected $_fieldConfig = array();

    /**
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array();

    /**
     * if set to true _hasTwig() will return true in any case
     *
     * @var boolean
     */
    protected $_hasTemplate = false;

    /** @var Tinebase_Twig */
    protected $_twig = null;
    /**
     * @var Twig_TemplateWrapper|null
     */
    protected $_twigTemplate = null;

    protected $_twigMapping = array();

    /**
     * @var string
     */
    protected $_templateFileName = null;

    protected $_resolvedFields = array();

    /**
     * @var Tinebase_DateTime|null
     */
    protected $_exportTimeStamp = null;

    /**
     * @var null|string
     */
    protected $_logoPath = null;

    /**
     * @var Tinebase_Record_RecordSet|null
     */
    protected $_records = null;

    protected $_lastGroupValue = null;

    protected $_groupByProperty = null;

    protected $_groupByProcessor = null;

    protected $_currentRowType = null;

    /**
     * @var Tinebase_Record_Abstract
     */
    protected $_currentRecord = null;

    protected $_getRelations = false;

    protected $_additionalRecords = array();

    protected $_keyFields = array();

    protected $_virtualFields = array();

    protected $_foreignIdFields = array();

    protected $_expandCustomFields = array();

    protected $_fields = null;

    protected $_rawData = false;

    /**
     * @var Tinebase_ModelConfiguration|null
     */
    protected $_modelConfig = null;

    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function __construct(
        Tinebase_Model_Filter_FilterGroup $_filter,
        Tinebase_Controller_Record_Interface $_controller = null,
        $_additionalOptions = array()
    ) {
        $this->_filter = $_filter;
        if (! $this->_modelName) {
            $this->_modelName = $this->_filter->getModelName();
        }
        if (! $this->_applicationName) {
            $this->_applicationName = $this->_filter->getApplicationName();
        }

        $this->_controller = ($_controller !== null) ? $_controller :
            Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName);
        $this->_translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $this->_locale = Tinebase_Core::get(Tinebase_Core::LOCALE);
        $this->_config = $this->_getExportConfig($_additionalOptions);
        if ($this->_config->template) {
            $this->_templateFileName = $this->_config->template;
        }
        if ($this->_config->templateFileId) {
            try {
                $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(Tinebase_FileSystem::getInstance()->getPathOfNode($this->_config->templateFileId, true));
                $this->_templateFileName = $path->streamwrapperpath;
            } catch (Exception $e) {}
        }
        if (isset($_additionalOptions['template'])) {
            try {
                $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(Tinebase_FileSystem::getInstance()->getPathOfNode($_additionalOptions['template'], true));
                $this->_templateFileName = $path->streamwrapperpath;
            } catch (Exception $e) {}
        }
        if (! $this->_modelName && !empty($this->_config->model)) {
            $this->_modelName = $this->_config->model;
        }
        $this->_exportTimeStamp = Tinebase_DateTime::now();

        if (!empty($this->_config->group)) {
            $this->_groupByProperty = $this->_config->group;
            $this->_sortInfo['sort'] = $this->_groupByProperty;
            if (!empty($this->_config->groupSortDir)) {
                $this->_sortInfo['dir'] = $this->_config->groupSortDir;
            }
        }

        if (isset($_additionalOptions['sortInfo'])) {
            if (isset($this->_sortInfo['sort'])) {
                $this->_sortInfo['sort'] = array_unique(array_merge((array)$this->_sortInfo['sort'],
                    (array)((isset($_additionalOptions['sortInfo']['field']) ?
                        $_additionalOptions['sortInfo']['field'] : $_additionalOptions['sortInfo']['sort']))));
            } else {
                if (isset($_additionalOptions['sortInfo']['field'])) {
                    $this->_sortInfo['sort'] = $_additionalOptions['sortInfo']['field'];
                    $this->_sortInfo['dir'] = isset($_additionalOptions['sortInfo']['direction']) ?
                        $_additionalOptions['sortInfo']['direction'] : 'ASC';
                } else {
                    $this->_sortInfo = $_additionalOptions['sortInfo'];
                }
            }
        }

        if (isset($_additionalOptions['recordData'])) {
            if (isset($_additionalOptions['recordData']['container_id']) && is_array($_additionalOptions['recordData']['container_id'])) {
                $_additionalOptions['recordData']['container_id'] = $_additionalOptions['recordData']['container_id']['id'];
            }
            $this->_records = new Tinebase_Record_RecordSet($this->_modelName,
                array(new $this->_modelName($_additionalOptions['recordData'])));
        }

        if (isset($_additionalOptions['additionalRecords'])) {
            foreach ($_additionalOptions['additionalRecords'] as $key => $value) {
                if (!isset($value['model']) || !isset($value['recordData'])) {
                    throw new Tinebase_Exception_UnexpectedValue('additionalRecords needs to specify model and recordData');
                }
                $record = new $value['model']($value['recordData']);
                $this->_additionalRecords[$key] = $record;
            }
        }

        if ($this->_config->keyFields) {
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->keyFields, 'keyField')
                     as $keyField) {
                $this->_keyFields[$keyField->propertyName] = $keyField->name;
            }
        }

        if ($this->_config->foreignIds) {
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->foreignIds, 'foreignId')
                     as $foreignId) {
                $this->_foreignIdFields[$foreignId->name] = $foreignId->controller;
            }
        }

        if ($this->_config->virtualFields) {
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->virtualFields, 'virtualField')
                     as $virtualField) {
                $this->_virtualFields[$virtualField->name] = array(
                    'relatedModel' => $virtualField->relatedModel,
                    'relatedDegree' => $virtualField->relatedDegree,
                    'type' => $virtualField->type
                );
            }
        }

        if ($this->_config->rawData) {
            $this->_rawData = true;
        }
        if (!$this->_rawData && !$this->_config->noCustomFieldExpand) {
            $disallowedKeys = Tinebase_Helper_ZendConfig::getChildrenStrings($this->_config->customfieldBlackList,
                'name');

            $cfConfigs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($this->_applicationName,
                $this->_modelName);

            /** @var Tinebase_Model_CustomField_Config $cfConfig */
            foreach ($cfConfigs as $cfConfig) {
                if (isset($disallowedKeys[$cfConfig->name])) {
                    continue;
                }
                $this->_expandCustomFields[$cfConfig->name] = empty($cfConfig->definition->label) ? $cfConfig->name :
                    $cfConfig->definition->label;
            }
        }
    }

    /**
     * get export config
     *
     * @param array $_additionalOptions additional options
     * @return Zend_Config_Xml
     * @throws Tinebase_Exception_NotFound
     */
    protected function _getExportConfig($_additionalOptions = array())
    {
        if (isset($_additionalOptions['definitionFilename'])) {
            // get definition from file
            $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile(
                $_additionalOptions['definitionFilename'],
                Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId()
            );
        } elseif (isset($_additionalOptions['definitionId'])) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->get($_additionalOptions['definitionId']);
        } else {
            // get preference from db and set export definition name
            $exportName = $this->_defaultExportname;
            if ($this->_prefKey !== null) {
                $exportName = Tinebase_Core::getPreference($this->_applicationName)->
                    getValue($this->_prefKey, $exportName);
            }

            // get export definition by name / model
            $filter = new Tinebase_Model_ImportExportDefinitionFilter(array(
                array('field' => 'model', 'operator' => 'equals', 'value' => $this->_modelName),
                array('field' => 'name',  'operator' => 'equals', 'value' => $exportName),
            ));
            $definitions = Tinebase_ImportExportDefinition::getInstance()->search($filter);
            if (count($definitions) == 0) {
                throw new Tinebase_Exception_NotFound('Export definition for model ' .
                    $this->_modelName . ' not found.');
            }
            $definition = $definitions->getFirstRecord();
        }

        $config = Tinebase_ImportExportDefinition::getInstance()->
            getOptionsAsZendConfigXml($definition, $_additionalOptions);

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' export config: ' .
                print_r($config->toArray(), true));
        }

        return $config;
    }

    protected function _getTemplateFilename()
    {
        return $this->_templateFileName;
    }

    /**
     * get export format string (csv, ...)
     *
     * @return string
     * @throws Tinebase_Exception_NotFound
     */
    public function getFormat()
    {
        if ($this->_format === null) {
            throw new Tinebase_Exception_NotFound('Format string not found.');
        }

        return $this->_format;
    }

    public static function getDefaultFormat()
    {
        return null;
    }

    /**
     * get download content type
     *
     * @return string
     */
    abstract public function getDownloadContentType();

    /**
     * return download filename
     *
     * @param string $_appName
     * @param string $_format
     * @return string
     */
    public function getDownloadFilename($_appName, $_format)
    {
        $model = '';
        if (null !== $this->_modelName) {
            /** @var Tinebase_Record_Abstract $model */
            $model = $this->_modelName;
            if (null !== ($modelConf = $model::getConfiguration())) {
                $model = '_' . $this->_translate->_($modelConf->recordName, $this->_locale);
            } else {
                $model = explode('_', $model, 3);
                if (count($model) === 3) {
                    $model = '_' . $this->_translate->_($model[2], $this->_locale);
                } else {
                    $model = '';
                }
            }
        }
        $name = '';
        if (!empty($this->_config->label)) {
            $name = '_' . $this->_translate->_($this->_config->label, $this->_locale);
        }
        $tineTranslate = Tinebase_Translation::getTranslation('Tinebase');
        return preg_replace('/\s+/', '_',
            mb_strtolower($tineTranslate->_('Export', $this->_locale) . '_' .
            $this->_translate->_($_appName, $this->_locale) . $model . $name . '.' . $_format));
    }


    /**
     * workflow
     * generate();
     * * _exportRecords();
     * * * if _hasTwig()
     * * * * _loadTwig();
     * * * * * _getTwigSource();
     * * processIteration();
     * * * _resolveRecords();
     * * * if _firstIteration && _writeGenericHeader
     * * * * _writeGenericHead();
     * * * foreach $records
     * * * * _startRow();
     * * * * _processRecord();
     * * * * _endRow();
     * * _onAfterExportRecords();
     */
    /**
     * generate export
     */
    abstract public function generate();


    /**
     * export records
     */
    protected function _exportRecords()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Starting export of ' . $this->_modelName . ' with filter: ' . print_r($this->_filter->toArray(), true)
            . ' and sort info: ' . print_r($this->_sortInfo, true));

        if (true === $this->_hasTwig()) {
            $this->_loadTwig();
        }

        $this->_onBeforeExportRecords();

        $this->_firstIteration = true;

        if (null === $this->_records) {
            $iterator = new Tinebase_Record_Iterator(array(
                'iteratable' => $this,
                'controller' => $this->_controller,
                'filter' => $this->_filter,
                'options' => array(
                    'searchAction' => 'export',
                    'sortInfo' => $this->_sortInfo,
                    'getRelations' => $this->_getRelations,
                ),
            ));

            if (false === ($result = $iterator->iterate())) {
                $result = array(
                    'totalcount' => $this->_records->count(),
                    'results'    => array(),
                );
            }
        } else {
            $totalCount = 0;
            $totalCountFn = function(&$val) use (&$totalCountFn, &$totalCount) {
                if (is_array($val)) {
                    foreach ($val as &$a) {
                        $totalCountFn($a);
                    }
                } else {
                    /** @var Tinebase_Record_RecordSet $val */
                    $totalCount += $val->count();
                }
            };
            $totalCountFn($this->_records);

            $result = array(
                'totalcount' => $totalCount,
                'results'    => array(),
            );
            $result['results'][] = $this->processIteration($this->_records);
        }

        $this->_onAfterExportRecords($result);

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Exported ' . $result['totalcount'] . ' records.');
    }

    protected function _onBeforeExportRecords()
    {
    }

    /**
     * @return bool
     */
    protected function _hasTwig()
    {
        if (true === $this->_hasTemplate) {
            return true;
        }
        if ($this->_config->columns) {
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
                if ($column->twig) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function _loadTwig()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' loading twig template...');

        $options = [
            // in order to cache the templates, we need to cache $this->_twigMapping too!
            Tinebase_Twig::TWIG_CACHE       => false,
            Tinebase_Twig::TWIG_AUTOESCAPE  => 'json',
            Tinebase_Twig::TWIG_LOADER      => new Twig_Loader_Chain(array(
                new Tinebase_Twig_CallBackLoader($this->_templateFileName, $this->_getLastModifiedTimeStamp(),
                    array($this, '_getTwigSource'))))
        ];

        $this->_twig = new Tinebase_Twig($this->_locale, $this->_translate, $options);

        $this->_twigTemplate = $this->_twig->load($this->_templateFileName);
    }

    /**
     * @return string
     */
    public function _getTwigSource()
    {
        $source = '[';
        if (true !== $this->_hasTemplate && $this->_config->columns) {
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
                if ($column->twig) {
                    $source .= ($source!=='' ? ',"' : '""') . (string)$column->twig . '"';
                }
            }
        }
        return $source . ']';
    }

    /**
     * @return int
     */
    protected function _getLastModifiedTimeStamp()
    {
        return filemtime($this->_templateFileName);
    }

    protected function _getCurrentState()
    {
        return array(
            '_firstIteration'       => $this->_firstIteration,
            '_writeGenericHeader'   => $this->_writeGenericHeader,
            '_groupByProperty'      => $this->_groupByProperty,
            '_groupByProcessor'     => $this->_groupByProcessor,
            '_lastGroupValue'       => $this->_lastGroupValue,
            '_currentRecord'        => $this->_currentRecord,
            '_currentRowType'       => $this->_currentRowType,
            '_twigTemplate'         => $this->_twigTemplate,
            '_twigMapping'          => $this->_twigMapping,
        );
    }

    protected function _setCurrentState(array $array)
    {
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * add body rows
     *
     * @param Tinebase_Record_RecordSet|array $_records
     */
    public function processIteration($_records)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' iterating over export data...');

        if (is_array($_records)) {

            foreach ($_records as $key => $value) {
                $this->_startDataSource($key);

                $this->processIteration($value);

                $this->_endDataSource($key);
            }

            return;
        }

        $this->_resolveRecords($_records);

        if (true === $this->_firstIteration && true === $this->_writeGenericHeader) {
            $this->_writeGenericHead();
        }

        $first = $this->_firstIteration;
        foreach ($_records as $record) {
            if (null !== $this->_groupByProperty) {
                $propertyValue = $record->{$this->_groupByProperty};
                if (null !== $this->_groupByProcessor) {
                    /** @var closure $fn */
                    $fn = $this->_groupByProcessor;
                    $fn($propertyValue);
                }
                if (true === $first || $this->_lastGroupValue !== $propertyValue) {
                    $this->_lastGroupValue = $propertyValue;
                    if (false === $first) {
                        $this->_endGroup();
                    }
                    $this->_currentRecord = $record;
                    $this->_startGroup();
                }
                // TODO fix this?
                //$this->_writeGroupHeading($record);
            }
            $this->_currentRecord = $record;

            $this->_currentRowType = self::ROW_TYPE_RECORD;

            $this->_startRow();

            $this->_processRecord($record);

            $this->_endRow();

            if (true === $first) {
                $first = false;
            }
        }

        if ($_records->count() > 0 && null !== $this->_groupByProperty) {
            $this->_endGroup();
        }

        $this->_firstIteration = false;
    }

    /**
     * @param $_name
     */
    protected function _startDataSource($_name)
    {
    }

    /**
     * @param $_name
     */
    protected function _endDataSource($_name)
    {
    }

    protected function _startGroup()
    {
    }

    protected function _endGroup()
    {
    }

    protected function _writeGroupHeading(Tinebase_Record_Interface $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' writting group heading...');

        $this->_currentRowType = self::ROW_TYPE_GROUP_HEADER;

        $this->_startRow();

        $this->_writeValue($_record->{$this->_groupByProperty});

        $this->_endRow();
    }

    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' resolving export records...');
        if ($_records->count() === 0) {
            return;
        }
        $record = $_records->getFirstRecord();
        // FIXME think what to do
        // TODO fix ALL this!
        // this is code present in the abstract controller, getRelatedData... why is it here?

        // get field types/identifiers from config
        $identifiers = array();
        if ($this->_config->columns) {
            $types = array();
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
                $types[] = $column->type;
                $identifiers[] = $column->identifier;
            }
            $types = array_unique($types);
        } else {
            $types = $this->_resolvedFields;
        }

        // resolve users
        foreach ($this->_userFields as $field) {
            if (in_array($field, $types) || in_array($field, $identifiers)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Resolving users for ' . $field);
                Tinebase_User::getInstance()->resolveMultipleUsers($_records, $field, true);
            }
        }

        // add notes
        if (in_array('notes', $types)) {
            Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records, 'notes', 'Sql', false);
        }

        // add container
        if (in_array('container_id', $types)) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::getUser());
        }

        if ($record->has('customfields')) {
            $_records->customfields = array();
            Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($_records, true);
            if (!empty($this->_expandCustomFields)) {
                $validators = $_records->getFirstRecord()->getValidators();
                foreach ($this->_expandCustomFields as $field => $label) {
                    if (!isset($validators[$field])) {
                        $validators[$field] = [];
                    }
                }
                /** @var Tinebase_Record_Abstract $record */
                foreach ($_records as $record) {
                    $record->setValidators($validators);
                    foreach ($this->_expandCustomFields as $field => $label) {
                        if (isset($record->customfields[$field])) {
                            $record->{$field} = $record->customfields[$field];
                        }
                    }
                }
            }
        }

        /** @var Tinebase_Record_Abstract $modelName */
        $modelName = $_records->getRecordClassName();

        if ($record->has('relations')) {
            $relations = Tinebase_Relations::getInstance()->getMultipleRelations($modelName, 'Sql',
                $_records->getArrayOfIds());
            $this->_resolveRelationsType($relations);

            /** @var Tinebase_Record_Abstract $record */
            foreach ($_records as $idx => $record) {
                if (isset($relations[$idx])) {
                    $record->relations = $relations[$idx];
                }
            }
        }

        $appConfig = Tinebase_Config::factory($this->_applicationName);
        $this->_modelConfig = $modelName::getConfiguration();

        if (null === $this->_modelConfig && $_records->getRecordClassName() === $this->_modelName) {
            /** @var Tinebase_Record_Abstract $record */
            foreach ($_records as $idx => $record) {
                foreach ($this->_virtualFields as $name => $virtualField) {
                    $value = null;
                    if (!empty($record->relations)) {
                        /** @var Tinebase_Model_Relation $relation */
                        foreach ($record->relations as $relation) {
                            if ($relation->related_model === $virtualField['relatedModel'] &&
                                $relation->related_degree === $virtualField['relatedDegree'] &&
                                $relation->type === $virtualField['type']
                            ) {
                                $value = $relation->related_record;
                                break;
                            }
                        }
                    }
                    $record->{$name} = $value;
                }

                foreach ($this->_foreignIdFields as $name => $controller) {
                    if (!empty($record->{$name})) {
                        /** @var Tinebase_Controller_Record_Abstract $controller */
                        $controller = $controller::getInstance();
                        $record->{$name} = $controller->get($record->{$name});
                    }
                }
            }
        } elseif ($this->_modelConfig) {
            $this->_modelConfig->resolveRecords($_records);
            $this->_keyFields = [];
            foreach ($this->_modelConfig->keyfieldFields as $property) {
                $this->_keyFields[$property] = $this->_modelConfig->getFields()[$property]['name'];
            }
        }

        foreach ($this->_keyFields as $name => $keyField) {
            /** @var Tinebase_Config_KeyField $keyField */
            $keyField = $appConfig->{$keyField};
            $record->{$name} = $keyField->getTranslatedValue($record->{$name});
        }

        $_records->setTimezone(Tinebase_Core::getUserTimezone());
    }

    protected function _resolveRelationsType(array $relations)
    {
        $models = array();
        foreach($relations as $rels) {
            $models = array_merge($models, $rels->own_model);
            $models = array_merge($models, $rels->related_model);
        }
        $models = array_unique($models);
        $relConfig = Tinebase_Relations::getConstraintsConfigs($models);
        if (empty($relConfig)) {
            return;
        }

        foreach ($relations as $rels) {
            /** @var Tinebase_Model_Relation $relation */
            foreach ($rels as $relation) {
                $text = null;
                $relatedApp = null;
                $revertedText = null;
                $revertedRelatedApp = null;
                foreach ($relConfig as $cfg) {
                    if ($cfg['ownRecordClassName'] === $relation->own_model && $cfg['relatedRecordClassName'] ===
                            $relation->related_model && isset($cfg['config'])) {
                        foreach ($cfg['config'] as $cfg1) {
                            if ($relation->type === $cfg1['type'] && $relation->related_degree === $cfg1['degree']) {
                                if (isset($cfg['reverted'])) {
                                    $revertedText = $cfg1['text'];
                                    $revertedRelatedApp = $cfg['relatedApp'];
                                } else {
                                    $relatedApp = $cfg['relatedApp'];
                                    $text = $cfg1['text'];
                                    break 2;
                                }
                            }
                        }

                        if ($text === null && $revertedText === null) {
                            foreach ($cfg['config'] as $cfg1) {
                                if ($relation->type === $cfg1['type']) {
                                    $relatedApp = $cfg['relatedApp'];
                                    $text = $cfg1['text'];
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if (null === $text && null !== $revertedText) {
                    $text = $revertedText;
                    $relatedApp = $revertedRelatedApp;
                }

                if (null !== $text) {
                    $translatedStr = $this->_translate->_($text, $this->_locale);
                    if ($translatedStr === $text) {
                        $translatedStr = Tinebase_Translation::getTranslation($relatedApp, $this->_locale)
                            ->translate($text, $this->_locale);
                        if ($translatedStr === $text) {
                            $translatedStr = Tinebase_Translation::getTranslation('Tinebase', $this->_locale)
                                ->translate($text, $this->_locale);
                        }
                    }
                    $relation->type = $translatedStr;
                }
            }
        }
    }

    protected function _writeGenericHead()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' writing generic header...');

        $this->_currentRowType = self::ROW_TYPE_GENERIC_HEADER;

        $this->_startRow();

        if ($this->_config->columns) {
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
                if ($column->header) {
                    $this->_writeValue($column->header);
                } elseif ($column->recordProperty) {
                    $this->_writeValue($column->recordProperty);
                } else {
                    $this->_writeValue('');
                }
            }
        } else {
            /** @var Tinebase_Record_Abstract $record */
            $record = new $this->_modelName(array(), true);

            $this->_fields = $record->getFields();
            if (!$this->_config->rawData) {
                if (null !== $this->_modelConfig) {
                    $modelConfigFields = $this->_modelConfig->getFields();
                } else {
                    $modelConfigFields = null;
                }
                
                $systemFields = [];
                
                foreach($this->_fields as $field) {
                    if (isset($modelConfigFields[$field]) && isset($modelConfigFields[$field]['system']) && $modelConfigFields[$field]['system'] === true) {
                        $systemFields[] = $field;
                    }   
                }
                
                $this->_fields = array_merge(
                    array_diff($this->_fields, array_merge(['customfields'], $systemFields)),
                    array_keys($this->_expandCustomFields)
                );
                
                foreach ($this->_fields as $field) {
                    if (isset($this->_expandCustomFields[$field])) {
                        $field = $this->_expandCustomFields[$field];
                    } elseif (null !== $modelConfigFields) {
                        if (isset($modelConfigFields[$field]) && isset($modelConfigFields[$field]['label'])) {
                            $field = $modelConfigFields[$field]['label'];
                        }
                    }
                    
                    $this->_writeValue($this->_translate->_($field) ?: $field);
                }
            } else {
                foreach ($this->_fields as $field) {
                    $this->_writeValue($field);
                }
            }
        }

        $this->_endRow();
    }

    protected function _startRow()
    {
    }

    /**
     * @param Tinebase_Record_Interface $_record
     */
    protected function _processRecord(Tinebase_Record_Interface $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' processing a export record...');

        if (true === $this->_dumpRecords) {
            foreach (empty($this->_fields) ? $_record->getFields() : $this->_fields as $field) {
                if ($this->_rawData === false) {
                    if ($this->_modelConfig && isset($this->_modelConfig->getFields()[$field]) && isset($this->_modelConfig->getFields()[$field]['system']) && $this->_modelConfig->getFields()[$field]['system'] === true) {
                        continue;
                    } 
                }
                
                $this->_writeValue($this->_convertToString($_record->{$field}));
            }
        } elseif (true !== $this->_hasTemplate) {
            $twigResult = array();
            if (null !== $this->_twigTemplate) {
                $result = json_decode($this->_twigTemplate->render(
                    $this->_getTwigContext(array('record' => $_record))));
                if (is_array($result)) {
                    $twigResult = $result;
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                        ' twig render and json_decode did not return an array: ' . print_r($result, true));
                }
            }
            $twigCounter = 0;
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
                if ($column->twig) {
                    if (isset($twigResult[$twigCounter]) || array_key_exists($twigCounter, $twigResult)) {
                        $this->_writeValue($this->_convertToString($twigResult[$twigCounter]));
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                            ' twig column: ' . $column->twig . ' not found in twig result array');
                        $this->_writeValue('');
                    }
                } elseif ($column->recordProperty) {
                    $this->_writeValue($this->_convertToString($_record->{$column->recordProperty}));
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .
                        ' pointless column found: ' . print_r($column, true));
                }
            }
        } elseif (null !== $this->_twigTemplate) {
            $this->_renderTwigTemplate($_record);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' can not process record, misconfigured!');
        }
    }

    /**
     * @param Tinebase_Record_Interface|null $_record
     */
    protected function _renderTwigTemplate($_record = null)
    {
        $twigResult = $this->_twigTemplate->render(
            $this->_getTwigContext(array('record' => $_record)));
        $twigResult = json_decode($twigResult);
        if (!is_array($twigResult)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                ' twig render and json_decode did not return an array: ' . print_r($twigResult, true));
            return;
        }

        foreach ($this->_twigMapping as $key => $twigKey) {
            if (isset($twigResult[$key]) || array_key_exists($key, $twigResult)) {
                $value = $this->_convertToString($twigResult[$key]);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                    ' twig mapping: ' . $key . ' ' . $twigKey . ' not found in twig result array');
                $value = '';
            }
            $this->_setValue($twigKey, $value);
        }
    }

    /**
     * @param array $context
     * @return array
     */
    protected function _getTwigContext(array $context)
    {
        if (null === $this->_logoPath) {
            $this->_logoPath = Tinebase_Helper::getFilename(Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_LOGO}, false);
        }

        $contact = Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId());
        
        return array_merge(array(
            'branding'          => array(
                'logo'              => $this->_logoPath,
                'title'             => Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_TITLE},
                'description'       => Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_DESCRIPTION},
                'weburl'            => Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_WEBURL},
            ),
            'export'            => array(
                'timestamp'         => $this->_exportTimeStamp,
                'account'           => Tinebase_Core::getUser(),
                'contact'           => $contact,
                'groupdata'         => $this->_lastGroupValue,
            ),
            'additionalRecords' => $this->_additionalRecords,
        ), $context);
    }

    /**
     * @param string $_key
     * @param string $_value
     */
    abstract protected function _setValue($_key, $_value);

    /**
     * @param string $_value
     */
    abstract protected function _writeValue($_value);

    /**
     * @param mixed $_value
     * @return string
     */
    protected function _convertToString($_value)
    {
        if (is_object($_value)) {
            if ($this->_rawData) {
                if ($_value instanceof DateTime) {
                    $_value = $_value->format('Y-m-d H:i:s');
                } elseif (method_exists($_value, 'toArray')) {
                    $_value = $_value->toArray();
                } elseif (method_exists($_value, 'getId')) {
                    $_value = $_value->getId();
                } elseif (method_exists($_value, '__toString')) {
                    $_value = $_value->__toString();
                } else {
                    $_value = '';
                }
            } else {
                if ($_value instanceof DateTime) {
                    $_value = Tinebase_Translation::dateToStringInTzAndLocaleFormat($_value, null, null,
                        $this->_config->datetimeformat);
                } elseif ($_value instanceof Tinebase_Model_CustomField_Config) {
                    if (is_array($_value->value)) {
                        $value = '';
                        $model = Tinebase_CustomField::getModelNameFromDefinition($_value->definition);
                        if (strtolower($_value->definition['type']) === 'record') {
                            $value = new $model($_value->value, true);
                        } elseif (strtolower($_value->definition['type']) === 'recordlist') {
                            $value = new Tinebase_Record_RecordSet($model, $_value->value, true);
                        }
                        $_value = $this->_convertToString($value);
                    } else {
                        $_value = $this->_convertToString($_value->value);
                    }
                } elseif ($_value instanceof Tinebase_Record_Abstract) {
                    $_value = $_value->getTitle();
                } elseif ($_value instanceof Tinebase_Record_RecordSet) {
                    $_value = join(', ', $_value->getTitle());
                } elseif (method_exists($_value, '__toString')) {
                    $_value = $_value->__toString();
                } else {
                    $_value = '';
                }
            }
        }

        // do not elseif this
        if (!is_scalar($_value)) {
            if ($this->_rawData && is_array($_value)) {
                $_value = json_encode($_value);
            } else {
                $_value = '';
            }
        }

        return (string)$_value;
    }

    protected function _endRow()
    {
    }

    /**
     * set generic data
     *
     * @param array $result
     */
    protected function _onAfterExportRecords(/** @noinspection PhpUnusedParameterInspection */ array $result)
    {
        $this->_iterationDone = true;

        if (null !== $this->_twigTemplate) {
            $this->_renderTwigTemplate();
        }
    }

    /**
     * @return Zend_Translate|Zend_Translate_Adapter
     */
    public function getTranslate()
    {
        return $this->_translate;
    }
}
