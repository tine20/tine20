<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem controller
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 */
class Tinebase_FileSystem implements
    Tinebase_Controller_Interface,
    Tinebase_Container_Interface,
    Tinebase_Controller_Alarm_Interface
{
    /**
     * folder name/type for previews
     *
     * @var string
     */
    const FOLDER_TYPE_PREVIEWS = 'previews';

    /**
     * folder name/type for record attachments
     *
     * @var string
     */
    const FOLDER_TYPE_RECORDS = 'records';

    /**
     * folder name/type for record attachments
     *
     * @var string
     */
    const FOLDER_TYPE_SHARED = 'shared';

    /**
     * folder name/type for record attachments
     *
     * @var string
     */
    const FOLDER_TYPE_PERSONAL = 'personal';

    const STREAM_OPTION_CREATE_PREVIEW = 'createPreview';

    /**
     * @var Tinebase_Tree_FileObject
     */
    protected $_fileObjectBackend;
    
    /**
     * @var Tinebase_Tree_Node
     */
    protected $_treeNodeBackend = null;

    /**
     * @var string
     */
    protected $_treeNodeModel = 'Tinebase_Model_Tree_Node';

    /**
     * @var Tinebase_Tree_NodeGrants
     */
    protected $_nodeAclController = null;

    /**
     * path where physical files gets stored
     *
     * @var string
     */
    protected $_basePath;

    protected $_modLogActive = false;

    protected $_indexingActive = false;

    protected $_previewActive = false;

    protected $_streamOptionsForNextOperation = array();

    protected $_notificationActive = false;

    /**
     * stat cache
     *
     * @var array
     */
    protected $_statCache = array();
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_FileSystem
     */
    private static $_instance = null;
    
    /**
     * the constructor
     */
    public function __construct()
    {
        if (! Tinebase_Core::isFilesystemAvailable()) {
            throw new Tinebase_Exception_Backend('No base path (filesdir) configured or path not writeable');
        }

        $config = Tinebase_Core::getConfig();
        $this->_basePath = $config->filesdir;

        $fsConfig = $config->{Tinebase_Config::FILESYSTEM};
        // FIXME why is this check needed (setup tests fail without)?
        if ($fsConfig) {
            $this->_modLogActive = true === $fsConfig->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE};
            $this->_indexingActive = true === $fsConfig->{Tinebase_Config::FILESYSTEM_INDEX_CONTENT};
            $this->_notificationActive = true === $fsConfig->{Tinebase_Config::FILESYSTEM_ENABLE_NOTIFICATIONS};
            $this->_previewActive = true === $fsConfig->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS};
        }

        $this->_fileObjectBackend = new Tinebase_Tree_FileObject(null, array(
            Tinebase_Config::FILESYSTEM_MODLOGACTIVE => $this->_modLogActive
        ));

        $this->_nodeAclController = Tinebase_Tree_NodeGrants::getInstance();
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_FileSystem
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new Tinebase_FileSystem;
        }
        
        return self::$_instance;
    }

    public function resetBackends()
    {
        $config = Tinebase_Core::getConfig()->{Tinebase_Config::FILESYSTEM};
        $this->_modLogActive = true === $config->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE};
        $this->_indexingActive = true === $config->{Tinebase_Config::FILESYSTEM_INDEX_CONTENT};

        $this->_treeNodeBackend = null;

        $this->_fileObjectBackend  = new Tinebase_Tree_FileObject(null, array(
            Tinebase_Config::FILESYSTEM_MODLOGACTIVE => $this->_modLogActive
        ));
    }

    public function setStreamOptionForNextOperation($_key, $_value)
    {
        $this->_streamOptionsForNextOperation[$_key] = $_value;
    }

    /**
     * init application base paths
     *
     * @param Tinebase_Model_Application|string $_application
     */
    public function initializeApplication($_application)
    {
        // create app root node
        $appPath = $this->getApplicationBasePath($_application);
        if (!$this->fileExists($appPath)) {
            $this->mkdir($appPath);
        }
        
        $sharedBasePath = $this->getApplicationBasePath($_application, self::FOLDER_TYPE_SHARED);
        if (!$this->fileExists($sharedBasePath)) {
            $this->mkdir($sharedBasePath);
        }
        
        $personalBasePath = $this->getApplicationBasePath($_application, self::FOLDER_TYPE_PERSONAL);
        if (!$this->fileExists($personalBasePath)) {
            $this->mkdir($personalBasePath);
        }
    }
    
    /**
     * get application base path
     *
     * @param Tinebase_Model_Application|string $_application
     * @param string $_type
     * @return string
     */
    public function getApplicationBasePath($_application, $_type = null)
    {
        $application = $_application instanceof Tinebase_Model_Application
            ? $_application
            : Tinebase_Application::getInstance()->getApplicationById($_application);
        
        $result = '/' . $application->getId();
        
        if ($_type !== null) {
            if (! in_array($_type, array(self::FOLDER_TYPE_SHARED, self::FOLDER_TYPE_PERSONAL,
                    self::FOLDER_TYPE_RECORDS, self::FOLDER_TYPE_PREVIEWS))) {
                throw new Tinebase_Exception_UnexpectedValue('Type can only be shared or personal.');
            }
            
            $result .= '/folders/' . $_type;
        }
        
        return $result;
    }
    
    /**
     * Get one tree node (by id)
     *
     * @param integer|Tinebase_Record_Interface $_id
     * @param boolean $_getDeleted get deleted records
     * @param int|null $_revision
     * @return Tinebase_Model_Tree_Node
     */
    public function get($_id, $_getDeleted = false, $_revision = null)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        $treeBackend = $this->_getTreeNodeBackend();

        try {
            if (null !== $_revision) {
                $treeBackend->setRevision($_revision);
            }
            $node = $treeBackend->get($_id, $_getDeleted);
        } finally {
            if (null !== $_revision) {
                $treeBackend->setRevision(null);
            }
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        }

        return $node;
    }

    protected function _getTreeNodeBackend()
    {
        if ($this->_treeNodeBackend === null) {
            $this->_treeNodeBackend    = new Tinebase_Tree_Node(null, /* options */ array(
                'modelName' => $this->_treeNodeModel,
                Tinebase_Config::FILESYSTEM_ENABLE_NOTIFICATIONS => $this->_notificationActive
            ));
        }

        return $this->_treeNodeBackend;
    }

    /**
     * Get multiple tree nodes identified by id
     *
     * @param string|array $_id Ids
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function getMultipleTreeNodes($_id)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            return $this->_getTreeNodeBackend()->getMultiple($_id);
        } finally {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        }
    }

    /**
     * create new node with acl
     *
     * @param string $path
     * @param array|null $grants
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_SystemGeneric
     */
    public function createAclNode($path, $grants = null)
    {
        $node = null;
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($path);
            if (true === $this->fileExists($pathRecord->statpath)) {
                // TODO always throw exception?
                throw new Tinebase_Exception_SystemGeneric('Node already exists');
            }

            // create folder node
            $node = $this->mkdir($pathRecord->statpath);

            if (null === $grants) {
                switch ($pathRecord->containerType) {
                    case self::FOLDER_TYPE_PERSONAL:
                        $node->grants = Tinebase_Model_Grants::getPersonalGrants($pathRecord->getUser(), array(
                            Tinebase_Model_Grants::GRANT_DOWNLOAD => true,
                            Tinebase_Model_Grants::GRANT_PUBLISH => true,
                        ));
                        break;
                    case self::FOLDER_TYPE_SHARED:
                        $node->grants = Tinebase_Model_Grants::getDefaultGrants(array(
                            Tinebase_Model_Grants::GRANT_DOWNLOAD => true
                        ), array(
                            Tinebase_Model_Grants::GRANT_PUBLISH => true
                        ));
                        break;
                }
            } else {
                $node->grants = $grants;
            }

            $this->_nodeAclController->setGrants($node);
            $node->acl_node = $node->getId();
            $this->update($node);

            // append path for convenience
            $node->path = $path;

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return $node;
    }

    /**
     * set grants for node
     *
     * @param Tinebase_Model_Tree_Node $node
     * @param                          $grants
     * @return Tinebase_Model_Tree_Node
     * @throws Timetracker_Exception_UnexpectedValue
     * @throws Tinebase_Exception_Backend
     *
     * TODO check acl here?
     */
    public function setGrantsForNode(Tinebase_Model_Tree_Node $node, $grants)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $node->grants = $grants;
            $this->_nodeAclController->setGrants($node);
            $node->acl_node = $node->getId();
            $this->update($node);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

            return $node;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }

    /**
     * remove acl from node (inherit acl from parent)
     *
     * @param Tinebase_Model_Tree_Node $node
     * @return Tinebase_Model_Tree_Node
     */
    public function removeAclFromNode(Tinebase_Model_Tree_Node $node)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $parentNode = $this->get($node->parent_id);
            $node->acl_node = $parentNode->acl_node;
            $this->update($node);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

            return $node;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }

    /**
     * get contents of node
     *
     * @param string|Tinebase_Model_Tree_Node $nodeId
     * @return string
     */
    public function getNodeContents($nodeId)
    {
        // getPathOfNode uses transactions and fills the stat cache, so fopen should fetch the node from the stat cache
        // we do not start a transaction here
        $path = $this->getPathOfNode($nodeId, /* $getPathAsString */ true);
        $handle = $this->fopen($path, 'r');
        $contents = stream_get_contents($handle);
        $this->fclose($handle);

        return $contents;
    }
    
    /**
     * clear stat cache
     *
     * @param string $path if given, only remove this path from statcache
     */
    public function clearStatCache($path = null)
    {
        if ($path !== null) {
            unset($this->_statCache[$this->_getCacheId($path)]);
        } else {
            // clear the whole cache
            $this->_statCache = array();
        }
    }
    
    /**
     * copy file/directory
     *
     * @todo copy recursive
     *
     * @param  string  $sourcePath
     * @param  string  $destinationPath
     * @throws Tinebase_Exception_UnexpectedValue
     * @return Tinebase_Model_Tree_Node
     */
    public function copy($sourcePath, $destinationPath)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $destinationNode = $this->stat($sourcePath);
            $sourcePathParts = $this->_splitPath($sourcePath);

            try {
                // does destinationPath exist ...
                $parentNode = $this->stat($destinationPath);

                // ... and is a directory?
                if ($parentNode->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                    throw new Tinebase_Exception_UnexpectedValue
                        ("Destination path exists and is a file. Please remove before.");
                }

                $destinationNodeName = basename(trim($sourcePath, '/'));
                $destinationPathParts = array_merge($this->_splitPath($destinationPath), (array)$destinationNodeName);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // does parent directory of destinationPath exist?
                try {
                    $parentNode = $this->stat(dirname($destinationPath));
                } catch (Tinebase_Exception_NotFound $tenf) {
                    throw new Tinebase_Exception_UnexpectedValue
                        ("Parent directory does not exist. Please create before.");
                }

                $destinationNodeName = basename(trim($destinationPath, '/'));
                $destinationPathParts = array_merge($this->_splitPath(dirname($destinationPath)),
                    (array)$destinationNodeName);
            }

            if ($sourcePathParts == $destinationPathParts) {
                throw new Tinebase_Exception_UnexpectedValue("Source path and destination path must be different.");
            }

            // set new node properties
            $destinationNode->setId(null);
            $destinationNode->parent_id = $parentNode->getId();
            $destinationNode->name = $destinationNodeName;

            $createdNode = $this->_getTreeNodeBackend()->create($destinationNode);

            // update hash of all parent folders
            $this->_updateDirectoryNodesHash(dirname(implode('/', $destinationPathParts)));

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

            return $createdNode;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }
    
    /**
     * get modification timestamp
     *
     * @param  string  $path
     * @return string  UNIX timestamp
     */
    public function getMTime($path)
    {
        $node = $this->stat($path);
        
        $timestamp = $node->last_modified_time instanceof Tinebase_DateTime
            ? $node->last_modified_time->getTimestamp()
            : $node->creation_time->getTimestamp();
        
        return $timestamp;
    }
    
    /**
     * check if file exists
     *
     * @param  string $path
     * @param  integer|null $revision
     * @return boolean true if file/directory exists
     */
    public function fileExists($path, $revision = null)
    {
        try {
            $this->stat($path, $revision);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        
        return true;
    }
    
    /**
     * close file handle
     *
     * @param  resource $handle
     * @return boolean
     */
    public function fclose($handle)
    {
        if (!is_resource($handle)) {
            return false;
        }
        
        $options = stream_context_get_options($handle);
        $this->_streamOptionsForNextOperation = array();

        switch ($options['tine20']['mode']) {
            case 'w':
            case 'wb':
            case 'x':
            case 'xb':
                $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                try {
                    $parentPath = dirname($options['tine20']['path']);

                    list ($hash, $hashFile) = $this->createFileBlob($handle);

                    $parentFolder = $this->stat($parentPath);

                    $this->_updateFileObject($parentFolder, $options['tine20']['node']->object_id, $hash, $hashFile);

                    $this->clearStatCache($options['tine20']['path']);

                    $newNode = $this->stat($options['tine20']['path']);

                    // write modlog and system notes
                    $this->_getTreeNodeBackend()->updated($newNode, $options['tine20']['node']);

                    // update hash of all parent folders
                    $this->_updateDirectoryNodesHash($parentPath);

                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                    $transactionId = null;

                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Writing to file : ' .
                        $options['tine20']['path'] . ' successful.');
                } finally {
                    if (null !== $transactionId) {
                        Tinebase_TransactionManager::getInstance()->rollBack();
                    }
                }
                
                break;
                
            default:
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got mode : ' .
                    $options['tine20']['mode'] . ' - nothing to do.');
        }
        
        fclose($handle);

        return true;
    }

    public function getRealPathForHash($_hash)
    {
        return $this->_basePath . '/' . substr($_hash, 0, 3) . '/' . substr($_hash, 3);
    }

    /**
     * update file object with hash file info
     *
     * @param Tinebase_Model_Tree_Node $_parentNode
     * @param string|Tinebase_Model_Tree_FileObject $_id file object (or id)
     * @param string $_hash
     * @param string $_hashFile
     * @return Tinebase_Model_Tree_FileObject
     */
    protected function _updateFileObject(Tinebase_Model_Tree_Node $_parentNode, $_id, $_hash, $_hashFile = null)
    {
        /** @var Tinebase_Model_Tree_FileObject $currentFileObject */
        $currentFileObject = $_id instanceof Tinebase_Record_Abstract ? $_id : $this->_fileObjectBackend->get($_id);

        if (! $_hash) {
            // use existing hash from file object
            $_hash = $currentFileObject->hash;
        }
        $_hashFile = $_hashFile ?: ($this->getRealPathForHash($_hash));
        
        $updatedFileObject = clone($currentFileObject);
        $updatedFileObject->hash = $_hash;

        if (is_file($_hashFile)) {
            $updatedFileObject->size = filesize($_hashFile);

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_hashFile);
                if ($mimeType !== false) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                            " Setting file contenttype to " . $mimeType);
                    $updatedFileObject->contenttype = $mimeType;
                }
                finfo_close($finfo);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' finfo_open() is not available: Could not get file information.');
            }
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' File hash does not exist - directory?');
        }
        
        $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
        $modLog->setRecordMetaData($updatedFileObject, 'update', $currentFileObject);

        // quick hack for 2014.11 - will be resolved correctly in 2015.11-develop
        if (isset($_SERVER['HTTP_X_OC_MTIME'])) {
            $updatedFileObject->last_modified_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
            Tinebase_Server_WebDAV::getResponse()->setHeader('X-OC-MTime', 'accepted');
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " using X-OC-MTIME: {$updatedFileObject->last_modified_time->format(Tinebase_Record_Abstract::ISO8601LONG)} for {$updatedFileObject->id}");

        }
        
        // sanitize file size, somehow filesize() seems to return empty strings on some systems
        if (empty($updatedFileObject->size)) {
            $updatedFileObject->size = 0;
        }

        $oldKeepRevisionValue = $this->_fileObjectBackend->getKeepOldRevision();
        try {
            if (isset($_parentNode->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION)[Tinebase_Model_Tree_Node::XPROPS_REVISION_ON])) {
                $this->_fileObjectBackend->setKeepOldRevision($_parentNode->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION)[Tinebase_Model_Tree_Node::XPROPS_REVISION_ON]);
            }
            /** @var Tinebase_Model_Tree_FileObject $newFileObject */
            $newFileObject = $this->_fileObjectBackend->update($updatedFileObject);
        } finally {
            $this->_fileObjectBackend->setKeepOldRevision($oldKeepRevisionValue);
        }

        $sizeDiff = ((int)$newFileObject->size) - ((int)$currentFileObject->size);
        $revisionSizeDiff = (((int)$currentFileObject->revision) === ((int)$newFileObject->revision) ? 0 : $newFileObject->revision_size);

        if ($sizeDiff !== 0 || $revisionSizeDiff > 0) {
            // update parents with new sizes
            $this->_updateFolderSizesUpToRoot($this->_getTreeNodeBackend()->getObjectUsage($newFileObject->getId()), $sizeDiff, $revisionSizeDiff);
        }

        if (true === Tinebase_Config::getInstance()->get(Tinebase_Config::FILESYSTEM)->{Tinebase_Config::FILESYSTEM_INDEX_CONTENT}) {
            Tinebase_ActionQueue::getInstance()->queueAction('Tinebase_FOO_FileSystem.indexFileObject', $newFileObject->getId());
        }

        return $newFileObject;
    }

    /**
     * @param Tinebase_Record_RecordSet $_nodes
     * @param int $_sizeDiff
     * @param int $_revisionSizeDiff
     */
    protected function _updateFolderSizesUpToRoot(Tinebase_Record_RecordSet $_nodes, $_sizeDiff, $_revisionSizeDiff)
    {
        $objectIds = $this->_getTreeNodeBackend()->getAllFolderNodes($_nodes)->object_id;
        if (!empty($objectIds)) {
            /** @var Tinebase_Model_Tree_FileObject $fileObject */
            foreach($this->_fileObjectBackend->getMultiple($objectIds) as $fileObject) {
                $fileObject->size = (int)$fileObject->size + (int)$_sizeDiff;
                if ($fileObject->size < 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' size should not become smaller than 0: ' . $fileObject->size . ' for object id: ' . $fileObject->getId());
                    $fileObject->size = 0;
                }
                $fileObject->revision_size = (int)$fileObject->revision_size + (int)$_revisionSizeDiff;
                if ($fileObject->revision_size < 0) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' revision_size should not become smaller than 0: ' . $fileObject->size . ' for object id: ' . $fileObject->getId());
                    $fileObject->revision_size = 0;
                }
                $this->_fileObjectBackend->update($fileObject);
            }
        }
    }

    /**
     * @param string $_objectId
     * @return bool
     */
    public function indexFileObject($_objectId)
    {
        /** @var Tinebase_Model_Tree_FileObject $fileObject */
        try {
            $fileObject = $this->_fileObjectBackend->get($_objectId);
        } catch(Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Could not find file object ' . $_objectId);
            return true;
        }
        if (Tinebase_Model_Tree_FileObject::TYPE_FILE !== $fileObject->type) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' file object ' . $_objectId . ' is not a file: ' . $fileObject->type);
            return true;
        }
        if ($fileObject->hash === $fileObject->indexed_hash) {
            // nothing to do
            return true;
        }

        // we clean up $tmpFile down there in finally
        if (false === ($tmpFile = Tinebase_Fulltext_TextExtract::getInstance()->fileObjectToTempFile($fileObject))) {
            return false;
        }

        $indexedHash = $fileObject->hash;

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {

            try {
                $fileObject = $this->_fileObjectBackend->get($_objectId);
            } catch(Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Could not find file object ' . $_objectId);
                return true;
            }
            if (Tinebase_Model_Tree_FileObject::TYPE_FILE !== $fileObject->type) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                    . ' file object ' . $_objectId . ' is not a file: ' . $fileObject->type);
                return true;
            }
            if ($fileObject->hash === $fileObject->indexed_hash || $indexedHash === $fileObject->indexed_hash) {
                // nothing to do
                return true;
            }

            Tinebase_Fulltext_Indexer::getInstance()->addFileContentsToIndex($fileObject->getId(), $tmpFile);

            $fileObject->indexed_hash = $indexedHash;
            $this->_fileObjectBackend->update($fileObject);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);

        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            Tinebase_TransactionManager::getInstance()->rollBack();

            return false;

        } finally {
            unlink($tmpFile);
        }

        return true;
    }
    
    /**
     * update hash of all directories for given path
     * 
     * @param string $path
     */
    protected function _updateDirectoryNodesHash($path)
    {
        // update hash of all parent folders
        $parentNodes = $this->_getPathNodes($path);
        $updatedNodes = $this->_fileObjectBackend->updateDirectoryNodesHash($parentNodes);
        
        // update nodes stored in local statCache
        $subPath = null;
        /** @var Tinebase_Model_Tree_Node $node */
        foreach ($parentNodes as $node) {
            /** @var Tinebase_Model_Tree_FileObject $directoryObject */
            $directoryObject = $updatedNodes->getById($node->object_id);
            
            if ($directoryObject) {
                $node->revision             = $directoryObject->revision;
                $node->hash                 = $directoryObject->hash;
                $node->size                 = $directoryObject->size;
                $node->revision_size        = $directoryObject->revision_size;
                $node->available_revisions  = $directoryObject->available_revisions;
            }
            
            $subPath .= "/" . $node->name;
            $this->_addStatCache($subPath, $node);
        }
    }
    
    /**
     * open file
     * 
     * @param string $_path
     * @param string $_mode
     * @param int|null $_revision
     * @return resource|boolean
     */
    public function fopen($_path, $_mode, $_revision = null)
    {
        $dirName = dirname($_path);
        $fileName = basename($_path);
        $node = null;
        $handle = null;
        $fileType = isset($this->_streamOptionsForNextOperation[self::STREAM_OPTION_CREATE_PREVIEW]) && true === $this->_streamOptionsForNextOperation[self::STREAM_OPTION_CREATE_PREVIEW] ? Tinebase_Model_Tree_FileObject::TYPE_PREVIEW : Tinebase_Model_Tree_FileObject::TYPE_FILE;

        $rollBack = true;
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            switch ($_mode) {
                // Create and open for writing only; place the file pointer at the beginning of the file.
                // If the file already exists, the fopen() call will fail by returning false and generating
                // an error of level E_WARNING. If the file does not exist, attempt to create it. This is
                // equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
                case 'x':
                case 'xb':
                    if (!$this->isDir($dirName) || $this->fileExists($_path)) {
                        $rollBack = false;
                        return false;
                    }

                    $parent = $this->stat($dirName);
                    $node = $this->createFileTreeNode($parent, $fileName, $fileType);

                    $handle = Tinebase_TempFile::getInstance()->openTempFile();

                    break;

                // Open for reading only; place the file pointer at the beginning of the file.
                case 'r':
                case 'rb':
                    if ($this->isDir($_path) || !$this->fileExists($_path)) {
                        $rollBack = false;
                        return false;
                    }

                    $node = $this->stat($_path, $_revision);
                    $hashFile = $this->getRealPathForHash($node->hash);

                    $handle = fopen($hashFile, $_mode);

                    break;

                // Open for writing only; place the file pointer at the beginning of the file and truncate the
                // file to zero length. If the file does not exist, attempt to create it.
                case 'w':
                case 'wb':
                    if (!$this->isDir($dirName)) {
                        $rollBack = false;
                        return false;
                    }

                    if (!$this->fileExists($_path)) {
                        $parent = $this->stat($dirName);
                        $node = $this->createFileTreeNode($parent, $fileName, $fileType);
                    } else {
                        $node = $this->stat($_path, $_revision);
                        if ($fileType !== $node->type) {
                            $rollBack = false;
                            return false;
                        }
                    }

                    $handle = Tinebase_TempFile::getInstance()->openTempFile();

                    break;

                default:
                    $rollBack = false;
                    return false;
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                if (true === $rollBack) {
                    Tinebase_TransactionManager::getInstance()->rollBack();
                } else {
                    Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                }
            }
        }
        
        $contextOptions = array('tine20' => array(
            'path' => $_path,
            'mode' => $_mode,
            'node' => $node
        ));
        stream_context_set_option($handle, $contextOptions);
        
        return $handle;
    }
    
    /**
     * get content type
     * 
     * @deprecated use Tinebase_FileSystem::stat()->contenttype
     * @param  string  $path
     * @return string
     */
    public function getContentType($path)
    {
        $node = $this->stat($path);
        
        return $node->contenttype;
    }
    
    /**
     * get etag
     * 
     * @deprecated use Tinebase_FileSystem::stat()->hash
     * @param  string $path
     * @return string
     */
    public function getETag($path)
    {
        $node = $this->stat($path);
        
        return $node->hash;
    }
    
    /**
     * return if path is a directory
     * 
     * @param  string  $path
     * @return boolean
     */
    public function isDir($path)
    {
        try {
            $node = $this->stat($path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        
        if ($node->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            return false;
        }
        
        return true;
    }
    
    /**
     * return if path is a file
     *
     * @param  string  $path
     * @return boolean
     */
    public function isFile($path)
    {
        try {
            $node = $this->stat($path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
    
        if ($node->type != Tinebase_Model_Tree_FileObject::TYPE_FILE) {
            return false;
        }
    
        return true;
    }
    
    /**
     * rename file/directory
     *
     * @param  string  $oldPath
     * @param  string  $newPath
     * @return Tinebase_Model_Tree_Node|boolean
     */
    public function rename($oldPath, $newPath)
    {
        $transactionManager = Tinebase_TransactionManager::getInstance();
        $transactionId = $transactionManager->startTransaction(Tinebase_Core::getDb());

        try {
            try {
                $node = $this->stat($oldPath);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                return false;
            } catch (Tinebase_Exception_NotFound $tenf) {
                return false;
            }

            if (dirname($oldPath) != dirname($newPath)) {
                try {
                    $newParent = $this->stat(dirname($newPath));
                    $oldParent = $this->stat(dirname($oldPath));
                } catch (Tinebase_Exception_InvalidArgument $teia) {
                    return false;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    return false;
                }

                if ($node->acl_node === $oldParent->acl_node && $newParent->acl_node !== $node->acl_node) {
                    $node->acl_node = $newParent->acl_node;
                    if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $node->type) {
                        $this->_recursiveInheritPropertyUpdate($node, 'acl_node', $newParent->acl_node, $oldParent->acl_node);
                    }
                }

                if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $node->type) {
                    if ($node->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION) == $oldParent->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION) &&
                        $node->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION) != $newParent->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION)
                    ) {
                        $node->{Tinebase_Model_Tree_Node::XPROPS_REVISION} = $newParent->{Tinebase_Model_Tree_Node::XPROPS_REVISION};
                        $oldValue = $oldParent->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION);
                        $newValue = $newParent->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION);
                        $oldValue = count($oldValue) > 0 ? json_encode($oldValue) : null;
                        $newValue = count($newValue) > 0 ? json_encode($newValue) : null;
                        if (null === $newValue) {
                            $node->{Tinebase_Model_Tree_Node::XPROPS_REVISION} = null;
                        }
                        // update revisionProps of subtree if changed
                        $this->_recursiveInheritFolderPropertyUpdate($node, Tinebase_Model_Tree_Node::XPROPS_REVISION, $newValue, $oldValue, false);
                    }
                }

                $node->parent_id = $newParent->getId();

                $this->_updateFolderSizesUpToRoot(new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($oldParent)),
                    0 - (int)$node->size, 0 - (int)$node->revision_size);
                $this->_updateFolderSizesUpToRoot(new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($newParent)),
                    (int)$node->size, (int)$node->revision_size);
            }

            if (basename($oldPath) != basename($newPath)) {
                $node->name = basename($newPath);
            }

            $node = $this->_getTreeNodeBackend()->update($node, true);

            $transactionManager->commitTransaction($transactionId);
            $transactionId = null;

            $this->clearStatCache($oldPath);

            $this->_addStatCache($newPath, $node);

            return $node;

        } finally {
            if (null !== $transactionId) {
                $transactionManager->rollBack();
            }
        }
    }
    
    /**
     * create directory
     * 
     * @param string $path
     * @return Tinebase_Model_Tree_Node
     */
    public function mkdir($path)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Creating directory ' . $path);
        
        $currentPath = array();
        $parentNode  = null;
        $pathParts   = $this->_splitPath($path);
        $node = null;

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            foreach ($pathParts as $pathPart) {
                $pathPart = trim($pathPart);
                $currentPath[] = $pathPart;

                try {
                    $node = $this->stat('/' . implode('/', $currentPath));
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $node = $this->createDirectoryTreeNode($parentNode, $pathPart);

                    $this->_addStatCache($currentPath, $node);
                }

                $parentNode = $node;
            }

            // update hash of all parent folders
            $this->_updateDirectoryNodesHash($path);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
        
        return $node;
    }

    /**
     * remove directory
     *
     * @param  string $path
     * @param  boolean $recursive
     * @param  boolean $recursion
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function rmdir($path, $recursive = false, $recursion = false)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Removing directory ' . $path);

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $node = $this->stat($path);

            $children = $this->getTreeNodeChildren($node);

            // check if child entries exists and delete if $_recursive is true
            if (count($children) > 0) {
                if ($recursive !== true) {
                    throw new Tinebase_Exception_InvalidArgument('directory not empty');
                } else {
                    foreach ($children as $child) {
                        if ($this->isDir($path . '/' . $child->name)) {
                            $this->rmdir($path . '/' . $child->name, true, true);
                        } else {
                            $this->unlink($path . '/' . $child->name, true);
                        }
                    }
                }
            }

            if (false === $recursion) {
                $this->_updateFolderSizesUpToRoot(new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($node)),
                    0 - (int)$node->size, 0 - (int)$node->revision_size);
            }
            $this->_getTreeNodeBackend()->delete($node->getId());
            $this->clearStatCache($path);

            // delete object only, if no other tree node refers to it
            // we can use treeNodeBackend property because getTreeNodeBackend was called just above
            if ($this->_treeNodeBackend->getObjectCount($node->object_id) == 0) {
                $this->_fileObjectBackend->softDelete($node->object_id);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
        
        return true;
    }
    
    /**
     * scan dir
     * 
     * @param  string  $path
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function scanDir($path)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $children = $this->getTreeNodeChildren($this->stat($path));

            foreach ($children as $node) {
                $this->_addStatCache($path . '/' . $node->name, $node);
            }

            return $children;
        } finally {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        }
    }

    /**
     * return node for a path, caches found nodes in statcache
     *
     * @param  string  $path
     * @param  int|null $revision
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    public function stat($path, $revision = null)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {

            $pathParts = $this->_splitPath($path);
            $cacheId = $this->_getCacheId($pathParts, $revision);

            // let's see if the path is cached in statCache
            if ((isset($this->_statCache[$cacheId]) || array_key_exists($cacheId, $this->_statCache))) {
                try {
                    // let's try to get the node from backend, to make sure it still exists
                    $this->_getTreeNodeBackend()->setRevision($revision);
                    return $this->_checkRevision($this->_getTreeNodeBackend()->get($this->_statCache[$cacheId]), $revision);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    // something went wrong. let's clear the whole statCache
                    $this->clearStatCache();
                } finally {
                    $this->_getTreeNodeBackend()->setRevision(null);
                }
            }

            $parentNode = null;
            $node       = null;

            // find out if we have cached any node up in the path
            do {
                $cacheId = $this->_getCacheId($pathParts);

                if ((isset($this->_statCache[$cacheId]) || array_key_exists($cacheId, $this->_statCache))) {
                    $node = $parentNode = $this->_statCache[$cacheId];
                    break;
                }
            } while (($pathPart = array_pop($pathParts) !== null));

            $missingPathParts = array_diff_assoc($this->_splitPath($path), $pathParts);

            foreach ($missingPathParts as $pathPart) {
                $node = $this->_getTreeNodeBackend()->getChild($parentNode, $pathPart);

                // keep track of current path position
                array_push($pathParts, $pathPart);

                // add found path to statCache
                $this->_addStatCache($pathParts, $node);

                $parentNode = $node;
            }

            if (null !== $revision) {
                try {
                    $this->_getTreeNodeBackend()->setRevision($revision);
                    $node = $this->_checkRevision($this->_getTreeNodeBackend()->get($node->getId()), $revision);

                    // add found path to statCache
                    $this->_addStatCache($pathParts, $node, $revision);
                } finally {
                    $this->_getTreeNodeBackend()->setRevision(null);
                }
            }

            // TODO needed here?
            $node->path = $path;

            return $node;

        } finally {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        }

        // just for PHPStorm Code Inspect
        return null;
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param int|null $_revision
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    protected function _checkRevision(Tinebase_Model_Tree_Node $_node, $_revision)
    {
        if (null !== $_revision && empty($_node->hash)) {
            throw new Tinebase_Exception_NotFound('file does not have revision: ' . $_revision);
        }

        return $_node;
    }

    /**
     * get filesize
     * 
     * @deprecated use Tinebase_FileSystem::stat()->size
     * @param  string  $path
     * @return integer
     */
    public function filesize($path)
    {
        $node = $this->stat($path);
        
        return $node->size;
    }
    
    /**
     * delete file
     * 
     * @param  string  $path
     * @param  boolean $recursion
     * @return boolean
     */
    public function unlink($path, $recursion = false)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            $node = $this->stat($path);
            $this->deleteFileNode($node, false === $recursion);

            $this->clearStatCache($path);

            // update hash of all parent folders
            $this->_updateDirectoryNodesHash(dirname($path));

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return true;
    }

    /**
     * delete file node
     *
     * @param Tinebase_Model_Tree_Node $node
     * @param bool $updateDirectoryNodesHash
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function deleteFileNode(Tinebase_Model_Tree_Node $node, $updateDirectoryNodesHash = true)
    {
        if ($node->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            throw new Tinebase_Exception_InvalidArgument('can not unlink directories');
        }

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {

            if (true === $updateDirectoryNodesHash) {
                $this->_updateFolderSizesUpToRoot(new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($node)),
                    0 - (int)$node->size, 0 - (int)$node->revision_size);

                try {
                    $path = Tinebase_Model_Tree_Node_Path::createFromPath($this->getPathOfNode($node, true));
                    $this->_updateDirectoryNodesHash(dirname($path->statpath));

                    // Tinebase_Model_Tree_Node_Path::_getContainerType may find that is not a personal or shared container (for example it may be a records container)
                } catch (Tinebase_Exception_InvalidArgument $teia) {}
            }

            $this->_getTreeNodeBackend()->softDelete($node->getId());

            // delete object only, if no one uses it anymore
            // we can use treeNodeBackend property because getTreeNodeBackend was called just above
            if ($this->_treeNodeBackend->getObjectCount($node->object_id) === 0) {
                if (false === $this->_modLogActive && true === $this->_previewActive) {
                    $hashes = $this->_fileObjectBackend->getHashes(array($node->object_id));
                } else {
                    $hashes = array();
                }
                $this->_fileObjectBackend->softDelete($node->object_id);
                if (false === $this->_modLogActive ) {
                    if (true === $this->_indexingActive) {
                        Tinebase_Fulltext_Indexer::getInstance()->removeFileContentsFromIndex($node->object_id);
                    }
                    if (true === $this->_previewActive) {
                        $existingHashes = $this->_fileObjectBackend->checkRevisions($hashes);
                        $hashesToDelete = array_diff($hashes, $existingHashes);
                        Tinebase_FileSystem_Previews::getInstance()->deletePreviews($hashesToDelete);
                    }
                }
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }
    
    /**
     * create directory
     * 
     * @param  string|Tinebase_Model_Tree_Node  $_parentId
     * @param  string                           $name
     * @return Tinebase_Model_Tree_Node
     */
    public function createDirectoryTreeNode($_parentId, $name)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
            $parentNode = $_parentId instanceof Tinebase_Model_Tree_Node
                ? $_parentId
                : ($_parentId ? $this->get($_parentId) : null);

            $directoryObject = new Tinebase_Model_Tree_FileObject(array(
                'type'          => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                'contentytype'  => null,
                'hash'          => Tinebase_Record_Abstract::generateUID(),
                'size'          => 0
            ));
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($directoryObject, 'create');
            $directoryObject = $this->_fileObjectBackend->create($directoryObject);

            $treeNode = new Tinebase_Model_Tree_Node(array(
                'name'          => $name,
                'object_id'     => $directoryObject->getId(),
                'parent_id'     => $parentId,
                'acl_node'      => $parentNode && !empty($parentNode->acl_node) ? $parentNode->acl_node : null,
                Tinebase_Model_Tree_Node::XPROPS_REVISION
                                => $parentNode && !empty($parentNode->{Tinebase_Model_Tree_Node::XPROPS_REVISION}) ? $parentNode->{Tinebase_Model_Tree_Node::XPROPS_REVISION} : null
            ));
            $treeNode = $this->_getTreeNodeBackend()->create($treeNode);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

            return $treeNode;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }
    
    /**
     * create new file node
     * 
     * @param  string|Tinebase_Model_Tree_Node  $_parentId
     * @param  string                           $_name
     * @param  string                           $_fileType
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    public function createFileTreeNode($_parentId, $_name, $_fileType = Tinebase_Model_Tree_FileObject::TYPE_FILE)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
            $parentNode = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId : $this->get($parentId);

            $fileObject = new Tinebase_Model_Tree_FileObject(array(
                'type'          => $_fileType,
                'contentytype'  => null,
            ));
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($fileObject, 'create');

            // quick hack for 2014.11 - will be resolved correctly in 2015.11-develop
            if (isset($_SERVER['HTTP_X_OC_MTIME'])) {
                $fileObject->creation_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
                $fileObject->last_modified_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
                Tinebase_Server_WebDAV::getResponse()->setHeader('X-OC-MTime', 'accepted');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " using X-OC-MTIME: {$fileObject->last_modified_time->format(Tinebase_Record_Abstract::ISO8601LONG)} for {$_name}");

            }

            $fileObject = $this->_fileObjectBackend->create($fileObject);

            $treeNode = new Tinebase_Model_Tree_Node(array(
                'name'          => $_name,
                'object_id'     => $fileObject->getId(),
                'parent_id'     => $parentId,
                'acl_node'      => $parentNode && empty($parentNode->acl_node) ? null : $parentNode->acl_node,
            ));

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                ' ' . print_r($treeNode->toArray(), true));

            $treeNode = $this->_getTreeNodeBackend()->create($treeNode);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

            return $treeNode;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }

    /**
     * places contents into a file blob
     * 
     * @param  resource $contents
     * @return string hash
     * @throws Tinebase_Exception_NotImplemented
     */
    public function createFileBlob($contents)
    {
        if (! is_resource($contents)) {
            throw new Tinebase_Exception_NotImplemented('please implement me!');
        }
        
        $handle = $contents;
        rewind($handle);
        
        $ctx = hash_init('sha1');
        hash_update_stream($ctx, $handle);
        $hash = hash_final($ctx);
        
        $hashDirectory = $this->_basePath . '/' . substr($hash, 0, 3);
        if (!file_exists($hashDirectory)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create hash directory: ' . $hashDirectory);
            if(mkdir($hashDirectory, 0700) === false) {
                throw new Tinebase_Exception_UnexpectedValue('failed to create directory');
            }
        }
        
        $hashFile      = $hashDirectory . '/' . substr($hash, 3);
        if (!file_exists($hashFile)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create hash file: ' . $hashFile);
            rewind($handle);
            $hashHandle = fopen($hashFile, 'x');
            stream_copy_to_stream($handle, $hashHandle);
            fclose($hashHandle);
        }
        
        return array($hash, $hashFile);
    }
    
    /**
     * get tree node children
     * 
     * @param string|Tinebase_Model_Tree_Node|Tinebase_Record_RecordSet  $nodeId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     *
     * TODO always ignore acl here?
     */
    public function getTreeNodeChildren($nodeId)
    {
        if ($nodeId instanceof Tinebase_Model_Tree_Node) {
            $nodeId = $nodeId->getId();
            $operator = 'equals';
        } elseif ($nodeId instanceof Tinebase_Record_RecordSet) {
            $nodeId = $nodeId->getArrayOfIds();
            $operator = 'in';
        } else {
            $operator = 'equals';
        }
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => $operator,
                'value'     => $nodeId
            )
        ), Tinebase_Model_Filter_FilterGroup::CONDITION_AND, array('ignoreAcl' => true));
        $children = $this->searchNodes($searchFilter);
        
        return $children;
    }
    
    /**
     * search tree nodes
     * 
     * @param Tinebase_Model_Tree_Node_Filter $_filter
     * @param Tinebase_Record_Interface $_pagination
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function searchNodes(Tinebase_Model_Tree_Node_Filter $_filter = null, Tinebase_Record_Interface $_pagination = null)
    {
        $result = $this->_getTreeNodeBackend()->search($_filter, $_pagination);
        return $result;
    }

    /**
     * search tree nodes
     *
     * TODO replace searchNodes / or refactor this - tree objects has no search function yet / might be ambiguous...
     *
     * @param Tinebase_Model_Tree_Node_Filter $_filter
     * @param Tinebase_Record_Interface $_pagination
     * @param boolean $_onlyIds
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function search(Tinebase_Model_Tree_Node_Filter $_filter = null, Tinebase_Record_Interface $_pagination = null, $_onlyIds = false)
    {
        $result = $this->_getTreeNodeBackend()->search($_filter, $_pagination, $_onlyIds);
        return $result;
    }

    /**
    * search tree nodes count
    *
    * @param Tinebase_Model_Tree_Node_Filter $_filter
    * @return integer
    */
    public function searchNodesCount(Tinebase_Model_Tree_Node_Filter $_filter = null)
    {
        $result = $this->_getTreeNodeBackend()->searchCount($_filter);
        return $result;
    }

    /**
     * get tree node specified by parent node (or id) and name
     * 
     * @param string|Tinebase_Model_Tree_Node $_parentId
     * @param string $_name
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    public function getTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Getting tree node ' . $parentId . '/'. $_name);
        
        return$this->_getTreeNodeBackend()->getChild($_parentId, $_name);
    }
    
    /**
     * add entry to stat cache
     * 
     * @param string|array              $path
     * @param Tinebase_Model_Tree_Node  $node
     * @param int|null                  $revision
     */
    protected function _addStatCache($path, Tinebase_Model_Tree_Node $node, $revision = null)
    {
        $this->_statCache[$this->_getCacheId($path, $revision)] = $node;
    }
    
    /**
     * generate cache id
     * 
     * @param  string|array  $path
     * @param  int|null $revision
     * @return string
     */
    protected function _getCacheId($path, $revision = null)
    {
        $pathParts = is_array($path) ? $path : $this->_splitPath($path);
        array_unshift($pathParts, '@' . $revision);

        return sha1(implode(null, $pathParts));
    }
    
    /**
     * split path
     * 
     * @param  string  $path
     * @return array
     */
    protected function _splitPath($path)
    {
        return explode('/', trim($path, '/'));
    }
    
    /**
     * update node
     * 
     * @param Tinebase_Model_Tree_Node $_node
     * @return Tinebase_Model_Tree_Node
     */
    public function update(Tinebase_Model_Tree_Node $_node)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {
            $currentNodeObject = $this->get($_node->getId());
            $fileObject = $this->_fileObjectBackend->get($currentNodeObject->object_id);

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($_node, 'update', $currentNodeObject);
            Tinebase_Timemachine_ModificationLog::setRecordMetaData($fileObject, 'update', $fileObject);

            // quick hack for 2014.11 - will be resolved correctly in 2016.11-develop?
            if (isset($_SERVER['HTTP_X_OC_MTIME'])) {
                $fileObject->last_modified_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
                Tinebase_Server_WebDAV::getResponse()->setHeader('X-OC-MTime', 'accepted');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " using X-OC-MTIME: {$fileObject->last_modified_time->format(Tinebase_Record_Abstract::ISO8601LONG)} for {$_node->name}");

            }

            // update file object
            $fileObject->description = $_node->description;
            $this->_updateFileObject($this->get($currentNodeObject->parent_id), $fileObject, $_node->hash);

            if ($currentNodeObject->acl_node !== $_node->acl_node) {
                // update acl_node of subtree if changed
                $this->_recursiveInheritPropertyUpdate($_node, 'acl_node', $_node->acl_node, $currentNodeObject->acl_node);
            }

            $oldValue = $currentNodeObject->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION);
            $newValue = $_node->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION);

            if ($oldValue != $newValue) {
                $oldValue = count($oldValue) > 0 ? json_encode($oldValue) : null;
                $newValue = count($newValue) > 0 ? json_encode($newValue) : null;

                // update revisionProps of subtree if changed
                $this->_recursiveInheritFolderPropertyUpdate($_node, Tinebase_Model_Tree_Node::XPROPS_REVISION, $newValue, $oldValue, false);
            }

            $newNode = $this->_getTreeNodeBackend()->update($_node);

            $this->_getTreeNodeBackend()->updated($newNode, $currentNodeObject);

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;

        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }

        return $newNode;
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_property
     * @param string $_newValue
     * @param string $_oldValue
     */
    protected function _recursiveInheritPropertyUpdate(Tinebase_Model_Tree_Node $_node, $_property, $_newValue, $_oldValue)
    {
        $childIds = $this->getAllChildIds(array($_node->getId()), array(array(
            'field'     => $_property,
            'operator'  => 'equals',
            'value'     => $_oldValue
        )));
        if (count($childIds) > 0) {
            $this->_getTreeNodeBackend()->updateMultiple($childIds, array($_property => $_newValue));
        }
    }

    /**
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_property
     * @param string $_newValue
     * @param string $_oldValue
     * @param bool  $_ignoreACL
     */
    protected function _recursiveInheritFolderPropertyUpdate(Tinebase_Model_Tree_Node $_node, $_property, $_newValue, $_oldValue, $_ignoreACL = true)
    {
        $childIds = $this->getAllChildIds(array($_node->getId()), array(
            array(
                'field'     => $_property,
                'operator'  => 'equals',
                'value'     => $_oldValue
            ),
            array(
                'field'     => 'type',
                'operator'  => 'equals',
                'value'     => Tinebase_Model_Tree_FileObject::TYPE_FOLDER
            )
        ), $_ignoreACL, (true === $_ignoreACL ? null : array(Tinebase_Model_PersistentFilterGrant::GRANT_EDIT)));
        if (count($childIds) > 0) {
            $this->_getTreeNodeBackend()->updateMultiple($childIds, array($_property => $_newValue));
        }
    }

    /**
     * returns all children nodes, allows to set addition filters
     *
     * @param array         $_ids
     * @param array         $_additionalFilters
     * @param bool          $_ignoreAcl
     * @param array|null    $_requiredGrants
     * @return array
     */
    public function getAllChildIds(array $_ids, array $_additionalFilters = array(), $_ignoreAcl = true, $_requiredGrants = null)
    {
        $result = array();
        $filter = array(
            array(
                'field'     => 'parent_id',
                'operator'  => 'in',
                'value'     => $_ids
            )
        );
        foreach($_additionalFilters as $aF) {
            $filter[] = $aF;
        }
        $searchFilter = new Tinebase_Model_Tree_Node_Filter($filter,  /* $_condition = */ '', /* $_options */ array(
            'ignoreAcl' => $_ignoreAcl,
        ));
        if (null !== $_requiredGrants) {
            $searchFilter->setRequiredGrants($_requiredGrants);
        }
        $children = $this->search($searchFilter, null, true);
        if (count($children) > 0) {
            $result = array_merge($result, $children, $this->getAllChildIds($children, $_additionalFilters, $_ignoreAcl));
        }

        return $result;
    }

    /**
     * get path of node
     * 
     * @param Tinebase_Model_Tree_Node|string $node
     * @param boolean $getPathAsString
     * @return array|string
     */
    public function getPathOfNode($node, $getPathAsString = false)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $node = $node instanceof Tinebase_Model_Tree_Node ? $node : $this->get($node);

            $nodesPath = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($node));
            while ($node->parent_id) {
                $node = $this->get($node->parent_id);
                $nodesPath->addRecord($node);
            }

            $result = ($getPathAsString) ? '/' . implode('/', array_reverse($nodesPath->name)) : array_reverse($nodesPath->toArray());
            return $result;
        } finally {
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        }
    }
    
    protected function _getPathNodes($path)
    {
        $pathParts = $this->_splitPath($path);
        
        if (empty($pathParts)) {
            throw new Tinebase_Exception_InvalidArgument('empty path provided');
        }
        
        $subPath   = null;
        $pathNodes = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        
        foreach ($pathParts as $pathPart) {
            $subPath .= "/$pathPart"; 
            
            $node = $this->stat($subPath);
            if ($node) {
                $pathNodes->addRecord($node);
            }
        }
        
        return $pathNodes;
    }
    
    /**
     * clears deleted files from filesystem + database
     */
    public function clearDeletedFiles()
    {
        $this->clearDeletedFilesFromFilesystem();
        $this->clearDeletedFilesFromDatabase();
    }

    /**
     * removes deleted files that no longer exist in the database from the filesystem
     * @return int number of deleted files
     * @throws Tinebase_Exception_AccessDenied
     */
    public function clearDeletedFilesFromFilesystem()
    {
        try {
            $dirIterator = new DirectoryIterator($this->_basePath);
        } catch (Exception $e) {
            throw new Tinebase_Exception_AccessDenied('Could not open files directory.');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Scanning ' . $this->_basePath . ' for deleted files ...');
        
        $deleteCount = 0;
        /** @var DirectoryIterator $item */
        foreach ($dirIterator as $item) {
            if (!$item->isDir()) {
                continue;
            }
            $subDir = $item->getFilename();
            if ($subDir[0] == '.') continue;
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Checking ' . $subDir);
            $subDirIterator = new DirectoryIterator($this->_basePath . '/' . $subDir);
            $hashsToCheck = array();
            // loop dirs + check if files in dir are in tree_filerevisions
            /** @var DirectoryIterator $file */
            foreach ($subDirIterator as $file) {
                if ($file->isFile()) {
                    $hash = $subDir . $file->getFilename();
                    $hashsToCheck[] = $hash;
                }
            }
            $existingHashes = $this->_fileObjectBackend->checkRevisions($hashsToCheck);
            $hashesToDelete = array_diff($hashsToCheck, $existingHashes);
            // remove from filesystem if not existing any more
            foreach ($hashesToDelete as $hashToDelete) {
                $filename = $this->_basePath . '/' . $subDir . '/' . substr($hashToDelete, 3);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Deleting ' . $filename);
                unlink($filename);
                $deleteCount++;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Deleted ' . $deleteCount . ' obsolete file(s).');
        
        return $deleteCount;
    }
    
    /**
     * removes deleted files that no longer exist in the filesystem from the database
     * 
     * @return integer number of deleted files
     */
    public function clearDeletedFilesFromDatabase()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Scanning database for deleted files ...');

        // get all file objects from db and check filesystem existance
        $filter = new Tinebase_Model_Tree_FileObjectFilter();
        $start = 0;
        $limit = 500;
        $toDeleteIds = array();

        do {
            $pagination = new Tinebase_Model_Pagination(array(
                'start' => $start,
                'limit' => $limit,
                'sort' => 'id',
            ));

            /** @var Tinebase_Record_RecordSet $fileObjects */
            $fileObjects = $this->_fileObjectBackend->search($filter, $pagination);
            /** @var Tinebase_Model_Tree_FileObject $fileObject */
            foreach ($fileObjects as $fileObject) {
                if (($fileObject->type === Tinebase_Model_Tree_FileObject::TYPE_FILE || $fileObject->type === Tinebase_Model_Tree_FileObject::TYPE_PREVIEW)
                        && $fileObject->hash && !file_exists($fileObject->getFilesystemPath())) {
                    $toDeleteIds[] = $fileObject->getId();
                }
            }

            $start += $limit;
        } while ($fileObjects->count() >= $limit);

        if (count($toDeleteIds) === 0) {
            return 0;
        }

        $nodeIdsToDelete = $this->_getTreeNodeBackend()->search(
            new Tinebase_Model_Tree_Node_Filter(array(array(
                'field'     => 'object_id',
                'operator'  => 'in',
                'value'     => $toDeleteIds
            )), /* $_condition = */ '',
                /* $_options */ array(
                    'ignoreAcl' => true,
                )
            ),
            null,
            Tinebase_Backend_Sql_Abstract::IDCOL
        );

        // hard delete is ok here
        $deleteCount = $this->_getTreeNodeBackend()->delete($nodeIdsToDelete);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed ' . $deleteCount . ' obsolete filenode(s) from the database.');

        if (true === $this->_previewActive) {
            $hashes = $this->_fileObjectBackend->getHashes($toDeleteIds);
        } else {
            $hashes = array();
        }

        $this->_fileObjectBackend->delete($toDeleteIds);
        if (true === $this->_indexingActive) {
            Tinebase_Fulltext_Indexer::getInstance()->removeFileContentsFromIndex($toDeleteIds);
        }

        if (true === $this->_previewActive && count($hashes) > 0) {
            $existingHashes = $this->_fileObjectBackend->checkRevisions($hashes);
            $hashesToDelete = array_diff($hashes, $existingHashes);
            Tinebase_FileSystem_Previews::getInstance()->deletePreviews($hashesToDelete);
        }

        return $deleteCount;
    }

    /**
     * copy tempfile data to file path
     * 
     * @param  mixed   $tempFile
         Tinebase_Model_Tree_Node     with property hash, tempfile or stream
         Tinebase_Model_Tempfile      tempfile
         string                       with tempFile id
         array                        with [id] => tempFile id (this is odd IMHO)
         stream                       stream ressource
         null                         create empty file
     * @param  string  $path
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_AccessDenied
     */
    public function copyTempfile($tempFile, $path)
    {
        if ($tempFile === null) {
            $tempStream = fopen('php://memory', 'r');
        } else if (is_resource($tempFile)) {
            $tempStream = $tempFile;
        } else if (is_string($tempFile) || is_array($tempFile)) {
            $tempFile = Tinebase_TempFile::getInstance()->getTempFile($tempFile);
            return $this->copyTempfile($tempFile, $path);
        } else if ($tempFile instanceof Tinebase_Model_Tree_Node) {
            if (isset($tempFile->hash)) {
                $hashFile = $this->getRealPathForHash($tempFile->hash);
                $tempStream = fopen($hashFile, 'r');
            } else if (is_resource($tempFile->stream)) {
                $tempStream = $tempFile->stream;
            } else {
                return $this->copyTempfile($tempFile->tempFile, $path);
            }
        } else if ($tempFile instanceof Tinebase_Model_TempFile) {
            $tempStream = fopen($tempFile->path, 'r');
        } else {
            throw new Tinebase_Exception_UnexpectedValue('unexpected tempfile value');
        }
        
        $this->copyStream($tempStream, $path);

        // TODO revision properties need to be inherited

        $node = $this->setAclFromParent($path);

        return $node;
    }

    public function setAclFromParent($path)
    {
        $node = $this->stat($path);
        $parent = $this->get($node->parent_id);
        $node->acl_node = $parent->acl_node;
        $this->update($node);

        return $node;
    }
    
    /**
     * copy stream data to file path
     *
     * @param  resource  $in
     * @param  string  $path
     * @throws Tinebase_Exception_AccessDenied
     * @throws Tinebase_Exception_UnexpectedValue
     */
    public function copyStream($in, $path)
    {
        if (! $handle = $this->fopen($path, 'w')) {
            throw new Tinebase_Exception_AccessDenied('Permission denied to create file (filename ' . $path . ')');
        }
        
        if (! is_resource($in)) {
            throw new Tinebase_Exception_UnexpectedValue('source needs to be of type stream');
        }
        
        if (is_resource($in) !== null) {
            $metaData = stream_get_meta_data($in);
            if (true === $metaData['seekable']) {
                rewind($in);
            }
            stream_copy_to_stream($in, $handle);
            
            $this->clearStatCache($path);
        }
        
        $this->fclose($handle);
    }

    /**
     * recalculates all revision sizes of file objects of type file only
     *
     * on error it still continues and tries to calculate as many revision sizes as possible, but returns false
     *
     * @return bool
     */
    public function recalculateRevisionSize()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting to recalculate revision size');
        return $this->_fileObjectBackend->recalculateRevisionSize();
    }

    /**
     * recalculates all folder sizes
     *
     * on error it still continues and tries to calculate as many folder sizes as possible, but returns false
     *
     * @return bool
     */
    public function recalculateFolderSize()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting to recalculate folder size');
        return $this->_getTreeNodeBackend()->recalculateFolderSize($this->_fileObjectBackend);
    }

    /**
     * indexes all not indexed file objects
     *
     * on error it still continues and tries to index as many file objects as possible, but returns false
     *
     * @return bool
     */
    public function checkIndexing()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting to check indexing');

        if (false === $this->_indexingActive) {
            return true;
        }

        $success = true;
        foreach($this->_fileObjectBackend->getNotIndexedObjectIds() as $objectId) {
            $success = $this->indexFileObject($objectId) && $success;
        }

        return $success;
    }

    /**
     * check acl of path
     *
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param string $_action
     * @param boolean $_topLevelAllowed
     * @throws Tinebase_Exception_AccessDenied
     */
    public function checkPathACL(Tinebase_Model_Tree_Node_Path $_path, $_action = 'get', /** @noinspection PhpUnusedParameterInspection */ $_topLevelAllowed = true)
    {
        switch ($_path->containerType) {
            case Tinebase_FileSystem::FOLDER_TYPE_PERSONAL:
                if ($_path->containerOwner) {
                    $hasPermission = ($_path->containerOwner === Tinebase_Core::getUser()->accountLoginName || $_action === 'get');
                } else {
                    $hasPermission = ($_action === 'get');
                }
                break;
            case Tinebase_FileSystem::FOLDER_TYPE_SHARED:
                if ($_action !== 'get') {
                    // TODO check if app has MANAGE_SHARED_FOLDERS richt?
                    $hasPermission = Tinebase_Acl_Roles::getInstance()->hasRight(
                        $_path->application->name,
                        Tinebase_Core::getUser()->getId(),
                        Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS
                    );
                } else {
                    $hasPermission = true;
                }
                break;
            case Tinebase_Model_Tree_Node_Path::TYPE_ROOT:
                $hasPermission = ($_action === 'get');
                break;
            default:
                $hasPermission = $this->checkACLNode($_path->getNode(), $_action);
        }

        if (! $hasPermission) {
            throw new Tinebase_Exception_AccessDenied('No permission to ' . $_action . ' nodes in path ' . $_path->flatpath);
        }
    }

    /**
     * check if user has the permissions for the node
     *
     * does not start a transaction!
     *
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_action get|update|...
     * @return boolean
     */
    public function checkACLNode(Tinebase_Model_Tree_Node $_node, $_action = 'get')
    {
        if (Tinebase_Core::getUser()->hasGrant($_node, Tinebase_Model_Grants::GRANT_ADMIN, 'Tinebase_Model_Tree_Node')) {
            return true;
        }

        switch ($_action) {
            case 'get':
                $requiredGrant = Tinebase_Model_Grants::GRANT_READ;
                break;
            case 'add':
                $requiredGrant = Tinebase_Model_Grants::GRANT_ADD;
                break;
            case 'update':
                $requiredGrant = Tinebase_Model_Grants::GRANT_EDIT;
                break;
            case 'delete':
                $requiredGrant = Tinebase_Model_Grants::GRANT_DELETE;
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('Unknown action: ' . $_action);
        }

        $result = Tinebase_Core::getUser()->hasGrant($_node, $requiredGrant, 'Tinebase_Model_Tree_Node');
        if (true === $result && Tinebase_Model_Grants::GRANT_DELETE === $requiredGrant) {
            // check that we have the grant for all children too!
            $allChildIds = $this->getAllChildIds(array($_node->getId()));
            $deleteGrantChildIds = $this->getAllChildIds(array($_node->getId()), array(), false, $requiredGrant);
            if ($allChildIds != $deleteGrantChildIds) {
                $result = false;
            }
        }

        return $result;
    }


    /**************** container interface *******************/

    /**
     * check if the given user user has a certain grant
     *
     * @param   string|Tinebase_Model_User   $_accountId
     * @param   int|Tinebase_Record_Abstract $_containerId
     * @param   array|string                 $_grant
     * @return  boolean
     */
    public function hasGrant($_accountId, $_containerId, $_grant)
    {
        // always refetch node to have current acl_node value
        $node = $this->get($_containerId);
        /** @noinspection PhpUndefinedMethodInspection */
        $account = $_accountId instanceof Tinebase_Model_FullUser
            ? $_accountId
            : Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $_accountId, 'Tinebase_Model_FullUser');
        return $this->_nodeAclController->hasGrant($node, $_grant, $account);
    }

    /**
     * return users which made personal containers accessible to given account
     *
     * @param   string|Tinebase_Model_User        $_accountId
     * @param   string|Tinebase_Model_Application $_recordClass
     * @param   array|string                      $_grant
     * @param   bool                              $_ignoreACL
     * @param   bool                              $_andGrants
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_User
     */
    public function getOtherUsers($_accountId, $_recordClass, $_grant, $_ignoreACL = false, $_andGrants = false)
    {
        $result = $this->_getNodesOfType(self::FOLDER_TYPE_PERSONAL, $_accountId, $_recordClass, /* $_owner = */ null, $_grant, $_ignoreACL);
        return $result;
    }

    /**
     * returns the shared container for a given application accessible by the current user
     *
     * @param   string|Tinebase_Model_User        $_accountId
     * @param   string|Tinebase_Model_Application $_recordClass
     * @param   array|string                      $_grant
     * @param   bool                              $_ignoreACL
     * @param   bool                              $_andGrants
     * @return  Tinebase_Record_RecordSet set of Tinebase_Model_Container
     * @throws  Tinebase_Exception_NotFound
     */
    public function getSharedContainer($_accountId, $_recordClass, $_grant, $_ignoreACL = false, $_andGrants = false)
    {
        $result = $this->_getNodesOfType(self::FOLDER_TYPE_SHARED, $_accountId, $_recordClass, /* $_owner = */ null, $_grant, $_ignoreACL);
        return $result;
    }

    /**
     * @param            $_type
     * @param            $_accountId
     * @param            $_recordClass
     * @param null       $_owner
     * @param string     $_grant
     * @param bool|false $_ignoreACL
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Tinebase_Exception_SystemGeneric
     */
    protected function _getNodesOfType($_type, $_accountId, $_recordClass, $_owner = null, $_grant = Tinebase_Model_Grants::GRANT_READ, $_ignoreACL = false)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $appAndModel = Tinebase_Application::extractAppAndModel($_recordClass);
        $app = Tinebase_Application::getInstance()->getApplicationByName($appAndModel['appName']);
        $path = $this->getApplicationBasePath($app, $_type);

        if ($_type == self::FOLDER_TYPE_PERSONAL and $_owner == null) {
            return $this->_getOtherUsersNodes($_accountId, $path, $_grant, $_ignoreACL);

        } else {
            // SHARED or MY_FOLDERS

            $ownerId = $_owner instanceof Tinebase_Model_FullUser ? $_owner->getId() : $_owner;
            if ($ownerId) {
                $path .= '/' . $ownerId;
            }
            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($path);

            try {
                $parentNode = $this->stat($pathRecord->statpath);
                $filterArray = array(array('field' => 'parent_id', 'operator' => 'equals', 'value' => $parentNode->getId()));

                $filter = new Tinebase_Model_Tree_Node_Filter(
                    $filterArray,
                    /* $_condition = */ '',
                    /* $_options */ array(
                    'ignoreAcl' => $_ignoreACL,
                    'user' => $_accountId instanceof Tinebase_Record_Abstract
                        ? $_accountId->getId()
                        : $_accountId
                ));
                $filter->setRequiredGrants((array)$_grant);
                $result = $this->searchNodes($filter);

            } catch (Tinebase_Exception_NotFound $tenf) {
                if ($accountId === $ownerId) {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                        . ' Creating personal root node for user ' . $accountId);
                    $this->createAclNode($pathRecord->statpath);
                }

                return $result;
            }
        }

        foreach ($result as $node) {
            $node->path = $path . '/' . $node->name;
        }

        return $result;
    }


    protected function _getOtherUsersNodes($_accountId, $_path, $_grant, $_ignoreACL)
    {
        $result = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node');
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);

        // other users
        $accountIds = Tinebase_User::getInstance()->getUsers()->getArrayOfIds();
        // remove own id
        $accountIds = Tinebase_Helper::array_remove_by_value($accountId, $accountIds);
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($_path);
        try {
            $parentNode = $this->stat($pathRecord->statpath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Creating PERSONAL root node');
            $this->createAclNode($pathRecord->statpath);
            return $result;
        }
        $filter = new Tinebase_Model_Tree_Node_Filter(
            array(
                array('field' => 'name',      'operator' => 'in',     'value' => $accountIds),
                array('field' => 'parent_id', 'operator' => 'equals', 'value' => $parentNode->getId())
            ),
            /* $_condition = */ '',
            /* $_options */ array(
                'ignoreAcl' => true,
            )
        );
        $otherAccountNodes = $this->searchNodes($filter);
        $filter = new Tinebase_Model_Tree_Node_Filter(
            array(
                array('field' => 'parent_id', 'operator' => 'in', 'value' => $otherAccountNodes->getArrayOfIds()),
            ),
            /* $_condition = */ '',
            /* $_options */ array(
                'ignoreAcl' => $_ignoreACL,
                'user' => $_accountId instanceof Tinebase_Record_Abstract
                    ? $_accountId->getId()
                    : $_accountId
            )
        );
        $filter->setRequiredGrants((array)$_grant);
        // get shared folders of other users
        $sharedFoldersOfOtherUsers = $this->searchNodes($filter);

        foreach ($otherAccountNodes as $otherAccount) {
            if ($sharedFoldersOfOtherUsers->filter('parent_id', $otherAccount->getId())) {
                $result->addRecord($otherAccount);
                $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend(
                    'accountId',
                    $otherAccount->name,
                    'Tinebase_Model_FullUser'
                );
                $otherAccount->name = $account->accountDisplayName;
            }
        }

        return $result;
    }

    /**
     * returns the personal containers of a given account accessible by a another given account
     *
     * @param   string|Tinebase_Model_User       $_accountId
     * @param   string|Tinebase_Record_Interface $_recordClass
     * @param   int|Tinebase_Model_User          $_owner
     * @param   array|string                     $_grant
     * @param   bool                             $_ignoreACL
     * @return  Tinebase_Record_RecordSet of subtype Tinebase_Model_Tree_Node
     * @throws  Tinebase_Exception_NotFound
     */
    public function getPersonalContainer($_accountId, $_recordClass, $_owner, $_grant = Tinebase_Model_Grants::GRANT_READ, $_ignoreACL = false)
    {
        $result = $this->_getNodesOfType(self::FOLDER_TYPE_PERSONAL, $_accountId, $_recordClass, $_owner, $_grant, $_ignoreACL);

        // TODO generalize
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $ownerId = $_owner instanceof Tinebase_Model_FullUser ? $_owner->getId() : $_owner;
        $appAndModel = Tinebase_Application::extractAppAndModel($_recordClass);
        $app = Tinebase_Application::getInstance()->getApplicationByName($appAndModel['appName']);
        $path = $this->getApplicationBasePath($app, self::FOLDER_TYPE_PERSONAL);
        $path .= '/' . $ownerId;
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromPath($path);

        // no personal node found ... creating one?
        if (count($result) === 0 && $accountId === $ownerId) {
            /** @noinspection PhpUndefinedMethodInspection */
            $account = (!$_accountId instanceof Tinebase_Model_User)
                ? Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $_accountId)
                : $_accountId;

            $translation = Tinebase_Translation::getTranslation('Tinebase');
            $nodeName = sprintf($translation->_("%s's personal container"), $account->accountFullName);
            $nodeName = preg_replace('/\//', '', $nodeName);
            $path = $pathRecord->statpath . '/' . $nodeName;

            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Creating personal node with name ' . $nodeName);

            $personalNode = Tinebase_FileSystem::getInstance()->createAclNode($path);
            $result->addRecord($personalNode);
        }

        return $result;
    }

    /**
     * return all container, which the user has the requested right for
     *
     * used to get a list of all containers accesssible by the current user
     *
     * @param   string|Tinebase_Model_User $accountId
     * @param   string|Tinebase_Model_Application $recordClass
     * @param   array|string $grant
     * @param   bool $onlyIds return only ids
     * @param   bool $ignoreACL
     * @return array|Tinebase_Record_RecordSet
     * @throws Tinebase_Exception
     */
    public function getContainerByACL($accountId, $recordClass, $grant, $onlyIds = false, $ignoreACL = false)
    {
        throw new Tinebase_Exception('implement me');
    }

    /**
     * gets default container of given user for given app
     *  - did and still does return personal first container by using the application name instead of the recordClass name
     *  - allows now to use different models with default container in one application
     *
     * @param   string|Tinebase_Record_Interface $recordClass
     * @param   string|Tinebase_Model_User       $accountId use current user if omitted
     * @param   string                           $defaultContainerPreferenceName
     * @return  Tinebase_Record_Abstract
     */
    public function getDefaultContainer($recordClass, $accountId = null, $defaultContainerPreferenceName = null)
    {
        $account = Tinebase_Core::getUser();
        return $this->getPersonalContainer($account, $recordClass, $accountId ? $accountId : $account)->getFirstRecord();
    }

    /**
     * get grants assigned to one account of one container
     *
     * @param   string|Tinebase_Model_User          $_accountId
     * @param   int|Tinebase_Record_Abstract        $_containerId
     * @param   string                              $_grantModel
     * @return Tinebase_Model_Grants
     *
     * TODO add to interface
     */
    public function getGrantsOfAccount($_accountId, $_containerId, /** @noinspection PhpUnusedParameterInspection */ $_grantModel = 'Tinebase_Model_Grants')
    {
        $path = $this->getPathOfNode($_containerId, true);
        $accountId = Tinebase_Model_User::convertUserIdToInt($_accountId);
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($path);
        if ($pathRecord->isPersonalPath($_accountId)) {
            return new Tinebase_Model_Grants(array(
                'account_id' => $accountId,
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_ADD => true,
                Tinebase_Model_Grants::GRANT_EDIT => true,
                Tinebase_Model_Grants::GRANT_DELETE => true,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
                Tinebase_Model_Grants::GRANT_ADMIN => true,
            ));
        } else if ($pathRecord->isToplevelPath() && $pathRecord->containerType === Tinebase_FileSystem::FOLDER_TYPE_SHARED) {
            $account = $_accountId instanceof Tinebase_Model_FullUser
                ? $_accountId
                : Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $_accountId, 'Tinebase_Model_FullUser');
            $hasManageSharedRight = $account->hasRight($pathRecord->application->name, Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS);
            return new Tinebase_Model_Grants(array(
                'account_id' => $accountId,
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_ADD => $hasManageSharedRight,
                Tinebase_Model_Grants::GRANT_EDIT => $hasManageSharedRight,
                Tinebase_Model_Grants::GRANT_DELETE => $hasManageSharedRight,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
            ));
        } else if ($pathRecord->isToplevelPath() && $pathRecord->containerType === Tinebase_FileSystem::FOLDER_TYPE_PERSONAL) {
            // other users
            return new Tinebase_Model_Grants(array(
                'account_id' => $accountId,
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ => true,
            ));
        } else {
            return $this->_nodeAclController->getGrantsOfAccount($_accountId, $_containerId);
        }
    }


    /**
     * get all grants assigned to this container
     *
     * @param   int|Tinebase_Record_Abstract $_containerId
     * @param   bool                         $_ignoreAcl
     * @param   string                       $_grantModel
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     *
     * TODO add to interface
     */
    public function getGrantsOfContainer($_containerId, $_ignoreAcl = false, $_grantModel = 'Tinebase_Model_Grants')
    {
        $record = $_containerId instanceof Tinebase_Model_Tree_Node ? $_containerId : $this->get($_containerId);

        if (! $_ignoreAcl) {
            if (! Tinebase_Core::getUser()->hasGrant($record, Tinebase_Model_Grants::GRANT_READ)) {
                throw new Tinebase_Exception_AccessDenied('not allowed to read grants');
            }
        }

        return $this->_nodeAclController->getGrantsForRecord($record);
    }

    /**
     * remove file revisions based on settings:
     * Tinebase_Config::FILESYSTEM -> Tinebase_Config::FILESYSTEM_NUMKEEPREVISIONS
     * Tinebase_Config::FILESYSTEM -> Tinebase_Config::FILESYSTEM_MONTHKEEPREVISIONS
     * or folder specific settings
     */
    public function clearFileRevisions()
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting to clear file revisions');

        $config = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};
        $numRevisions = (int)$config->{Tinebase_Config::FILESYSTEM_NUMKEEPREVISIONS};
        $monthRevisions = (int)$config->{Tinebase_Config::FILESYSTEM_MONTHKEEPREVISIONS};
        $treeNodeBackend = $this->_getTreeNodeBackend();
        $parents = array();
        $count = 0;

        foreach ($treeNodeBackend->search(
                new Tinebase_Model_Tree_Node_Filter(array(
                    array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE)
                ), '', array('ignoreAcl' => true))
                , null, true) as $id) {
            try {
                /** @var Tinebase_Model_Tree_Node $fileNode */
                $fileNode = $treeNodeBackend->get($id, true);
                if (isset($parents[$fileNode->parent_id])) {
                    $parentXProps = $parents[$fileNode->parent_id];
                } else {
                    $parentNode = $treeNodeBackend->get($fileNode->parent_id, true);
                    $parentXProps = $parents[$fileNode->parent_id] = $parentNode->{Tinebase_Model_Tree_Node::XPROPS_REVISION};
                }

                if (!empty($parentXProps)) {
                    if (isset($parentXProps[Tinebase_Model_Tree_Node::XPROPS_REVISION_ON])
                        && false === $parentXProps[Tinebase_Model_Tree_Node::XPROPS_REVISION_ON]) {
                        $numRev = 1;
                        $monthRev = 0;
                    } else {
                        if (isset($parentXProps[Tinebase_Model_Tree_Node::XPROPS_REVISION_NUM])) {
                            $numRev = $parentXProps[Tinebase_Model_Tree_Node::XPROPS_REVISION_NUM];
                        } else {
                            $numRev = $numRevisions;
                        }
                        if (isset($parentXProps[Tinebase_Model_Tree_Node::XPROPS_REVISION_MONTH])) {
                            $monthRev = $parentXProps[Tinebase_Model_Tree_Node::XPROPS_REVISION_MONTH];
                        } else {
                            $monthRev = $monthRevisions;
                        }
                    }
                } else {
                    $numRev = $numRevisions;
                    $monthRev = $monthRevisions;
                }

                if ($numRev > 0) {
                    if (is_array($fileNode->available_revisions) && count($fileNode->available_revisions) > $numRev) {
                        $revisions = $fileNode->available_revisions;
                        sort($revisions, SORT_NUMERIC);
                        $count += $this->_fileObjectBackend->deleteRevisions($fileNode->object_id, array_slice($revisions, 0, count($revisions) - $numRevisions));
                    }
                }

                if (1 !== $numRev && $monthRev > 0) {
                    $count += $this->_fileObjectBackend->clearOldRevisions($fileNode->object_id, $monthRev);
                }

            } catch(Tinebase_Exception_NotFound $tenf) {}
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' cleared ' . $count . ' file revisions');
    }

    /**
     * create preview for files without a preview, delete previews for already deleted files
     */
    public function sanitizePreviews()
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting to sanitize previews');

        if (false === $this->_previewActive) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' previews are disabled');
            return true;
        }

        $treeNodeBackend = $this->_getTreeNodeBackend();
        $previewController = Tinebase_FileSystem_Previews::getInstance();
        $validHashes = array();
        $invalidHashes = array();
        $created = 0;
        $deleted = 0;
        $transactionManager = Tinebase_TransactionManager::getInstance();

        foreach($treeNodeBackend->search(
                new Tinebase_Model_Tree_Node_Filter(array(
                    array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE)
                ), '', array('ignoreAcl' => true))
                , null, true) as $id) {

            /** @var Tinebase_Model_Tree_Node $node */
            try {
                $treeNodeBackend->setRevision(null);
                $node = $treeNodeBackend->get($id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                continue;
            }

            foreach ($node->available_revisions as $revision) {
                if ($node->revision != $revision) {
                    $treeNodeBackend->setRevision($revision);
                    try {
                        $actualNode = $treeNodeBackend->get($id);
                        $treeNodeBackend->setRevision(null);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        continue;
                    }
                } else {
                    $actualNode = $node;
                }

                if ($previewController->hasPreviews($actualNode->hash)) {
                    $validHashes[$actualNode->hash] = true;
                    continue;
                }

                $previewController->createPreviews($actualNode->getId(), $actualNode->revision);
                $validHashes[$actualNode->hash] = true;
                ++$created;
            }
        }

        $treeNodeBackend->setRevision(null);


        $parents = array();
        foreach($treeNodeBackend->search(
                new Tinebase_Model_Tree_Node_Filter(array(
                    array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_PREVIEW)
                ), '', array('ignoreAcl' => true))
                , null, true) as $id) {
            /** @var Tinebase_Model_Tree_Node $fileNode */
            $fileNode = $treeNodeBackend->get($id, true);
            if (Tinebase_Model_Tree_FileObject::TYPE_PREVIEW !== $fileNode->type) {
                continue;
            }
            if (!isset($parents[$fileNode->parent_id])) {
                $parent = $treeNodeBackend->get($fileNode->parent_id);
                $parents[$fileNode->parent_id] = $parent;
            } else {
                $parent = $parents[$fileNode->parent_id];
            }
            $name = $parent->name;

            $parentId = $parent->parent_id;
            if (!isset($parents[$parentId])) {
                $parent = $treeNodeBackend->get($parentId);
                $parents[$parentId] = $parent;
            } else {
                $parent = $parents[$parentId];
            }

            $name = $parent->name . $name;

            if (!isset($validHashes[$name])) {
                $invalidHashes[] = $name;
            }
        }

        $validHashes = $this->_fileObjectBackend->checkRevisions($invalidHashes);
        $hashesToDelete = array_diff($invalidHashes, $validHashes);
        if (count($hashesToDelete) > 0) {
            $deleted = count($hashesToDelete);
            $previewController->deletePreviews($hashesToDelete);
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' created ' . $created . ' new previews, deleted ' . $deleted . ' previews.');

        return true;
    }

    /**
     * @param string $_hash
     * @param integer $_count
     */
    public function updatePreviewCount($_hash, $_count)
    {
        $this->_fileObjectBackend->updatePreviewCount($_hash, $_count);
    }

    /**
     * check the folder tree up to the root for notification settings and either send notification immediately
     * or create alarm for it to be send in the future. Alarms aggregate!
     *
     * @param string $_fileNodeId
     * @param string $_crudAction
     * @return boolean
     */
    public function checkForCRUDNotifications($_fileNodeId, $_crudAction)
    {
        $nodeId = $_fileNodeId;
        $foundUsers = array();
        $foundGroups = array();
        $alarmController = Tinebase_Alarm::getInstance();

        do {
            $node = $this->get($nodeId);
            $notificationProps = $node->xprops(Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION);

            if (count($notificationProps) === 0) {
                continue;
            }

            //sort it to handle user settings first, then group settings!
            //TODO write a test that tests this!
            usort($notificationProps, function($a) {
                if (is_array($a) && isset($a[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE]) &&
                    $a[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE] === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                    return false;
                }
                return true;
            });

            foreach($notificationProps as $notificationProp) {

                $notifyUsers = array();

                if (!isset($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]) ||
                        !isset($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE])) {
                    // LOG broken notification setting
                    continue;
                }

                if ($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE] === Tinebase_Acl_Rights::ACCOUNT_TYPE_USER) {
                    if (isset($foundUsers[$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]])) {
                        continue;
                    }

                    $foundUsers[$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]] = true;
                    if (isset($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE]) && false === (bool)$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE]) {
                        continue;
                    }

                    $notifyUsers[] = $notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID];

                } elseif ($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_TYPE] === Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP) {
                    if (isset($foundGroups[$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]])) {
                        continue;
                    }

                    $foundGroups[$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]] = true;

                    $doNotify = !isset($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE]) ||
                        true === (bool)$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACTIVE];

                    // resolve Group Members
                    foreach (Tinebase_Group::getInstance()->getGroupMembers($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_ACCOUNT_ID]) as $userId) {
                        if (true === $doNotify && !isset($foundUsers[$userId])) {
                            $notifyUsers[] = $userId;
                        }
                        $foundUsers[$userId] = true;
                    }

                    if (false === $doNotify) {
                        continue;
                    }
                } else {
                    // LOG broken notification setting
                    continue;
                }

                if (isset($notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_SUMMARY]) && (int)$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_SUMMARY] > 0) {

                    foreach ($notifyUsers as $accountId) {
                        $crudNotificationHash = $this->_getCRUDNotificationHash($accountId, $nodeId);
                        $alarms = $alarmController->search(new Tinebase_Model_AlarmFilter(array(
                            array(
                                'field'     => 'model',
                                'operator'  => 'equals',
                                'value'     => 'Tinebase_FOOO_FileSystem'
                            ),
                            array(
                                'field'     => 'record_id',
                                'operator'  => 'equals',
                                'value'     => $crudNotificationHash
                            ),
                            array(
                                'field'     => 'sent_status',
                                'operator'  => 'equals',
                                'value'     => Tinebase_Model_Alarm::STATUS_PENDING
                            )
                        )));

                        if ($alarms->count() > 0) {
                            /** @var Tinebase_Model_Alarm $alarm */
                            $alarm = $alarms->getFirstRecord();
                            $options = json_decode($alarm->options, true);
                            if (null === $options) {
                                // broken! not good
                                $options = array();
                            }
                            if (!isset($options['files'])) {
                                // broken! not good
                                $options['files'] = array();
                            }
                            if (!isset($options['files'][$_fileNodeId])) {
                                $options['files'][$_fileNodeId] = array();
                            }
                            $options['files'][$_fileNodeId][$_crudAction] = true;
                            $alarm->options = json_encode($options);
                            $alarmController->update($alarm);
                        } else {
                            $alarm = new Tinebase_Model_Alarm(array(
                                'record_id'     => $crudNotificationHash,
                                'model'         => 'Tinebase_FOOO_FileSystem',
                                'minutes_before'=> 0,
                                'alarm_time'    => Tinebase_DateTime::now()->addDay((int)$notificationProp[Tinebase_Model_Tree_Node::XPROPS_NOTIFICATION_SUMMARY]),
                                'options'       => array(
                                    'files'     => array($_fileNodeId => array($_crudAction => true)),
                                    'accountId' => $accountId
                                ),
                            ));
                            $alarmController->create($alarm);
                        }
                    }
                } else {
                    $this->sendCRUDNotification($notifyUsers, array($_fileNodeId => array($_crudAction => true)));
                }
            }

        } while(null !== ($nodeId = $node->parent_id));

        return true;
    }

    protected function _getCRUDNotificationHash($_accountId, $_nodeId)
    {
        return md5($_accountId . '_' . $_nodeId);
    }

    public function sendCRUDNotification(array $_accountIds, array $_crudActions)
    {
        $fileSystem = Tinebase_FileSystem::getInstance();

        foreach($_accountIds as $accountId) {
            //$locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $accountId));
            //$translate = Tinebase_Translation::getTranslation('Filemanager', $locale);

            try {
                $user = Tinebase_User::getInstance()->getFullUserById($accountId);
            } catch(Tinebase_Exception_NotFound $tenf) {
                continue;
            }

            $messageBody = '<html><body>';
            foreach($_crudActions as $fileNodeId => $changes) {

                try {
                    $fileNode = $fileSystem->get($fileNodeId, true);
                    $path = explode('/', ltrim($fileSystem->getPathOfNode($fileNode, true), '/'));
                    array_walk($path, function(&$val) {
                        $val = urldecode($val);
                    });
                    $path = '/' . join('/', $path);
                } catch(Tinebase_Exception_NotFound $tenf) {
                    continue;
                }

                $messageBody .= '<p>';

                foreach ($changes as $change => $foo) {
                    switch($change) {
                        case 'created':
                            $messageBody .= 'File <a href="http://tine20.vagrant/#/Filemanager' . $path . '">' . $fileNode->name . '</a> has been created.<br/>';
                            break;
                        case 'updated':
                            $messageBody .= 'File <a href="http://tine20.vagrant/#/Filemanager' . $path . '">' . $fileNode->name . '</a> has been changed.<br/>';
                            break;
                        case 'deleted':
                            $messageBody .= 'File <a href="http://tine20.vagrant/#/Filemanager' . $path . '">' . $fileNode->name . '</a> has been deleted.<br/>';
                            break;
                        default:
                            // should not happen!
                    }
                }

                $messageBody .= '</p>';
            }
            $messageBody .= '</body></html>';

            Tinebase_Notification::getInstance()->send($accountId, $user->contact_id, 'filemanager notification', '', $messageBody);
        }
    }

    /**
     * sendAlarm - send an alarm and update alarm status/sent_time/...
     *
     * @param  Tinebase_Model_Alarm $_alarm
     * @return Tinebase_Model_Alarm
     */
    public function sendAlarm(Tinebase_Model_Alarm $_alarm)
    {
        $options = json_decode($_alarm->options, true);
        do {
            if (null === $options) {
                // broken! not good
                break;
            }
            if (!isset($options['files'])) {
                // broken! not good
                break;
            }
            if (!isset($options['accountId'])) {
                // broken! not good
                break;
            }

            $this->sendCRUDNotification((array)$options['accountId'], $options['files']);
        } while (false);

        return $_alarm;
    }
}
