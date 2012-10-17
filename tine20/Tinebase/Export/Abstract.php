<?php
/**
 * Tinebase Abstract export class
 *
 * @package     Tinebase
 * @subpackage    Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * Tinebase Abstract export class
 * 
 * @package     Tinebase
 * @subpackage    Export
 * 
 */
abstract class Tinebase_Export_Abstract
{
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
    protected $_controller = NULL;
    
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
     * fields with special treatment in addBody
     *
     * @var array
     */
    protected $_specialFields = array();
    
    /**
     * @var string application name of this export class
     */
    protected $_applicationName = 'Tinebase';
    
    /**
     * the record model
     *
     * @var string
     */
    protected $_modelName = NULL;
    
    /**
     * filter to generate export for
     * 
     * @var Tinebase_Model_Filter_FilterGroup
     */
    protected $_filter = NULL;
    
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
    protected $_prefKey = NULL;
    
    /**
     * format strings
     * 
     * @var string
     */
    protected $_format = NULL;
    
    /**
     * other resolved records
     *
     * @var array of Tinebase_Record_RecordSet
     */
    protected $_resolvedRecords = array();
    
    /**
     * user fields to resolve
     * 
     * @var array
     */
    protected $_userFields = array('created_by', 'last_modified_by', 'account_id');
    
    /**
     * other fields to resolve
     * 
     * @var array
     */
    protected $_resolvedFields = array('created_by', 'last_modified_by', 'container_id', 'tags', 'notes', 'relation');
    
    /**
     * get record relations
     * 
     * @var boolean
     */
    protected $_getRelations = FALSE;
    
    /**
     * custom field names for this model
     * 
     * @var array
     */
    protected $_customFieldNames = NULL;
    
