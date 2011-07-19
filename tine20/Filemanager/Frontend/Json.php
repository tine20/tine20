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
     * 
     * @todo perhaps we can add searchCount() to the controller later and replace the count method TOTALCOUNT_COUNTRESULT
     */
    public function searchNodes($filter, $paging)
    {
        $result = $this->_search($filter, $paging, Filemanager_Controller_Node::getInstance(), 'Tinebase_Model_Tree_NodeFilter', FALSE, self::TOTALCOUNT_COUNTRESULT);
        
        return $result;
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
        // resolve containers (if node name is a container id / path is toplevel (shared/personal with useraccount)
        // @todo resolve containers
//        if ($_filter !== NULL) {
//            $pathFilter = $_filter->getFilter('path');
//            if (Filemanager_Controller_Node::getInstance()->getContainer($pathFilter->getValue()) === NULL) {
//                $this->_resolveNodeContainers($_records);
//            }
//        }
        
        // @todo add path to records
        
        return parent::_multipleRecordsToJson($_records, $_filter);
    }
    
    /**
     * replace name with container record
     * 
     * @param Tinebase_Record_RecordSet $_records
     */
    protected function _resolveNodeContainers(Tinebase_Record_RecordSet $_records)
    {
        if (count($_records) === 0) {
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
     * create node(s)
     * 
     * @param string|array $filenames
     * @param string $type directory or file
     * @return array
     * 
     * @todo implement
     */
    public function createNodes($filenames, $type)
    {
        throw new Tinebase_Exception_NotImplemented('not implemented yet');
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
