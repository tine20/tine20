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
        $options = (empty($_definition->plugin_options))
            ? array()
            : Tinebase_ImportExportDefinition::getOptionsAsZendConfigXml($_definition)->toArray();
        
        if (isset($options['autotags'])) {
            $options['autotags'] = $this->_handleAutotags($options['autotags']);
        }

        if (isset($options['container_id'])) {
            $options['container_id'] = Tinebase_Container::getInstance()->getContainerById($options['container_id'])->toArray();
        }
        
        $_definition->plugin_options = $options;
    }
    
    /**
     * resolve and sanitize tags
     * 
     * @param array $_autotagOptions
     * @return array
     */
    protected function _handleAutotags($_autotagOptions)
    {
        $result = (isset($_autotagOptions['tag'])) ? $_autotagOptions['tag'] : $_autotagOptions;
        
        if (isset($result['name'])) {
            $result = array($result);
        }
        
        // resolve tags if they exist
        foreach ($result as $idx => $value) {
            if (isset($value['id'])) {
                try {
                    $tag = Tinebase_Tags::getInstance()->get($value['id']);
                    $result[$idx] = $tag->toArray();
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // do nothing
                }
            }
        }
        
        return $result;
    }

    /**
     * converts Tinebase_Record_RecordSet to external format
     * 
     * @param  Tinebase_Record_RecordSet  $_records
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @param Tinebase_Model_Pagination $_pagination
     * 
     * @return mixed
     */
    public function fromTine20RecordSet(Tinebase_Record_RecordSet $_records, $_filter = NULL, $_pagination = NULL)
    {
        foreach ($_records as $record) {
            $this->_convertOptions($record);
        }
        
        $result = parent::fromTine20RecordSet($_records);
        
        return $result;
    }
}
