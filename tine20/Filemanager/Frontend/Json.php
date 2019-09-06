<?php
/**
 * Tine 2.0
 *
 * @package     Filemanager
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $controller = Filemanager_Controller_Node::getInstance();

        // remove recursive filter if not appropriate
        $filter = $this->_decodeFilter($filter, 'Filemanager_Model_NodeFilter');
        $filter->isRecursiveFilter(true);

        $result = $this->_search($filter, $paging, $controller, 'Filemanager_Model_NodeFilter');
        $this->_removeAppIdFromPathFilter($result);

        $context = $controller->getRequestContext();
        if (is_array($context)) {
            if (isset($context['quotaResult'])) {
                $result['quota'] = $context['quotaResult'];
            }
            if (isset($context['pinProtectedData'])) {
                $result['pinProtectedData'] = true;
            }
        }
        
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
            if (isset($filter['field']) && $filter['field'] === 'path') {
                // TODO what about subfilters?
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
     * @param string $type mimetype
     * @param string $tempFileId
     * @param boolean $forceOverwrite
     * @return array
     */
    public function createNode($filename, $type, $tempFileId = array(), $forceOverwrite = false)
    {
        // do not convert $type to array!
        $nodes = Filemanager_Controller_Node::getInstance()->createNodes((array)$filename, $type, (array)$tempFileId, $forceOverwrite);
        $result = (count($nodes) === 0) ? array() : $this->_recordToJson($nodes->getFirstRecord());
        
        return $result;
    }

    /**
     * create nodes
     * 
     * @param string|array $filenames
     * @param string|array $type directory or mime type in case of a file
     * @param string|array $tempFileIds
     * @param boolean $forceOverwrite
     * @return array
     */
    public function createNodes($filenames, $types, $tempFileIds = array(), $forceOverwrite = false)
    {
        // do not convert $type to array!
        $nodes = Filemanager_Controller_Node::getInstance()->createNodes((array)$filenames, $types, (array)$tempFileIds, $forceOverwrite);

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
        $controller = Filemanager_Controller_Node::getInstance();
        try {
            $oldDoThrow = $controller->doThrowOnGetQuarantined(false);
            $context = $controller->getRequestContext();
            if (!is_array($context)) {
                $context = array();
            }
            $context['quotaResult'] = true;
            $controller->setRequestContext($context);

            $result = $this->_get($id, $controller);

            $context = $controller->getRequestContext();
            if (is_array($context) && isset($context['quotaResult']) && is_array($context['quotaResult'])) {
                $result['effectiveAndLocalQuota'] = $context['quotaResult'];
                unset($context['quotaResult']);
            }
        } finally {
            $controller->doThrowOnGetQuarantined($oldDoThrow);
            $controller->setRequestContext($context);
        }

        return $result;
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
        if((isset($recordData['created_by']) || array_key_exists('created_by', $recordData))) {
            return $this->_save($recordData, Filemanager_Controller_Node::getInstance(), 'Node');
        } else {    // on upload complete
            return $recordData;
        }
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchDownloadLinks($filter, $paging)
    {
        return $this->_search($filter, $paging, Filemanager_Controller_DownloadLink::getInstance(), 'Filemanager_Model_DownloadLinkFilter');
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getDownloadLink($id)
    {
        return $this->_get($id, Filemanager_Controller_DownloadLink::getInstance());
    }

    /**
     * Return usage array of a folder
     *
     * @param $_id
     * @return array of folder usage
     */
    public function getFolderUsage($_id)
    {
        $_id = is_array($_id) ?: array($_id);
        $folderUsage = Filemanager_Controller_Node::getInstance()->getFolderUsage($_id);

        $createdBy = $folderUsage['createdBy'];
        $newCreatedBy = array();

        if (count($createdBy) > 0) {
            $accountIds = array_keys($createdBy);
            $accounts = Tinebase_User::getInstance()->getMultiple($accountIds);

            /** @var Tinebase_Model_User $account */
            foreach($accounts as $account) {
                $newCreatedBy[$account->contact_id] = $createdBy[$account->accountId];
            }
            $folderUsage['createdBy'] = $newCreatedBy;

            $folderUsage['contacts'] = Addressbook_Controller_Contact::getInstance()->getMultiple($accounts->contact_id)->toArray();
        } else {
            $folderUsage['contacts'] = array();
        }

        return $folderUsage;
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveDownloadLink($recordData)
    {
        return $this->_save($recordData, Filemanager_Controller_DownloadLink::getInstance(), 'DownloadLink');
    }
    
    /**
     * deletes existing records
     *
     * @param  array $ids
     * @return array
     */
    public function deleteDownloadLinks($ids)
    {
        return $this->_delete($ids, Filemanager_Controller_DownloadLink::getInstance());
    }
}
