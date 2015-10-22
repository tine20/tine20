<?php
/**
 * DownloadLink controller for Filemanager application
 *
 * @package     Filemanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2014 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * DownloadLink controller class for Filemanager application
 *
 * @package     Filemanager
 * @subpackage  Controller
 */
class Filemanager_Controller_DownloadLink extends Tinebase_Controller_Record_Abstract
{
    /**
     * check for container ACLs
     *
     * @var boolean
     *
     * @todo rename to containerACLChecks
     */
    protected $_doContainerACLChecks = false;
    
    /**
     * @var Tinebase_Tree_Node
     */
    protected $_treeNodeBackend;
    
    /**
     * the constructor
     * don't use the constructor. use the singleton
     */
    private function __construct()
    {
        $this->_applicationName = 'Filemanager';
        $this->_modelName = 'Filemanager_Model_DownloadLink';
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName, 
            'tableName' => 'filemanager_downloadlink',
        ));
    }
    
    /**
     * holds the instance of the singleton
     * @var Filemanager_Controller_DownloadLink
     */
    private static $_instance = NULL;
    
    /**
     * inspect update of one record (before update)
     *
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        $this->_sanitizeUserInput($_record);
    }
    
    /**
     * inspect creation of one record (before create)
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_sanitizeUserInput($_record);
    }
    
    /**
     * sanitize user input
     * 
     * @param Tinebase_Record_Interface $record
     */
    protected function _sanitizeUserInput($record)
    {
        // access_count can only be increased when file is downloaded or directory listing is shown
        unset($record->access_count);
    }
    
    /**
     * check if user has the right to manage download links
     *
     * @param string $_action {get|create|update|delete}
     * @return void
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkRight($_action)
    {
        switch ($_action) {
            case 'create':
            case 'update':
            case 'delete':
                $this->checkRight('MANAGE_DOWNLOADLINKS');
                break;
            default;
            break;
        }
    }
    
    /**
     * the singleton pattern
     * @return Filemanager_Controller_DownloadLink
     */
    public static function getInstance()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Filemanager_Controller_DownloadLink();
        }
        
        return self::$_instance;
    }
    
    /**
     * get download link node
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @param array $splittedPath
     * @return Tinebase_Model_Tree_Node
     */
    public function getNode(Filemanager_Model_DownloadLink $download, $splittedPath)
    {
        $this->_checkExpiryDate($download);
        
        $node = $this->_getRootNode($download);
        
        foreach ($splittedPath as $subPath) {
            $node = $this->_getTreeNodeBackend()->getChild($node, $subPath);
        }
        
        return $node;
    }
    
    /**
     * check download link expiry date
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @throws Tinebase_Exception_AccessDenied
     */
    protected function _checkExpiryDate(Filemanager_Model_DownloadLink $download)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Checking download link expiry time: ' . $download->expiry_time);
        
        if ($download->expiry_time instanceof Tinebase_DateTime && $download->expiry_time->isEarlier(Tinebase_DateTime::now())) {
            throw new Tinebase_Exception_AccessDenied('Download link has expired');
        }
    }
    
    /**
     * resolve root tree node
     *
     * @param  Filemanager_Model_DownloadLink $download
     * @return Tinebase_Model_Tree_Node
     */
    protected function _getRootNode(Filemanager_Model_DownloadLink $download)
    {
        // ACL is checked here, download link user should be already set by frontend
        $node = Filemanager_Controller_Node::getInstance()->get($download->node_id);
        
        return $node;
    }
    
    /**
     * get tree node backend instance
     *
     * @return Tinebase_Tree_Node
     */
    protected function _getTreeNodeBackend()
    {
        if (!$this->_treeNodeBackend) {
            $this->_treeNodeBackend = new Tinebase_Tree_Node();
        }
    
        return $this->_treeNodeBackend;
    }
    
    /**
     * get file list
     * 
     * @param Filemanager_Model_DownloadLink $download
     * @param array $splittedPath
     * @param Tinebase_Model_Tree_Node $node
     * @return Tinebase_Record_RecordSet
     * 
     * @todo move basePath calculation to view. In the controller we should start the path with $download-getId().
     */
    public function getFileList(Filemanager_Model_DownloadLink $download, $splittedPath, $node = null)
    {
        if ($node === null) {
            $node = $this->getNode($download, $splittedPath);
        }
        
        $basePath = '/download/show/' . $download->getId() . '/';
        
        if (count($splittedPath) > 0) {
            $basePath .= implode('/', $splittedPath) . '/';
        }
        
        $children = $this->_getTreeNodeBackend()->getChildren($node);
        foreach ($children as $child) {
            $child->path = $basePath . $child->name;
        }
        
        $files = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        if (count($splittedPath) > 0) {
            $parent = $this->_getTreeNodeBackend()->get($node->parent_id);
            $parent->name = '..';
            $parent->path = $basePath . '..';
            
            $files->addRecord($parent);
        }
        $files->merge($children->filter('type', Tinebase_Model_Tree_Node::TYPE_FOLDER)->sort('name'));
        $files->merge($children->filter('type', Tinebase_Model_Tree_Node::TYPE_FILE)->sort('name'));
        
        return $files;
    }

    /**
     * increase access count
     *
     * @param Filemanager_Model_DownloadLink $download
     */
    public function increaseAccessCount(Filemanager_Model_DownloadLink $download)
    {
        $db = (method_exists($this->_backend, 'getAdapter')) ? $this->_backend->getAdapter() : Tinebase_Core::getDb();
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

        $currentRecord = $this->_backend->get($download->getId());
        $currentRecord->access_count++;

        // yes, no history etc.
        $this->_backend->update($currentRecord);

        Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
    }
}