    /**
     * the constructor
     *
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Controller_Record_Interface $_controller (optional)
     * @param array $_additionalOptions (optional) additional options
     */
    public function __construct(Tinebase_Model_Filter_FilterGroup $_filter, Tinebase_Controller_Record_Interface $_controller = NULL, $_additionalOptions = array())
    {
        // init member vars
        $this->_modelName = $_filter->getModelName();
        $this->_filter = $_filter;
        $this->_controller = ($_controller !== NULL) ? $_controller : Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName);
        $this->_translate = Tinebase_Translation::getTranslation($this->_applicationName);
        $this->_config = $this->_getExportConfig($_additionalOptions);
        $this->_locale = Tinebase_Core::get(Tinebase_Core::LOCALE);
    }
    
    /**
     * generate export
     * 
     * @return mixed filename/generated object/...
     */
    abstract public function generate();
    
    /**
     * get custom field names for this app
     * 
     * @return array
     */
    protected function _getCustomFieldNames()
    {
        if ($this->_customFieldNames === NULL) {
            $this->_customFieldNames = Tinebase_CustomField::getInstance()->getCustomFieldsForApplication($this->_applicationName, $this->_modelName)->name;
        }
        
        return $this->_customFieldNames;
    }
    
    /**
     * get export format string (csv, ...)
     * 
     * @return string
     */
    public function getFormat()
    {
        if ($this->_format === NULL) {
            throw new Tinebase_Exception_NotFound('Format string not found.');
        }
        
        return $this->_format;
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
     */
    public function getDownloadFilename($_appName, $_format)
    {
        return 'export_' . strtolower($_appName) . '.' . $_format;
    }
    
    /**
     * export records
     */
    protected function _exportRecords()
    {
        $iterator = new Tinebase_Record_Iterator(array(
            'iteratable' => $this,
            'controller' => $this->_controller,
            'filter'     => $this->_filter,
            'options'     => array(
                'searchAction' => 'export',
                'sortInfo'       => $this->_sortInfo,
                'getRelations' => $this->_getRelations,
            ),
        ));
        $result = $iterator->iterate();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
            . ' Exported ' . $result['totalcount'] . ' records.');
    }
    
    /**
     * resolve records and prepare for export (set user timezone, ...)
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
        // get field types/identifiers from config
        $identifiers = array();
        if ($this->_config->columns) {
            $types = array();
            foreach ($this->_config->columns->column as $column) {
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
                Tinebase_User::getInstance()->resolveMultipleUsers($_records, $field, TRUE);
            }
        }
        
        // add notes
        if (in_array('notes', $types)) {
            Tinebase_Notes::getInstance()->getMultipleNotesOfRecords($_records, 'notes', 'Sql', FALSE);
        }
        
        // add container
        if (in_array('container_id', $types)) {
            Tinebase_Container::getInstance()->getGrantsOfRecords($_records, Tinebase_Core::getUser());
        }
        
        $_records->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
    }

    /**
     * return template filename if set
     * 
     * @return string|NULL
     */
    protected function _getTemplateFilename()
    {
        $templateFile = $this->_config->get('template', NULL);
        if ($templateFile !== NULL) {
            
            // check if template file has absolute path
            if (strpos($templateFile, '/') !== 0) {
                $templateFile = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . $this->_applicationName . 
                    DIRECTORY_SEPARATOR . 'Export' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $templateFile;
            }
            if (file_exists($templateFile)) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Using template file "' . $templateFile . '" for ' . $this->_modelName . ' export.');
            } else {
                throw new Tinebase_Exception_NotFound('Template file ' . $templateFile . ' not found');
            }
        }
        
        return $templateFile;
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
        if (array_key_exists('definitionFilename', $_additionalOptions)) {
            // get definition from file
            $definition = Tinebase_ImportExportDefinition::getInstance()->getFromFile(
                $_additionalOptions['definitionFilename'],
                Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId() 
            );
            
        } else if (array_key_exists('definitionId', $_additionalOptions)) {
            $definition = Tinebase_ImportExportDefinition::getInstance()->get($_additionalOptions['definitionId']);
            
        } else {
            // get preference from db and set export definition name
            $exportName = $this->_defaultExportname;
            if ($this->_prefKey !== NULL) {
                $exportName = Tinebase_Core::getPreference($this->_applicationName)->getValue($this->_prefKey, $exportName);
            }
            
            // get export definition by name / model
            $filter = new Tinebase_Model_ImportExportDefinitionFilter(array(
                array('field' => 'model', 'operator' => 'equals', 'value' => $this->_modelName),
                array('field' => 'name',  'operator' => 'equals', 'value' => $exportName),
            ));
            $definitions = Tinebase_ImportExportDefinition::getInstance()->search($filter);
            if (count($definitions) == 0) {
                throw new Tinebase_Exception_NotFound('Export definition for model ' . $this->_modelName . ' not found.');
            }
            $definition = $definitions->getFirstRecord();
            
            if (! empty($definition->filename)) {
                // check if file with plugin options exists and use that
                $completeFilename = dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . $this->_applicationName . 
                    DIRECTORY_SEPARATOR . 'Export' . DIRECTORY_SEPARATOR . 'definitions' . DIRECTORY_SEPARATOR . $definition->filename;
                try {
                    $fileDefinition = Tinebase_ImportExportDefinition::getInstance()->getFromFile(
                        $completeFilename,
                        Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId() 
                    );
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Using definition from file ' . $definition->filename);
                    $definition->plugin_options = $fileDefinition->plugin_options;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                }
            }
        }
        
        $config = Tinebase_ImportExportDefinition::getInstance()->getOptionsAsZendConfigXml($definition, $_additionalOptions);
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' export config: ' . print_r($config->toArray(), TRUE));
        
        return $config;
    }

    /**
     * return tag names of record
     * 
     * @param Tinebase_Record_Abstract $_record
     * @return string
     */
    protected function _getTags(Tinebase_Record_Abstract $_record)
    {
        $tags = Tinebase_Tags::getInstance()->getTagsOfRecord($_record);
        return implode(', ', $tags->name);
    }
    
    /**
     * 
     * return container name (or other field)
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_field
     * @param string $_property
     * @return string
     */
    protected function _getContainer(Tinebase_Record_Abstract $_record, $_field = 'id', $_property = 'container_id')
    {
        $container = $_record->{$_property};
        return $container[$_field];
    }
    
    /**
     * add relation values from related records
     * 
     * @param Tinebase_Record_Abstract $record
     * @param string $relationType
     * @param string $recordField
     * @param boolean $onlyFirstRelation
     * @return string
     */
    protected function _addRelations(Tinebase_Record_Abstract $record, $relationType, $recordField = NULL, $onlyFirstRelation = FALSE)
    {
        $record->relations->addIndices(array('type'));
        $matchingRelations = $record->relations->filter('type', $relationType);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' '
            . 'Found ' . count($matchingRelations) . ' relations of type ' . $relationType . ' (' . $recordField . ')');
        
        $resultArray = array();
        foreach ($matchingRelations as $relation) {
            if ($recordField !== NULL) {
                $resultArray[] = $relation->related_record->{$recordField};
            } else {
                $resultArray[] = $this->_getRelationSummary($relation->related_record);
            }
            
            if ($onlyFirstRelation) {
                break;
            }
        }
        
        $result = implode(';', $resultArray);
        
        return $result;
    }
    
    /**
     * add relation summary (such as n_fileas, title, ...)
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_type
     * @return string
     */
    protected function _getRelationSummary(Tinebase_Record_Abstract $_record)
    {
        $result = '';
        switch(get_class($_record)) {
            case 'Addressbook_Model_Contact':
                $result = $_record->n_fileas;
                break;
            case 'Tasks_Model_Task':
                $result = $_record->summary;
                break;
        }
        
        return $result;
    }
    
    /**
     * add relation values from related records
     * 
     * @param Tinebase_Record_Abstract $_record
     * @param string $_fieldName
     * @param string $_recordField
     * @return string
     */
    protected function _addNotes(Tinebase_Record_Abstract $_record)
    {
        //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_record->notes->toArray(), true));
        
        $resultArray = array();
        foreach ($_record->notes as $note) {
            $date = Tinebase_Translation::dateToStringInTzAndLocaleFormat($note->creation_time);
            $resultArray[] = $date . ' - ' . $note->note;
        }
        
        $result = implode(';', $resultArray);
        return $result;
    }

    /**
     * get special field value / overwrite this to add special values
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string || null $_key [may be used by child methods e.g. {@see Timetracker_Export_Abstract::_getSpecialFieldValue)]
     * @param string $_cellType
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $_key = NULL, &$_cellType = NULL)
    {
        return '';
    }

    /**
     * replace and match strings in value
     * 
     * @param string $_value
     * @param Zend_Config $_fieldConfig
     * @return string
     */
    protected function _replaceAndMatchvalue($_value, Zend_Config $_fieldConfig)
    {
        $value = $_value;
        
        // check for replacements
        if (isset($_fieldConfig->replace) && isset($_fieldConfig->replace->patterns) && isset($_fieldConfig->replace->replacements)) {
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_fieldConfig->replace->patterns->toArray(), true));
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_fieldConfig->replace->replacements->toArray(), true));
            $patterns =     (count($_fieldConfig->replace->patterns->pattern) > 1)          
                ? $_fieldConfig->replace->patterns->pattern->toArray()          
                : $_fieldConfig->replace->patterns->toArray();
            $replacements = (count($_fieldConfig->replace->replacements->replacement) > 1)  
                ? $_fieldConfig->replace->replacements->replacement->toArray()  
                : $_fieldConfig->replace->replacements->toArray();
            $value = preg_replace($patterns, $replacements, $value);
        }

        // check for matches
        if (isset($_fieldConfig->match)) {
            //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_fieldConfig->match, true));
            preg_match($_fieldConfig->match, $value, $matches);
            $value = (isset($matches[1])) ? $matches[1] : '';
        }
        
        return $value;
    }
}
