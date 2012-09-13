<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the Filemanager application
 *
 * @package     Filemanager
 * @subpackage  Frontend
 */
class Filemanager_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * app name
     * 
     * @var string
     */
    protected $_applicationName = 'Filemanager';
    /**
     * search file/directory nodes
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchNodes($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Filemanager_Controller_Node::getInstance(), 'Tinebase_Model_Tree_Node_Filter');
        $this->_removeAppIdFromPathFilter($result);
        
        return $result;
    }
    
    /**
     * remove app id (base path) from filter
     * 
     * @param array $_result
     * 
     * @todo is this really needed? perhaps we can set the correct path in Tinebase_Model_Tree_Node_PathFilter::toArray
     */
    protected function _removeAppIdFromPathFilter(&$_result)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        foreach ($_result['filter'] as $idx => &$filter) {
            if ($filter['field'] === 'path') {
                if (is_array($filter['value'])) {
                    $filter['value']['path'] = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($filter['value']['path'], $app);
                } else {
                    $filter['value'] = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($filter['value'], $app);
                }
            }
        }
    }

    /**
     * create node
     * 
     * @param array $filename
     * @param string $type directory or file
     * @param string $tempFileId
     * @param boolean $forceOverwrite
     * @return array
     */
    public function createNode($filename, $type, $tempFileId, $forceOverwrite)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->createNodes((array)$filename, $type, (array)$tempFileId, $forceOverwrite);
        $result = (count($nodes) === 0) ? array() : $this->_recordToJson($nodes->getFirstRecord());
        
        return $result;
    }

    /**
     * create nodes
     * 
     * @param string|array $filenames
     * @param string $type directory or file
     * @param string|array $tempFileIds
     * @param boolean $forceOverwrite
     * @return array
     */
    public function createNodes($filenames, $type, $tempFileIds, $forceOverwrite)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->createNodes((array)$filenames, $type, (array)$tempFileIds, $forceOverwrite);
        
        return $this->_multipleRecordsToJson($nodes);
    }
    
    /**
     * copy node(s)
     * 
     * @param string|array $sourceFilenames string->single file, array->multiple
     * @param string|array $destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $forceOverwrite
     * @return array
     */
    public function copyNodes($sourceFilenames, $destinationFilenames, $forceOverwrite)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->copyNodes((array)$sourceFilenames, $destinationFilenames, $forceOverwrite);
        
        return $this->_multipleRecordsToJson($nodes);
    }

    /**
     * move node(s)
     * 
     * @param string|array $sourceFilenames string->single file, array->multiple
     * @param string|array $destinationFilenames string->singlefile OR directory, array->multiple files
     * @param boolean $forceOverwrite
     * @return array
     */
    public function moveNodes($sourceFilenames, $destinationFilenames, $forceOverwrite)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->moveNodes((array)$sourceFilenames, $destinationFilenames, $forceOverwrite);
        
        return $this->_multipleRecordsToJson($nodes);
    }

    /**
     * delete node(s)
     * 
     * @param string|array $filenames string->single file, array->multiple
     * @return array
     */
    public function deleteNodes($filenames)
    {
        Filemanager_Controller_Node::getInstance()->deleteNodes((array)$filenames);
        
        return array(
            'status'    => 'success'
        );
    }

    /**
     * returns the node record
     * @param string $id
     * @return array
     */
    public function getNode($id)
    {
        return $this->_get($id, Filemanager_Controller_Node::getInstance());
    }
    
    /**
     * save node
     * save node here in json fe just updates meta info (name, description, relations, customfields, tags, notes),
     * if record already exists (after it had been uploaded)
     * @param array with record data 
     * @return array
     */
    public function saveNode($recordData)
    {
        if(array_key_exists('created_by', $recordData)) {
            return $this->_save($recordData, Filemanager_Controller_Node::getInstance(), 'Node');
        } else {    // on upload complete
            return $recordData;
        }
    }
    
}
