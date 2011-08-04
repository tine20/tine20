<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * user fields (created_by, ...) to resolve in _multipleRecordsToJson and _recordToJson
     *
     * @var array
     */
    protected $_resolveUserFields = array(
        'Tinebase_Model_Tree_Node' => array('created_by', 'last_modified_by'),
    );
        
    /**
     * search file/directory nodes
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchNodes($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Filemanager_Controller_Node::getInstance(), 'Tinebase_Model_Tree_Node_Filter', FALSE, self::TOTALCOUNT_COUNTRESULT);
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
     * returns multiple records prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_records Tinebase_Record_Abstract
     * @param Tinebase_Model_Filter_FilterGroup $_filter
     * @return array data
     */
    protected function _multipleRecordsToJson(Tinebase_Record_RecordSet $_records, $_filter = NULL)
    {
        if ($_filter !== NULL) {
            $path = Tinebase_Model_Tree_Node_Path::createFromPath($_filter->getFilter('path')->getValue());
            Filemanager_Controller_Node::getInstance()->resolveContainerAndAddPath($_records, $path);
        }
        
        return parent::_multipleRecordsToJson($_records, $_filter);
    }
    
    /**
     * create node
     * 
     * @param array $filename
     * @param string $type directory or file
     * @return array
     */
    public function createNode($filename, $type)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->createNodes((array)$filename, $type);
        $result = (count($nodes) === 0) ? array() :  $this->_recordToJson($nodes->getFirstRecord());
        
        return $result;
    }

    /**
     * create nodes
     * 
     * @param string|array $filenames
     * @param string $type directory or file
     * @return array
     */
    public function createNodes($filenames, $type)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->createNodes((array)$filenames, $type);
        
        return $this->_multipleRecordsToJson($nodes);
    }

    /**
     * attach uploaded file to node
     * 
     * @param string $filename
     * @param string $tempFileId
     * @return array
     * 
     * @todo implement
     */
    public function attachFileToNode($filename, $tempFileId)
    {
        throw new Tinebase_Exception_NotImplemented('not implemented yet');
    }
    
    /**
     * copy node(s)
     * 
     * @param string|array $sourceFilenames string->single file, array->multiple
     * @param string|array $destinationFilenames string->singlefile OR directory, array->multiple files
     * @return array
     */
    public function copyNodes($sourceFilenames, $destinationFilenames)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->copyNodes((array)$sourceFilenames, $destinationFilenames);
        
        return $this->_multipleRecordsToJson($nodes);
    }

    /**
     * move node(s)
     * 
     * @param string|array $sourceFilenames string->single file, array->multiple
     * @param string|array $destinationFilenames string->singlefile OR directory, array->multiple files
     * @return array
     */
    public function moveNodes($sourceFilenames, $destinationFilenames)
    {
        $nodes = Filemanager_Controller_Node::getInstance()->moveNodes((array)$sourceFilenames, $destinationFilenames);
        
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
}
