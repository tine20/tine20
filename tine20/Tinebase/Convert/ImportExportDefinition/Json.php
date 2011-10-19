<?php
/**
 * convert functions for ImportExportDefinitions from/to json (array) format
 * 
 * @package     Tinebase
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * convert functions for ImportExportDefinitions from/to json (array) format
 *
 * @package     Tinebase
 * @subpackage  Convert
 */
class Tinebase_Convert_ImportExportDefinition_Json extends Tinebase_Convert_Json
{
    /**
     * converts Tinebase_Record_Abstract to external format
     * 
     * @param  Tinebase_Record_Abstract $_record
     * @return mixed
     * 
     * @todo rename model to record?
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_model)
    {
        $this->_convertOptions($_model);
        
        $result = parent::fromTine20Model($_model);
        
        return $result;
    }
    
    /**
     * convert plugin_options to array
     * 
     * @param Tinebase_Model_ImportExportDefinition $_definition
     */
    protected function _convertOptions(Tinebase_Model_ImportExportDefinition $_definition)
    {
        $_definition->plugin_options = (empty($_definition->plugin_options))
            ? array()
            : Tinebase_ImportExportDefinition::getOptionsAsZendConfigXml($_definition)->toArray();
    }

    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param  Tinebase_Record_RecordSet  $_records
     * @param  array $_resolveUserFields
     * @return mixed
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records, $_resolveUserFields = array())
    {
        foreach ($_records as $record) {
            $this->_convertOptions($record);
        }
        
        $result = parent::fromTine20RecordSet($_records, $_resolveUserFields);
        
        return $result;
    }
}
