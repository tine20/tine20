<?php
/**
 * Tinebase Abstract export class
 *
 * @package     Tinebase
 * @subpackage	Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Abstract.php 12208 2010-01-11 14:46:35Z p.schuele@metaways.de $
 * 
 */

/**
 * Timetracker Abstract export class
 * 
 * @package     Tinebase
 * @subpackage	Export
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
     * @var Tinebase_Controller_Record_Interface
     */
    protected $_controller = NULL;
    
    /**
     * translation object
     *
     * @var Zend_Translate
     */
    protected $_translate;
    
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
     * resolved records
     *
     * @var array of Tinebase_Record_RecordSet
     */
    protected $_resolvedRecords = array();
    
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
    }
    
    /**
     * generate export
     * 
     * @return string filename
     */
    abstract public function generate();
    
    /**
     * get export document object
     * 
     * @return Object the generated document
     */
    abstract public function getDocument();
    
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
                    $definition->plugin_options = $fileDefinition->plugin_options;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
                }
            }
        }
        
        return Tinebase_ImportExportDefinition::getInstance()->getOptionsAsZendConfigXml($definition, $_additionalOptions);
    }

    /**
     * get special field value / overwrite this to add special values
     *
     * @param Tinebase_Record_Interface $_record
     * @param array $_param
     * @param string || null $key [may be used by child methods e.g. {@see Timetracker_Export_Abstract::_getSpecialFieldValue)]
     * @return string
     */
    protected function _getSpecialFieldValue(Tinebase_Record_Interface $_record, $_param, $key = null)
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
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_fieldConfig->replace->patterns->toArray(), true));
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_fieldConfig->replace->replacements->toArray(), true));
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
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_fieldConfig->match, true));
            preg_match($_fieldConfig->match, $value, $matches);
            $value = (isset($matches[1])) ? $matches[1] : '';
        }
        
        return $value;
    }
    
    /**
     * resolve records / overwrite this to resolve relations/linked data
     *
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveRecords(Tinebase_Record_RecordSet $_records)
    {
    }
}
