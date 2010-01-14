<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:ImportExportDefinition.php 7161 2009-03-04 14:27:07Z p.schuele@metaways.de $
 * 
 */


/**
 * backend for persistent filters
 *
 * @package     Timetracker
 * @subpackage  Backend
 */
class Tinebase_ImportExportDefinition
{
    /**
     * @var Tinebase_Backend_Sql
     */
    protected $_backend;
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_ImportExportDefinition';

    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_ImportExportDefinition
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     */
    private function __construct()
    {
        // set backend with activated modlog
        $this->_backend = new Tinebase_Backend_Sql($this->_modelName, 'importexport_definition', NULL, NULL, TRUE);
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
     */
    public function getByName($_name)
    {
        return $this->_backend->getByProperty($_name);
    }
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Model_ImportExportDefinition $_record
     * @return  Tinebase_Model_ImportExportDefinition
     */
    public function create(Tinebase_Model_ImportExportDefinition $_record) 
    {
        return $this->_backend->create($_record);
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
    public function getFromFile($_filename, $_applicationId, $_name = 'import_definition')
    {
        if (file_exists($_filename)) {
            
            $content = file_get_contents($_filename);
            $config = new Zend_Config_Xml($_filename);
                    
            $definition = new Tinebase_Model_ImportExportDefinition(array(
                'application_id'    => $_applicationId,
                'name'              => $_name,
                'description'       => $config->description,
                'type'              => $config->type,
                'model'             => $config->model,
                'plugin'            => $config->plugin,
                'plugin_options'    => $content
            ));
            
            return $definition;
        } else {
            throw Tinebase_Exception_NotFound('Definition file not found.');
        }
    }
    
    /**
     * get config options as Zend_Config_Xml object
     * 
     * @param Tinebase_Model_ImportExportDefinition $_definition
     * @param array $_additionalOptions additional options
     * @return Zend_Config_Xml
     */
    public function getOptionsAsZendConfigXml(Tinebase_Model_ImportExportDefinition $_definition, $_additionalOptions = array())
    {
        $tmpfname = tempnam(Tinebase_Core::getTempDir(), "tine_tempfile_");
        
        $handle = fopen($tmpfname, "w");
        fwrite($handle, $_definition->plugin_options);
        fclose($handle);
        
        // read file with Zend_Config_Xml
        $config = new Zend_Config_Xml($tmpfname, null, TRUE);
        $config->merge(new Zend_Config($_additionalOptions));
        
        unlink($tmpfname);
        
        return $config;
    }
}
