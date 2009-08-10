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
class Tinebase_ImportExportDefinition extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'importexport_definition';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Tinebase_Model_ImportExportDefinition';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;

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
}
