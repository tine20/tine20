<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * backend for import/export definitions
 *
 * @package     Tinebase
 * @subpackage  Controller
 */
class Tinebase_ImportExportDefinition extends Tinebase_Controller_Record_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_ImportExportDefinition
     */
    private static $_instance = NULL;
        
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_modelName = 'Tinebase_Model_ImportExportDefinition';
        $this->_applicationName = 'Tinebase';
        $this->_purgeRecords = FALSE;
        $this->_doContainerACLChecks = FALSE;

        // set backend with activated modlog
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName'     => $this->_modelName,
            'tableName'     => 'importexport_definition',
            'modlogActive'  => TRUE,
        ));
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_ImportExportDefinition
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_ImportExportDefinition();
        }
        return self::$_instance;
    }
    
    /**
     * get definition by name
     * 
     * @param string $_name
     * @return Tinebase_Model_ImportExportDefinition
     * 
     * @todo replace this with search function
     */
    public function getByName($_name)
    {
        return $this->_backend->getByProperty($_name);
    }
    
    /**
     * get application export definitions
     *
     * @param Tinebase_Model_Application $_application
     * @return Tinebase_Record_RecordSet of Tinebase_Model_ImportExportDefinition
     */
    public function getExportDefinitionsForApplication(Tinebase_Model_Application $_application)
    {
        $filter = new Tinebase_Model_ImportExportDefinitionFilter(array(
            array('field' => 'application_id',  'operator' => 'equals',  'value' => $_application->getId()),
            array('field' => 'type',            'operator' => 'equals',  'value' => 'export'),
        ));
        $result = $this->search($filter);
        
        return $result;
    }
    
    /**
     * get definition from file
     *
     * @param string $_filename
     * @param string $_applicationId
     * @param string $_name [optional]
     * @return Tinebase_Model_ImportExportDefinition
     * @throws Tinebase_Exception_NotFound
     */
    public function getFromFile($_filename, $_applicationId, $_name = NULL)
    {
        if (file_exists($_filename)) {
            $basename = basename($_filename);
            $content = file_get_contents($_filename);
            $config = new Zend_Config_Xml($_filename);
            
            if ($_name === NULL) {
                $name = ($config->name) ? $config->name : preg_replace("/\.xml/", '', $basename);
            } else {
                $name = $_name;
            }
            
            $definition = new Tinebase_Model_ImportExportDefinition(array(
                'application_id'              => $_applicationId,
                'name'                        => $name,
                'description'                 => $config->description,
                'type'                        => $config->type,
                'model'                       => $config->model,
                'plugin'                      => $config->plugin,
                'plugin_options'              => $content,
                'filename'                    => $basename,
                'mapUndefinedFieldsEnable'    => $config->mapUndefinedFieldsEnable,
                'mapUndefinedFieldsTo'        => $config->mapUndefinedFieldsTo,
                'postMappingHook'             => $config->postMappingHook
            ));
            
            return $definition;
        } else {
            throw new Tinebase_Exception_NotFound('Definition file "' . $_filename . '" not found.');
        }
    }
    
    /**
     * get config options as Zend_Config_Xml object
     * 
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param array $_additionalOptions additional options
     * @return Zend_Config_Xml
     */
    public static function getOptionsAsZendConfigXml(Tinebase_Model_ImportExportDefinition $_definition, $_additionalOptions = array())
    {
        $tmpfname = tempnam(Tinebase_Core::getTempDir(), "tine_tempfile_");
        
        if (! $tmpfname) {
            throw new Tinebase_Exception_AccessDenied('Could not create temporary file.');
        }
        
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $_definition->plugin_options);
        fclose($handle);
        
        // read file with Zend_Config_Xml
        $config = new Zend_Config_Xml($tmpfname, null, TRUE);
        $config->merge(new Zend_Config($_additionalOptions));
        
        unlink($tmpfname);
        
        return $config;
    }
    
    /**
     * update existing definition or create new from file
     * - use backend functions (create/update) directly because we do not want any default controller handling here
     * - calling function needs to make sure that user has admin right!
     * 
     * @param string $_filename
     * @param Tinebase_Model_Application $_application
     * @param string $_name
     * @return Tinebase_Model_ImportExportDefinition
     */
    public function updateOrCreateFromFilename($_filename, $_application, $_name = NULL)
    {
        $definition = $this->getFromFile(
            $_filename,
            $_application->getId(),
            $_name
        );
        
        // try to get definition and update if it exists
        try {
            $existing = $this->getByName($definition->name);
            Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Updating definition: ' . $definition->name);
            $copyFields = array('filename', 'plugin_options', 'description');
            foreach ($copyFields as $field) {
                $existing->{$field} = $definition->{$field};
            }
            $result = $this->_backend->update($existing);
            
        } catch (Tinebase_Exception_NotFound $tenf) {
            // does not exist
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating import/export definion from file: ' . $_filename);
            $result = $this->_backend->create($definition);
        }
        
        return $result;
    }
}
