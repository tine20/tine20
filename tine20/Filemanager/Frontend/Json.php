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
     */
    protected function _removeAppIdFromPathFilter(&$_result)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        
        foreach ($_result['filter'] as $idx => &$filter) {
            if ($filter['field'] === 'path') {
                $filter['value'] = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($filter['value'], $app);
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
            $this->_resolveTopLevelContainers($_records, $path);
            $this->_addPathToRecords($_records, $path);
        }
        
        return parent::_multipleRecordsToJson($_records, $_filter);
    }
    
    /**
     * replace name with container record
     * - resolve containers (if node name is a container id / path is toplevel (shared/personal with useraccount)
     * 
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_Model_Tree_Node_Path $_path
     */
    protected function _resolveTopLevelContainers(Tinebase_Record_RecordSet $_records, $_path)
    {
        if (count($_records) === 0) {
            return;
        }

        if ($_path->container !== NULL) {
            // only do it for top level nodes (above container nodes)
            return;
        }
        
        $containerIds = $_records->name;
        $containers = Tinebase_Container::getInstance()->getMultiple($containerIds);
        
        foreach ($_records as $record) {
            $idx = $containers->getIndexById($record->name);
            if ($idx !== FALSE) {
                $record->name = $containers[$idx];
            }
        }
    }

    /**
     * add path to records
     * 
     * @param Tinebase_Record_RecordSet $_records
     * @param Tinebase_Model_Tree_Node_Path $_path
     */
    protected function _addPathToRecords(Tinebase_Record_RecordSet $_records, $_path)
    {
        $app = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        $flatpathWithoutBasepath = Tinebase_Model_Tree_Node_Path::removeAppIdFromPath($_path->flatpath, $app);
        
        foreach ($_records as $record) {
            $record->path = $flatpathWithoutBasepath . '/' . $record->name;
        }
    }
    
    /**
     * create node(s)
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
     * 
     * @todo implement
     */
    public function copyNodes($sourceFilenames, $destinationFilenames)
    {
        throw new Tinebase_Exception_NotImplemented('not implemented yet');
    }

    /**
     * move node(s)
     * 
     * @param string|array $sourceFilenames string->single file, array->multiple
     * @param string|array $destinationFilenames string->singlefile OR directory, array->multiple files
     * @return array
     * 
     * @todo implement
     */
    public function moveNodes($sourceFilenames, $destinationFilenames)
    {
        throw new Tinebase_Exception_NotImplemented('not implemented yet');
    }

    /**
     * delete node(s)
     * 
     * @param string|array $filenames string->single file, array->multiple
     * @return array
     * 
     * @todo implement
     */
    public function deleteNodes($filenames)
    {
        throw new Tinebase_Exception_NotImplemented('not implemented yet');
    }
}
