<?php
/**
 * Tinebase Abstract export class
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * translation object
     *
     * @var Zend_Translate
     */
    protected $_tinebaseTranslate;

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
    protected $_config = null;

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
     * export definition
     *
     * @var Tinebase_Model_ImportExportDefinition
     */
    protected $_definition = null;

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
    
    protected $_twigExtensions = [];

    /**
     * @var string
     */
    protected $_templateFileName = null;

    protected $_resolvedFields = array();

    /**
     * @var Tinebase_DateTime|null
     */
    protected $_exportTimeStamp = null;

    protected $_baseContext = null;

    /**
     * @var Tinebase_Record_RecordSet|null
     */
    protected $_records = null;

    protected $_currentIterationRecords = null;

    protected $_lastGroupValue = null;

    protected $_groupByProperty = null;

    protected $_groupByProcessor = null;

    protected $_currentRowType = null;

    /**
     * @var Tinebase_Record_Interface
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

    protected $_FEDataRecordResolving = false;

    /**
     * @var array
     */
    protected $_customFieldsNameLocalLabelMapping = [];

    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function __construct(
        Tinebase_Model_Filter_FilterGroup $_filter = null,
        Tinebase_Controller_Record_Interface $_controller = null,
        $_additionalOptions = array()
    ) {
        if (null === $_filter) {
            if (!isset($_additionalOptions['definitionId']) && !isset($_additionalOptions['definitionFilename'])) {
                throw new Tinebase_Exception_InvalidArgument('no filter provided and no definitionId or name given');
            }
            $this->_config = $this->_getExportConfig($_additionalOptions);
            $_filter = $this->_definition->getFilter();
        }

        $this->_filter = $_filter;
        if (!$this->_modelName) {
            $this->_modelName = $this->_filter->getModelName();
        }
        if (!$this->_applicationName) {
            $this->_applicationName = $this->_filter->getApplicationName();
        }

        $this->_controller = ($_controller !== null)
            ? $_controller
            : $this->_getController(isset($_additionalOptions['ignoreACL']) && $_additionalOptions['ignoreACL']);

        $this->_translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $this->_tinebaseTranslate = Tinebase_Translation::getTranslation('Tinebase');
        $this->_locale = Tinebase_Core::getLocale();
        if (null === $this->_config) {
            $this->_config = $this->_getExportConfig($_additionalOptions);
        }
        if (null !== $this->_config->header) {
            $this->_writeGenericHeader = (bool)$this->_config->header;
        }
        if ($this->_config->template) {
            $this->_templateFileName = $this->_parseTemplatePath($this->_config->template);
        }
        if ($this->_config->templateFileId) {
            try {
                $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(Tinebase_FileSystem::getInstance()->getPathOfNode($this->_config->templateFileId,
                    true));
                $this->_templateFileName = $path->streamwrapperpath;
            } catch (Exception $e) {
            }
        }
        if (isset($_additionalOptions['template'])) {
            try {
                $path = Tinebase_Model_Tree_Node_Path::createFromStatPath(Tinebase_FileSystem::getInstance()->getPathOfNode($_additionalOptions['template'],
                    true));
                $this->_templateFileName = $path->streamwrapperpath;
            } catch (Exception $e) {
            }
        }
        if (!$this->_modelName && !empty($this->_config->model)) {
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

        if (!isset($this->_sortInfo['sort']) && null !== $this->_config->sort) {
            $this->_sortInfo['sort'] = $this->_config->sort->field;
            $this->_sortInfo['dir'] = $this->_config->sort->direction ?: 'ASC';
        }

        if (!isset($this->_sortInfo['sort']) && !empty($this->_modelName)) {
            /** @var Tinebase_Record_Interface $mc */
            $mc = $this->_modelName;
            if (null !== ($mc = $mc::getConfiguration())) {
                /** @var Tinebase_ModelConfiguration $mc */
                $titleProp = $mc->titleProperty;
                if (is_array($titleProp)) {
                    $titleProp = $titleProp[1];
                } else {
                    $titleProp = [$titleProp];
                }
                $sort = [];
                foreach ($titleProp as $prop) {
                    if ($mc->hasField($prop) && !array_key_exists($prop, $mc->getVirtualFields())) {
                        $sort[] = $prop;
                    }
                }
                if (count($sort) > 0) {
                    $this->_sortInfo['sort'] =  $sort;
                }
            }
        }

        if (isset($_additionalOptions['recordData'])) {
            if (isset($_additionalOptions['recordData']['container_id']) && is_array($_additionalOptions['recordData']['container_id'])) {
                $_additionalOptions['recordData']['container_id'] = $_additionalOptions['recordData']['container_id']['id'];
            }
            $this->_FEDataRecordResolving = true;
            /** @var Tinebase_Record_Interface $record */
            $record = new $this->_modelName([], true);
            $record->setFromJsonInUsersTimezone($_additionalOptions['recordData']);
            $this->_records = new Tinebase_Record_RecordSet($this->_modelName, [$record]);
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
                $this->_keyFields[$keyField->propertyName] = [
                    'name' => $keyField->name,
                    'application' => $keyField->application ? $keyField->application : $this->_applicationName
                ];
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

        $customFieldConfigs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication(
            $this->_applicationName, $this->_modelName);

        /** @var Tinebase_Model_CustomField_Config $cfConfig */
        foreach ($customFieldConfigs as $cfConfig) {
            $label = empty($cfConfig->definition->label) ? $cfConfig->name : $cfConfig->definition->label;
            $name = $this->_translate->_($label);

            if (empty($name) || $name === $label) {
                $name = $this->_tinebaseTranslate->_($label);
                if (empty($name)) {
                    $name = $label;
                }
            }
            $this->_customFieldsNameLocalLabelMapping[$cfConfig->name] = $name;
        }

        if ($this->_config->rawData) {
            $this->_rawData = true;
        }
        if (!$this->_rawData && !$this->_config->noCustomFieldExpand) {
            $disallowedKeys = Tinebase_Helper_ZendConfig::getChildrenStrings($this->_config->customfieldBlackList,
                'name');

            /** @var Tinebase_Model_CustomField_Config $cfConfig */
            foreach ($customFieldConfigs as $cfConfig) {
                if (isset($disallowedKeys[$cfConfig->name])) {
                    continue;
                }
                $this->_expandCustomFields[$cfConfig->name] = empty($cfConfig->definition->label) ? $cfConfig->name :
                    $cfConfig->definition->label;
            }
        }
    }

    protected function _getController($ignoreAcl = false)
    {
        return Tinebase_Core::getApplicationInstance(
            $this->_applicationName,
            $this->_modelName,
            $ignoreAcl
        );
    }

    protected function _parseTemplatePath($_path)
    {
        if (strpos($_path, 'tine20://') !== 0) {
            return $_path;
        }

        $versionConstraint = null;
        if (preg_match('#/([^/]+)-v([\. \d\^\~\|]+)(\.[^./]+)$#', $_path, $match) && preg_match('/\d/', $match[2])) {
            $versionConstraint = $match[2];
            $startsWith = $match[1];
            $endsWith = $match[3];
        } else {
            $pathParts = pathinfo($_path);
            $startsWith = $pathParts['filename'];
            $endsWith = '.' . $pathParts['extension'];
        }
        $dir = dirname(substr($_path, 9));
        $parent = Tinebase_FileSystem::getInstance()->stat($dir);
        $match = null;
        $matchVersion = null;
        $fileNameRegex = '/^' . preg_quote($startsWith, '/') . '(-v[\.\d]+)?' . preg_quote($endsWith, '/') . '$/';

        /** @var Tinebase_Model_Tree_Node $node */
        foreach (Tinebase_FileSystem::getInstance()->getTreeNodeChildren($parent) as $node) {
            if (preg_match($fileNameRegex, $node->name)) {
                if (null !== $versionConstraint) {
                    if (preg_match('/-v([\.\d]+)\.[^\.]+$/', $node->name, $vers) &&
                            \Composer\Semver\Semver::satisfies($vers[1], $versionConstraint) && (null === $matchVersion
                            || version_compare($matchVersion, $vers[1]) < 1)) {
                        $match = $node;
                        $matchVersion = $vers[1];
                    }
                } else {
                    if (null === $match) {
                        $match = $node;
                    } else {
                        if (preg_match('/-v([\.\d]+)\.[^\.]+$/', $match->name, $oldVer) &&
                                preg_match('/-v([\.\d]+)\.[^\.]+$/', $node->name, $newVers) &&
                                version_compare($oldVer[1], $newVers[1]) < 1) {
                            $match = $node;
                        }
                    }
                }
            }
        }

        if (null === $match) {
            throw new Tinebase_Exception('could not find template for path: ' . $_path);
        }
        return Tinebase_Model_Tree_Node_Path::createFromStatPath(Tinebase_FileSystem::getInstance()->getPathOfNode(
            $match, true))->streamwrapperpath;
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
            $filter = Tinebase_Model_Filter_FilterGroup::getFilterForModel(Tinebase_Model_ImportExportDefinition::class, array(
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

        $this->_definition = $definition;
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
    public function getDownloadFilename($_appName = null, $_format = null)
    {
        if (! $_appName) {
            $_appName = $this->_applicationName;
        }
        if (! $_format) {
            $_format = $this->_format;
        }

        if (isset($this->_config->exportFilename) && $this->_hasTwig()) {
            $this->_twig->addLoader(new Twig_Loader_Array(['fileNameTmpl' => $this->_config->exportFilename]));
            $twigTmpl = $this->_twig->load('fileNameTmpl');
            return $twigTmpl->render($this->_getTwigContext([]));
        }

        $model = '';
        if (null !== $this->_modelName) {
            /** @var Tinebase_Record_Interface $model */
            $model = $this->_modelName;
            if (null !== ($modelConf = $model::getConfiguration())) {
                $model = ' ' . $this->_translate->_($modelConf->recordName, $this->_locale);
            } else {
                $model = explode(' ', $model, 3);
                if (count($model) === 3) {
                    $model = ' ' . $this->_translate->_($model[2], $this->_locale);
                } else {
                    $model = '';
                }
            }
        }
        
        $name = '';
        if (!empty($this->_config->label)) {
            
            if ($model !== '') {
                $name .= ' ';
            }
            
            $name .= $this->_translate->_($this->_config->label, $this->_locale);
        }
        $tineTranslate = Tinebase_Translation::getTranslation('Tinebase');
        $result = mb_strtolower($tineTranslate->plural('Export', 'Exports', 1, $this->_locale) . '_' .
            $this->_translate->_($_appName, $this->_locale)
            . ($model !== '' ? $model : '_')
            . $name . '.' . $_format);
        return str_replace([' ', '/'], '_', $result);
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
                    'totalcount' => 0,
                    'results'    => [],
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
        foreach ($this->_twigExtensions as $extension) {
            $this->_twig->addExtension($extension);
        }

        $this->_extendTwigSetup();

        $this->_twigTemplate = $this->_twig->load($this->_templateFileName);
    }

    protected function _extendTwigSetup()
    {
        // the concrete class may do stuff to the twig environment here before we load the template
        // example:
        // $this->_twig->getEnvironment()->addFunction(new Twig_SimpleFunction(...));
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
            '_keyFields'            => $this->_keyFields,
            '_virtualField'         => $this->_virtualFields,
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
    public function processIteration($_records, $_resolveRecords = true)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' iterating over export data...');

        if (is_array($_records)) {

            foreach ($_records as $key => $value) {

                $this->_currentIterationRecords = $value;
                if ($_resolveRecords) {
                    $this->_resolveRecords($value);
                }

                $this->_startDataSource($key);

                $this->processIteration($value, false);

                $this->_endDataSource($key);
            }

            return;
        }

        $this->_currentIterationRecords = $_records;
        if ($_resolveRecords) {
            $this->_resolveRecords($_records);
        }

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
                    if (false === $first) {
                        $this->_endGroup();
                    }
                    $this->_lastGroupValue = $propertyValue;
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

    protected function _extendedCFResolving(Tinebase_Record_RecordSet $_records)
    {
        $_records->customfields = array();
        Tinebase_CustomField::getInstance()->resolveMultipleCustomfields($_records, true);
    }

    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' resolving export records...');
        if ($_records->count() === 0) {
            return;
        }
        $record = $_records->getFirstRecord();
        // FIXME think what to do
        // TODO fix ALL this!
        // this is code present in the abstract controller, getRelatedData... why is it here?

        // get field types/identifiers from config, this is sort of dead code?
        $identifiers = [];
        $types = [];
        if ($this->_config->columns) {
            $types = array();
            foreach (Tinebase_Helper_ZendConfig::getChildrenConfigs($this->_config->columns, 'column') as $column) {
                $types[] = $column->type;
                $identifiers[] = $column->identifier;
            }
            $types = array_unique($types);
        } /* else {
            $types = $this->_resolvedFields;
        }*/

        // resolve users, also for FE Data!
        $userFields = [];
        foreach ($this->_userFields as $field) {
            if (empty($types) || in_array($field, $types) || in_array($field, $identifiers)) {
                $userFields[] = $field;
            }
        }
        if (count($userFields) > 0) {
            Tinebase_User::getInstance()->resolveMultipleUsers($_records, $userFields, true);
            if ($this->_FEDataRecordResolving) {
                foreach ($userFields as $field) {
                    foreach ($_records->{$field} as $idx => $value) {
                        if (is_array($value)) {
                            $_records->getByIndex($idx)->{$field} = new Tinebase_Model_FullUser($value);
                        }
                    }
                }
            }
        }

        // add notes, this is dead code?
        if (in_array('notes', $types) && !$this->_FEDataRecordResolving) {
            Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records, 'notes', 'Sql', false);
        }

        // add container, this is dead code?
        if (in_array('container_id', $types) && !$this->_FEDataRecordResolving) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::getUser());
        }

        while ($record->has('customfields')) {
            $customFieldConfigs = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication(
                $this->_applicationName, $this->_modelName);
            if (empty($customFieldConfigs)) {
                $_records->customfields = array();
                break;
            }

            if (!$this->_FEDataRecordResolving) {
                $this->_extendedCFResolving($_records);
            }

            $availableCFNames = [];
            /** @var Tinebase_Model_CustomField_Config $cfc */
            foreach ($customFieldConfigs as $cfc) {
                $cfc->value = null;
                $availableCFNames[$cfc->name] = $cfc;
            }
            $validators = null;
            $cfNameLabelMap = $this->_customFieldsNameLocalLabelMapping;
            $instance = $this;
            $stringifyCallBack = function($val) use ($instance) {
                return $instance->_convertToString($val);
            };
            if (!empty($this->_expandCustomFields)) {
                $validators = $_records->getFirstRecord()->getValidators();
                foreach ($this->_expandCustomFields as $field => $label) {
                    if (!isset($validators[$field])) {
                        $validators[$field] = [];
                    } else {
                        unset($this->_expandCustomFields[$field]);
                    }
                }
                if (empty($this->_expandCustomFields)) {
                    $validators = null;
                }
            }

            /** @var Tinebase_Record_Interface $record */
            foreach ($_records as $record) {
                $cfs = $record->customfields;
                if (empty($cfs)) {
                    $cfs = [];
                }
                if ($this->_FEDataRecordResolving) {
                    foreach ($cfs as $name => &$val) {
                        if (!isset($availableCFNames[$name])) {
                            unset($cfs[$name]);
                            continue;
                        }
                        /** @var Tinebase_Model_CustomField_Config $cfc */
                        $cfc = clone $availableCFNames[$name];
                        $cfc->value = $cfs[$name];
                        $val = $cfc;
                    }
                }
                foreach (array_diff_key($availableCFNames, $cfs) as $name => $cfc) {
                    $cfs[$name] = $cfc;
                }

                array_walk($cfs, function(Tinebase_Model_CustomField_Config $val, $key)
                        use($cfNameLabelMap, $stringifyCallBack) {
                    if ($val->value instanceof Tinebase_CustomField_Value) return;

                    $val->label = $cfNameLabelMap[$key];
                    $val->value = new Tinebase_CustomField_Value($val->value, $val->definition, $stringifyCallBack,
                        $val->application_id);
                });
                uksort($cfs, function($a, $b) use($cfNameLabelMap) {
                    return strcmp($cfNameLabelMap[$a], $cfNameLabelMap[$b]);
                });
                $record->customfields = $cfs;
                if (null !== $validators) {
                    $record->setValidators($validators);
                    foreach ($this->_expandCustomFields as $field => $label) {
                        if (isset($cfs[$field])) {
                            $record->{$field} = $cfs[$field];
                        }
                    }
                }
            }
            break;
        }

        /** @var Tinebase_Record_Interface $modelName */
        $modelName = $_records->getRecordClassName();

        if ($record->has('relations')) {
            if (!$this->_FEDataRecordResolving) {
                $relations = Tinebase_Relations::getInstance()->getMultipleRelations($modelName, 'Sql',
                    $_records->getArrayOfIds());
                /** @var Tinebase_Record_RecordSet $rels */
                foreach ($relations as $rels) {
                    $rels->removeRecords($rels->filter('related_record'));
                }
            } else {
                $relations = [];
                foreach ($_records as $idx => $record) {
                    $rels = $record->relations;
                    if (empty($rels)) {
                        continue;
                    }
                    if (is_array($rels)) {
                        $rels = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class, $rels);
                        /** @var Tinebase_Model_Relation $rel */
                        foreach ($rels as $rel) {
                            if (is_array($rel->related_record)) {
                                $rel->related_record = new $rel->related_model($rel->related_record, true);
                            }
                        }
                    }
                    $relations[$idx] = $rels;
                }
            }
            $this->_resolveRelationsType($relations);

            /** @var Tinebase_Record_Interface $record */
            foreach ($_records as $idx => $record) {
                if (isset($relations[$idx])) {
                    $record->relations = $relations[$idx];
                    $record->relations->sort(function(Tinebase_Model_Relation $a, Tinebase_Model_Relation $b) {
                        if (! $a->related_record) {
                            return true;
                        } else if (! $b->related_record) {
                            return false;
                        } else {
                            return strcmp($a->related_record->getTitle(), $b->related_record->getTitle());
                        }
                    }, null, 'function');
                }
            }
        }

        $appConfig = Tinebase_Config::factory($this->_applicationName);
        $this->_modelConfig = $modelName::getConfiguration();

        if (null === $this->_modelConfig && $_records->getRecordClassName() === $this->_modelName) {
            /** @var Tinebase_Record_Interface $record */
            foreach ($_records as $idx => $record) {
                // TODO FE data resolving: what about this? I guess that is ok?
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

                if (!$this->_FEDataRecordResolving) {
                    foreach ($this->_foreignIdFields as $name => $controller) {
                        if (!empty($record->{$name})) {
                            /** @var Tinebase_Controller_Record_Abstract $controller */
                            $controller = $controller::getInstance();
                            $record->{$name} = $controller->get($record->{$name});
                        }
                    }
                }
            }
        } elseif ($this->_modelConfig) {
            // TODO FE data resolving: what about this!
            $backupFields = [];
            if ($this->_FEDataRecordResolving) {
                /** @var Tinebase_Record_Interface $recordsClass */
                $recordsClass = $_records->getRecordClassName();
                $resolveForeignIdFields = $recordsClass::getResolveForeignIdFields();
                // TODO switch to is_iterable() when we no longer support PHP < 7.0
                if (is_array($resolveForeignIdFields) || $resolveForeignIdFields instanceof \Traversable) {
                    foreach ($resolveForeignIdFields as $key => $value) {
                        if ($key === 'recursive') {
                            $value = array_keys($value);
                        }
                        foreach ($value as $field) {
                            $backupFields[$field] = $_records->{$field};
                            $_records->{$field} = null;
                        }
                    }
                }
            }

            $this->_modelConfig->resolveRecords($_records);
            $this->_keyFields = [];
            foreach ($this->_modelConfig->keyfieldFields as $property) {
                $this->_keyFields[$property] = [
                    'application' => isset($this->_modelConfig->getFields()[$property]['application']) ?
                        $this->_modelConfig->getFields()[$property]['application'] : $this->_applicationName,
                    'name' => $this->_modelConfig->getFields()[$property]['name'],
                ];
            }

            foreach ($backupFields as $field => $data) {
                foreach ($data as $idx => $value) {
                    $_records->getByIndex($idx)->{$field} = $value;
                }
            }
        }

        // TODO FE data resolving: what about this?
        foreach ((array)$this->_keyFields as $property => $keyField) {
            if (!$_records->getFirstRecord()->has($property)) {
                continue;
            }

            /** @var Tinebase_Config_KeyField $keyField */
            if ($keyField['application'] === $this->_applicationName) {
                $keyField = $appConfig->{$keyField['name']};
            } else {
                $keyField = Tinebase_Config::factory($keyField['application'])->{$keyField['name']};
            }
            foreach ($_records as $record) {
                $record->{$property} = $keyField->getTranslatedValue($record->{$property});
            }
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
            /** @var Tinebase_Record_Interface $record */
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

                    $name = $this->_translate->_($field);
                    
                    if (!$name || $name === $field) {
                        $name = $this->_tinebaseTranslate->_($field);   
                    }
                    
                    $this->_writeValue($name);
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
     *
     * @todo @refactor split this up in multiple FNs
     */
    protected function _processRecord(Tinebase_Record_Interface $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' processing a export record...');

        if (true === $this->_dumpRecords) {
            // TODO we should support "writing" whole records here and not only single fields - see \Calendar_Export_VCalendar
            foreach (empty($this->_fields) ? $_record->getFields() : $this->_fields as $field) {
                if ($this->_rawData === false) {
                    if ($this->_modelConfig && isset($this->_modelConfig->getFields()[$field])
                        && isset($this->_modelConfig->getFields()[$field]['system'])
                        && $this->_modelConfig->getFields()[$field]['system'] === true
                    ) {
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
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                __METHOD__ . '::' . __LINE__ . ' can not process record, misconfigured!');
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
        if (null === $this->_baseContext) {
            $this->_baseContext = [
                Addressbook_Config::INSTALLATION_REPRESENTATIVE => Addressbook_Config::getInstallationRepresentative(),
                'branding' => [
                    'logo' => Tinebase_Core::getInstallLogo(),
                    'title' => Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_TITLE},
                    'description' => Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_DESCRIPTION},
                    'weburl' => Tinebase_Config::getInstance()->{Tinebase_Config::BRANDING_WEBURL},
                ],
                'export' => [
                    'config' => $this->_config->toArray(),
                    'timestamp' => $this->_exportTimeStamp,
                    'account' => Tinebase_Core::getUser(),
                    'contact' => Addressbook_Controller_Contact::getInstance()->getContactByUserId(Tinebase_Core::getUser()->getId()),
                    'groupdata' => $this->_lastGroupValue,
                ],
                'additionalRecords' => $this->_additionalRecords,
            ];
        }
        $this->_baseContext['export']['groupdata'] = $this->_lastGroupValue;

        return array_merge($this->_baseContext, $context);
    }

    /**
     * NOTE: do we need this to be abstract? some exports might not need this - for example Calendar_Export_VCalendar
     *       -> so I remove the "abstract" here
     *
     * @param string $_key
     * @param string $_value
     */
    protected function _setValue($_key, $_value)
    {
    }

    /**
     * NOTE: do we need this to be abstract? some exports might not need this - for example Calendar_Export_VCalendar
     *       -> so I remove the "abstract" here
     *
     * @param string $_value
     */
    protected function _writeValue($_value)
    {
    }

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
                } elseif($_value instanceof Tinebase_Model_CustomField_Config) {
                    $_value = $_value->value->__toString();
                } elseif ($_value instanceof Tinebase_Record_Interface) {
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

    public function registerTwigExtension(Twig_ExtensionInterface $twigExtension)
    {
        $this->_twigExtensions[] = $twigExtension;
    }

    /**
     * @return Tinebase_Model_Filter_FilterGroup
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * @return Tinebase_Controller_Record_Abstract
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @return bool
     *
     * TODO remove code duplication with \Tinebase_Export_AbstractDeprecated::isDownload
     */
    public function isDownload()
    {
        return !$this->_config->returnFileLocation;
    }

    /**
     * @param null|string $filename
     * @return Tinebase_Model_Tree_FileLocation
     * @throws Tinebase_Exception_NotImplemented
     *
     * TODO remove code duplication with \Tinebase_Export_AbstractDeprecated::getTargetFileLocation
     */
    public function getTargetFileLocation($filename = null)
    {
        if ($filename === null) {
            if (method_exists($this, 'write')) {
                ob_start();
                $this->write();
                $output = ob_get_clean();
                $filename = Tinebase_TempFile::getTempPath();
                file_put_contents($filename, $output);
            } else {
                throw new Tinebase_Exception_NotImplemented('Not implemented for this export');
            }
        }

        $tempFile = Tinebase_TempFile::getInstance()->createTempFile($filename, $this->getDownloadFilename());
        return new Tinebase_Model_Tree_FileLocation([
            Tinebase_Model_Tree_FileLocation::FLD_TYPE => Tinebase_Model_Tree_FileLocation::TYPE_DOWNLOAD,
            Tinebase_Model_Tree_FileLocation::FLD_TEMPFILE_ID => $tempFile->getId(),
        ]);
    }
}
