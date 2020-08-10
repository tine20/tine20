<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2020 Metaways Infosystems GmbH (http://www.metaways.de)
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

    protected $_previewActive = null;

    protected $_streamOptionsForNextOperation = array();

    protected $_notificationActive = false;

    /**
     * stat cache
     *
     * @var array
     */
    protected $_statCache = array();
    protected $_statCacheById = [];

    /**
     * class cache to remember secondFactor check per node id. This is required as the "state" might change
     * during an update for example. Node is not pin protected, gets updated, becomes pin protected -> the final get
     * at the end of the update process fails -> pin protection will only be checked once, the first time in a request
     *
     * @var array
     */
    protected $_areaLockCache = array();

    /**
     * class cache to remember all members of the notification role
     *
     * @var array
     */
    protected $_quotaNotificationRoleMembers = array();

    /**
     * @var Tinebase_Backend_Sql
     */
    protected static $_refLogBackend = null;
    protected static $_refLogs = [];
    protected static $_refLogsFlushed = false;

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
        }

        $this->_fileObjectBackend = new Tinebase_Tree_FileObject(null, array(
            Tinebase_Config::FILESYSTEM_MODLOGACTIVE => $this->_modLogActive
        ));

        $this->_nodeAclController = Tinebase_Tree_NodeGrants::getInstance();

        if (null === static::$_refLogBackend) {
            static::$_refLogBackend = new Tinebase_Backend_Sql([
                Tinebase_Backend_Sql_Abstract::MODEL_NAME => Tinebase_Model_Tree_RefLog::class,
                Tinebase_Backend_Sql_Abstract::TABLE_NAME => Tinebase_Model_Tree_RefLog::TABLE_NAME,
            ]);
        }
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
        $config = Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};
        $this->_modLogActive = true === $config->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE};
        $this->_indexingActive = true === $config->{Tinebase_Config::FILESYSTEM_INDEX_CONTENT};
        $this->_notificationActive = true === $config->{Tinebase_Config::FILESYSTEM_ENABLE_NOTIFICATIONS};
        $this->_previewActive = null;

        $this->_treeNodeBackend = null;

        $this->_fileObjectBackend = new Tinebase_Tree_FileObject(null, array(
            Tinebase_Config::FILESYSTEM_MODLOGACTIVE => $this->_modLogActive
        ));
    }

    /**
     * @return Tinebase_Tree_FileObject
     */
    public function getFileObjectBackend()
    {
        return $this->_fileObjectBackend;
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
            if (null !== $_revision && (int)$node->revision !== (int)$_revision) {
                throw new Tinebase_Exception_NotFound(Tinebase_Model_Tree_Node::class . ' ' . $_id . ' revision: ' .
                    $_revision . ' couldn\'t be found');
            }
        } finally {
            if (null !== $_revision) {
                $treeBackend->setRevision(null);
            }
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
        }

        return $node;
    }

    public function _getTreeNodeBackend()
    {
        if ($this->_treeNodeBackend === null) {
            $this->_treeNodeBackend    = new Tinebase_Tree_Node(null, /* options */ array(
                'modelName' => $this->_treeNodeModel,
                Tinebase_Config::FILESYSTEM_ENABLE_NOTIFICATIONS => $this->_notificationActive,
                Tinebase_Config::FILESYSTEM_MODLOGACTIVE => $this->_modLogActive,
            ));
        }

        return $this->_treeNodeBackend;
    }

    public function repairAclOfNode($nodeId, $aclNode)
    {
        $node = $this->get($nodeId, true);
        $this->_recursiveInheritPropertyUpdate($node, 'acl_node', $aclNode, $node->acl_node, true, true);
        $node->acl_node = $aclNode;
        $this->_getTreeNodeBackend()->update($node);
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
                $node->grants = $this->getDefaultGrantsForContainerType($pathRecord);
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
     * returns the default grants for the container type of the given path
     *
     * @param Tinebase_Model_Tree_Node_Path $pathRecord
     * @return null|Tinebase_Record_RecordSet
     */
    protected function getDefaultGrantsForContainerType(Tinebase_Model_Tree_Node_Path $pathRecord)
    {
        switch ($pathRecord->containerType) {
            case self::FOLDER_TYPE_PERSONAL:
                return Tinebase_Model_Grants::getPersonalGrants($pathRecord->getUser(), array(
                    Tinebase_Model_Grants::GRANT_DOWNLOAD => true,
                    Tinebase_Model_Grants::GRANT_PUBLISH => true,
                ));
            case self::FOLDER_TYPE_SHARED:
                return Tinebase_Model_Grants::getDefaultGrants(array(
                    Tinebase_Model_Grants::GRANT_DOWNLOAD => true
                ), array(
                    Tinebase_Model_Grants::GRANT_PUBLISH => true
                ));
        }
        return null;
    }

    /**
     * set grants for node
     *
     * @param Tinebase_Model_Tree_Node $node
     * @param Tinebase_Record_RecordSet $grants
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
            $this->_nodeAclController->deleteGrantsOfRecord($node->getId());

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
     * @throws Tinebase_Exception
     */
    public function getNodeContents($nodeId)
    {
        // getPathOfNode uses transactions and fills the stat cache, so fopen should fetch the node from the stat cache
        // we do not start a transaction here
        $path = $this->getPathOfNode($nodeId, /* $getPathAsString */ true);
        $handle = $this->fopen($path, 'r');
        if (! $handle) {
            throw new Tinebase_Exception('Could not get contents of path ' . $path);
        }
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
            $cacheId = $this->_getCacheId($path);
            if (isset($this->_statCache[$cacheId])) {
                unset($this->_statCacheById[$this->_statCache[$cacheId]->getId()]);
                unset($this->_statCache[$cacheId]);
            }
        } else {
            // clear the whole cache
            $this->_statCache = [];
            $this->_statCacheById = [];
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
        $this->acquireWriteLock();

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

            if (null !== ($deletedNode = $this->_getTreeNodeBackend()
                    ->getChild($parentNode, $destinationNodeName, true, false)) && $deletedNode->is_deleted) {
                $this->_updateDeletedNodeName($deletedNode);
            }

            if ($destinationNode->type === Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                $createdNode = $this->createFileTreeNode($parentNode->getId(), $destinationNodeName);
                $this->_updateFileObject($parentNode, $createdNode, null, $destinationNode->hash);
                $createdNode = $this->get($createdNode->getId());
            } else {
                $createdNode = $this->_createDirectoryTreeNode($parentNode->getId(), $destinationNodeName);
            }

            // update hash of all parent folders
            $this->_checkQuotaAndRegisterRefLog($createdNode, (int)$createdNode->size, (int)$createdNode->revision_size);

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
            case 'a+':
            case 'w':
            case 'wb':
            case 'x':
            case 'xb':
                $parentPath = dirname($options['tine20']['path']);

                list ($hash, $hashFile, $avResult) = $this->createFileBlob($handle);

                try {
                    $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
                    $this->acquireWriteLock();

                    $parentFolder = $this->stat($parentPath);

                    $this->_updateFileObject($parentFolder, $options['tine20']['node'], null, $hash, $hashFile, $avResult);

                    $this->clearStatCache($options['tine20']['path']);

                    $newNode = $this->stat($options['tine20']['path']);

                    // write modlog and system notes
                    $this->_getTreeNodeBackend()->updated($newNode, $options['tine20']['node']);

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
     * @param Tinebase_FileSystem_AVScan_Result $_avResult
     * @return Tinebase_Model_Tree_FileObject
     */
    protected function _updateFileObject(Tinebase_Model_Tree_Node $_parentNode, Tinebase_Model_Tree_Node $_node, Tinebase_Model_Tree_FileObject $_fileObject = null, $_hash = null, $_hashFile = null, $_avResult = null)
    {
        /** @var Tinebase_Model_Tree_FileObject $currentFileObject */
        $currentFileObject = $_fileObject ?: $this->_fileObjectBackend->get($_node->object_id);

        if (! $_hash) {
            // use existing hash from file object
            $_hash = $currentFileObject->hash;
        }
        $_hashFile = $_hashFile ?: ($this->getRealPathForHash($_hash));
        
        $updatedFileObject = clone($currentFileObject);
        $updatedFileObject->hash = $_hash;

        if ($updatedFileObject->hash !== $currentFileObject->hash) {
            $updatedFileObject->is_quarantined = false;
        }

        if (null !== $_avResult) {
            if (Tinebase_FileSystem_AVScan_Result::RESULT_FOUND === $_avResult->result) {
                $updatedFileObject->lastavscan_time = Tinebase_DateTime::now();
                $updatedFileObject->is_quarantined = true;
            } elseif (Tinebase_FileSystem_AVScan_Result::RESULT_OK === $_avResult->result) {
                $updatedFileObject->lastavscan_time = Tinebase_DateTime::now();
                $updatedFileObject->is_quarantined = false;
            }
        }

        if (is_file($_hashFile)) {
            $updatedFileObject->size = filesize($_hashFile);

            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $_hashFile);
                if ($mimeType !== false) {
                    if (PHP_VERSION_ID >= 70300 && ($mimeLen = strlen($mimeType)) % 2 === 0 &&
                            substr($mimeType, 0, $mimeLen / 2) === substr($mimeType, $mimeLen / 2)) {
                        $mimeType = substr($mimeType, 0, $mimeLen / 2);
                    }
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
        $revisionSizeDiff = ((int)$newFileObject->revision_size) - ((int)$currentFileObject->revision_size);

        if ($sizeDiff !== 0 || $revisionSizeDiff !== 0 || $currentFileObject->hash !== $newFileObject->hash) {
            // update parents with new sizes if anything changed
            $this->_checkQuotaAndRegisterRefLog($_node, $sizeDiff, $revisionSizeDiff);
        }

        if (true === Tinebase_Config::getInstance()->get(Tinebase_Config::FILESYSTEM)->{Tinebase_Config::FILESYSTEM_INDEX_CONTENT}) {
            Tinebase_ActionQueueLongRun::getInstance()->queueAction('Tinebase_FOO_FileSystem.indexFileObject', $newFileObject->getId());
        }

        return $newFileObject;
    }

    public function getEffectiveAndLocalQuota(Tinebase_Model_Tree_Node $node)
    {
        $quotaConfig = Tinebase_FileSystem_Quota::getConfig();
        $total = Tinebase_FileSystem_Quota::getRootQuotaBytes();
        $effectiveQuota = $total;
        $localQuota = null !== $node->quota ? (int)$node->quota : null;
        if ($quotaConfig->{Tinebase_Config::QUOTA_INCLUDE_REVISION}) {
            $localSize = $node->size;
        } else {
            $localSize = $node->revision_size;
        }
        $totalByUser = Tinebase_FileSystem_Quota::getPersonalQuotaBytes();
        $personalNode = null;
        if (Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            $personalNode = $this->stat('/Filemanager/folders/personal');
        }

        /** @var Tinebase_Model_Application $tinebaseApplication */
        $tinebaseApplication = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');

        $totalUsage = Tinebase_FileSystem_Quota::getRootUsedBytes();
        $effectiveUsage = $totalUsage;


        $effectiveFree = $effectiveQuota > 0 ? $effectiveQuota - $effectiveUsage : null;

        foreach ($this->_getTreeNodeBackend()->getAllFolderNodes(new Tinebase_Record_RecordSet(
                'Tinebase_Model_Tree_Node', array($node))) as $parentNode) {
            if ($quotaConfig->{Tinebase_Config::QUOTA_INCLUDE_REVISION}) {
                $size = $parentNode->size;
            } else {
                $size = $parentNode->revision_size;
            }

            // folder quota
            if (null !== $parentNode->quota && 0 !== (int)$parentNode->quota) {
                if (null === $localQuota) {
                    $localQuota = $parentNode->quota;
                    $localSize = $size;
                }
                $free = $parentNode->quota - $size;
                if (null === $effectiveFree || $free < $effectiveFree) {
                    $effectiveFree = $free;
                    $effectiveQuota = $parentNode->quota;
                    $effectiveUsage = $size;
                }
            }

            //personal quota
            if (null !== $personalNode && $parentNode->parent_id === $personalNode->getId()) {
                $user = Tinebase_User::getInstance()->getFullUserById($parentNode->name);
                $quota = isset(
                    $user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA]) ?
                    $user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] :
                    $totalByUser;
                if ($quota > 0) {
                    if (null === $localQuota) {
                        $localQuota = $quota;
                        $localSize = $size;
                    }
                    $free = $quota - $size;
                    if (null === $effectiveFree || $free < $effectiveFree) {
                        $effectiveFree = $free;
                        $effectiveQuota = $quota;
                        $effectiveUsage = $size;
                    }
                }
            }
        }

        if (null === $localQuota && $total > 0) {
            $localQuota = $total;
            $localSize = $totalUsage;
        }

        return array(
            'localQuota'        => $localQuota,
            'localUsage'        => $localSize,
            'localFree'         => $localQuota !== null && $localQuota > $localSize ? $localQuota - $localSize : 0,
            'effectiveQuota'    => $effectiveQuota,
            'effectiveUsage'    => $effectiveUsage,
            'effectiveFree'     => $effectiveFree < 0 ? 0 : $effectiveFree,
        );
    }

    public function processRefLogs()
    {
        $lock = Tinebase_Core::getMultiServerLock(__METHOD__);
        $lock->tryAcquire(30); // if acquire fails, lets run anyway to avoid unprocessed data in an edge case
        $lockRaii = new Tinebase_RAII(function() /*use($lock)*/ {
            // a bit bad ... for unittests...
            //if ($lock->isLocked()) $lock->release();
            Tinebase_Lock::clearLocks();
        });

        $refLogBackend = static::$_refLogBackend;
        $refLogIds = $refLogBackend->search(null, new Tinebase_Model_Pagination([
            'limit'     => 1000,
            'sort'      => 'id'
        ]), Tinebase_Backend_Sql_Abstract::IDCOL);
        if (empty($refLogIds)) {
            return;
        }

        $db = Tinebase_Core::getDb();
        $transMgr = Tinebase_TransactionManager::getInstance();
        $transId = $transMgr->startTransaction($db);
        try {
            $applicationController = Tinebase_Application::getInstance();
            /** @var Tinebase_Model_Application $tinebaseApplication */
            $tinebaseApplication = $applicationController->getApplicationByName('Tinebase');
            $rootSize = (int)$applicationController->getApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE, true);
            $rootRevisionSize = (int)$applicationController->getApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE, true);

            $refLogBackend->addSelectHook(function(Zend_Db_Select $select) {
                $select->forUpdate(true);
            });
            $raii = new Tinebase_RAII(function() use($refLogBackend) {
                $refLogBackend->resetSelectHooks();
            });
            $refLogs = $refLogBackend->getMultiple($refLogIds);
            if (!$refLogs->count()) {
                $transMgr->commitTransaction($transId);
                $transId = null;
                return;
            }
            $refLogIds = $refLogs->getArrayOfIds();
            unset($raii);

            $treeBackend = $this->_getTreeNodeBackend();
            $treeBackend->addSelectHook(function(Zend_Db_Select $select) {
                $select->forUpdate(true);
            });
            $raii = new Tinebase_RAII(function() use($treeBackend) {
                $treeBackend->resetSelectHooks();
            });

            $folders = $allFolders = $treeBackend->getMultiple($refLogs->{Tinebase_Model_Tree_RefLog::FLD_FOLDER_ID});
            while (!empty($parentIds = array_filter($folders->parent_id, function($val) use($allFolders) {
                        return $val && $allFolders->getIndexById($val) === false;
                    }))) {
                $folders = $treeBackend->getMultiple($parentIds);
                $allFolders->mergeById($folders);
            }
            unset($raii);

            while ($refLogs->count()) {
                foreach ($refLogs as $refLog) {
                    if (false === ($folder = $allFolders->getById($refLog->{Tinebase_Model_Tree_RefLog::FLD_FOLDER_ID}))) {
                        $refLogs->removeById($refLog->getId());
                        continue;
                    }
                    $folder->size += $refLog->{Tinebase_Model_Tree_RefLog::FLD_SIZE_DELTA};
                    $folder->revision_size += $refLog->{Tinebase_Model_Tree_RefLog::FLD_SIZE_DELTA};
                    if ($folder->parent_id) {
                        $refLog->{Tinebase_Model_Tree_RefLog::FLD_FOLDER_ID} = $folder->parent_id;
                    } else {
                        $rootSize += $refLog->{Tinebase_Model_Tree_RefLog::FLD_SIZE_DELTA};
                        $rootRevisionSize += $refLog->{Tinebase_Model_Tree_RefLog::FLD_SIZE_DELTA};
                        $refLogs->removeById($refLog->getId());
                    }
                }
            }

            if ($rootSize < 0) $rootSize = 0;
            if ($rootRevisionSize) $rootRevisionSize = 0;
            $applicationController->setApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE, $rootSize);
            $applicationController->setApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE, $rootRevisionSize);

            $newHash = Tinebase_Record_Abstract::generateUID();
            $now = new Zend_Db_Expr('NOW()');
            /** @var Tinebase_Model_Tree_Node $folder */
            foreach ($allFolders as $folder) {
                if ($folder->size < 0) {
                    $folder->size = 0;
                }
                if ($folder->revision_size < 0) {
                    $folder->revision_size = 0;
                }

                $where = $db->quoteInto('id = ?', $folder->object_id);
                $db->update(SQL_TABLE_PREFIX . 'tree_filerevisions', ['hash' => $newHash, 'size' => $folder->size],
                    $where);
                $db->update(SQL_TABLE_PREFIX . 'tree_fileobjects', [
                    'revision_size' => $folder->revision_size,
                    'last_modified_time' => $now], $where);
            }

            static::$_refLogBackend->delete($refLogIds);

            $transMgr->commitTransaction($transId);
            $transId = null;
        } finally {
            if (null !== $transId) {
                $transMgr->rollBack();
            }
        }

        // only for unused variable check
        unset($lockRaii);
    }

    public static function insertRefLogActionQueue()
    {
        if (true === static::$_refLogsFlushed) {
            Tinebase_ActionQueue::getInstance()->queueAction('Tinebase_FOO_FileSystem.processRefLogs');
            static::$_refLogsFlushed = false;
        }
    }

    public static function flushRefLogs()
    {
        foreach (static::$_refLogs as $refLog) {
            static::$_refLogBackend->create($refLog);
        }
        static::$_refLogs = [];
        static::$_refLogsFlushed = true;
    }

    /**
     * expectes nodes path to be resolved!
     *
     * @param Tinebase_Model_Tree_Node $_node
     * @param int $_sizeDiff
     * @param int $_revisionSizeDiff
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected function _checkQuotaAndRegisterRefLog(Tinebase_Model_Tree_Node $_node, int $_sizeDiff, int $_revisionSizeDiff)
    {
        $transactionMgr = Tinebase_TransactionManager::getInstance();
        if (!$transactionMgr->hasOpenTransactions()) {
            throw new Tinebase_Exception('this function requires an open transaction!');
        }

        $applicationController = Tinebase_Application::getInstance();
        /** @var Tinebase_Model_Application $tinebaseApplication */
        $tinebaseApplication = $applicationController->getApplicationByName('Tinebase');
        if (empty($_node->parent_id)) {
            $rootSize = (int)$applicationController->getApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE, true) + $_sizeDiff;
            $rootRevisionSize = (int)$applicationController->getApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE, true) + $_revisionSizeDiff;
            if ($rootSize < 0) $rootSize = 0;
            if ($rootRevisionSize < 0) $rootRevisionSize = 0;
            $applicationController->setApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE, $rootRevisionSize);
            $applicationController->setApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE, $rootSize);

            return;
        }

        $path = $this->getPathOfNode($_node, true, true);

        $transactionMgr->registerOnCommitCallback([self::class, 'flushRefLogs']);
        $transactionMgr->registerAfterCommitCallback([self::class, 'insertRefLogActionQueue']);

        if (isset(static::$_refLogs[$_node->parent_id])) {
            static::$_refLogs[$_node->parent_id]->{Tinebase_Model_Tree_RefLog::FLD_SIZE_DELTA} += $_sizeDiff;
            static::$_refLogs[$_node->parent_id]->{Tinebase_Model_Tree_RefLog::FLD_REVISION_SIZE_DELTA}
                += $_revisionSizeDiff;
        } else {
            static::$_refLogs[$_node->parent_id] = new Tinebase_Model_Tree_RefLog([
                Tinebase_Model_Tree_RefLog::FLD_FOLDER_ID           => $_node->parent_id,
                Tinebase_Model_Tree_RefLog::FLD_SIZE_DELTA          => $_sizeDiff,
                Tinebase_Model_Tree_RefLog::FLD_REVISION_SIZE_DELTA => $_revisionSizeDiff,
            ]);
        }

        if (0 === $_sizeDiff && 0 === $_revisionSizeDiff) return;

        // check quota
        $quotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};

        if ($quotaConfig->{Tinebase_Config::QUOTA_INCLUDE_REVISION} && $_revisionSizeDiff > 0) {
            $sizeIncrease = true;
        } elseif ($_sizeDiff > 0) {
            $sizeIncrease = true;
        } else {
            $sizeIncrease = false;
        }

        if (null === ($rootSize = $applicationController->getApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE))) {
            $rootSize = 0;
        }
        if (null === ($rootRevisionSize = $applicationController->getApplicationState($tinebaseApplication,
                Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE))) {
            $rootRevisionSize = 0;
        }

        $rootSize += $_sizeDiff;
        $rootRevisionSize += $_revisionSizeDiff;

        if ($rootSize < 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' root size should not become smaller than 0: ' . $rootSize);
            $rootSize = 0;
        }
        if ($rootRevisionSize < 0) {
            if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' root revision size should not become smaller than 0: ' . $rootRevisionSize);
            $rootRevisionSize = 0;
        }

        if ($sizeIncrease && $quotaConfig->{Tinebase_Config::QUOTA_TOTALINMB} > 0) {
            $total = $quotaConfig->{Tinebase_Config::QUOTA_TOTALINMB} * 1024 * 1024;
            if ($quotaConfig->{Tinebase_Config::QUOTA_INCLUDE_REVISION}) {
                if ($rootRevisionSize > $total) {
                    throw new Tinebase_Exception_Record_NotAllowed('quota exceeded');
                }
            } else {
                if ($rootSize > $total) {
                    throw new Tinebase_Exception_Record_NotAllowed('quota exceeded');
                }
            }
        }

        $userController = Tinebase_User::getInstance();
        $totalByUser = $quotaConfig->{Tinebase_Config::QUOTA_TOTALBYUSERINMB} * 1024 * 1024;
        $personalNode = null;
        if ($applicationController->isInstalled('Filemanager')) {
            try {
                $personalNode = $this->stat('/Filemanager/folders/personal');
            } catch (Tinebase_Exception_NotFound $tenf) {
                $this->initializeApplication('Filemanager');
                $personalNode = $this->stat('/Filemanager/folders/personal');
            }
        }
        
        while (($path = dirname($path)) !== DIRECTORY_SEPARATOR) {
            $parentNode = $this->stat($path);
            $parentNode->size += $_sizeDiff;
            $parentNode->revision_size += $_revisionSizeDiff;

            if (!$sizeIncrease) continue;

            if ($quotaConfig->{Tinebase_Config::QUOTA_INCLUDE_REVISION}) {
                $size = $parentNode->size;
            } else {
                $size = $parentNode->revision_size;
            }

            // folder quota
            if (null !== $parentNode->quota && $size > $parentNode->quota) {
                throw new Tinebase_Exception_Record_NotAllowed('quota exceeded');
            }

            //personal quota
            if (null !== $personalNode && $parentNode->parent_id === $personalNode->getId()) {
                $user = $userController->getFullUserById($parentNode->name);
                $quota = isset(
                    $user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA]) ?
                    $user->xprops()[Tinebase_Model_FullUser::XPROP_PERSONAL_FS_QUOTA] :
                    $totalByUser;
                if ($quota > 0 && $size > $quota) {
                    throw new Tinebase_Exception_Record_NotAllowed('quota exceeded');
                }
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
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
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
                $this->_fileObjectBackend->addSelectHook(function(Zend_Db_Select $select) {$select->forUpdate(true);});
                $fileObject = $this->_fileObjectBackend->get($_objectId);
            } catch(Tinebase_Exception_NotFound $tenf) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                    . ' Could not find file object ' . $_objectId);
                return true;
            } finally {
                $this->_fileObjectBackend->resetSelectHooks();
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
            $transactionId = null;

        } catch (Exception $e) {
            $transactionId = null;
            Tinebase_Exception::log($e);
            Tinebase_TransactionManager::getInstance()->rollBack();

            return false;

        } finally {
            unlink($tmpFile);

            // in case of the return trues above
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            }
        }

        return true;
    }

    public function acquireWriteLock()
    {
        Tinebase_Application::getInstance()->getApplicationState('Tinebase',
            Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE, true);
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


                // this would need some StreamWrapper tweaking for fseek
                // StremWrapper needs optimization anyway, it should store the mode itself not ask the context...
                case 'a+':
                    if (!$this->isDir($dirName)) {
                        $rollBack = false;
                        return false;
                    }
                    $handle = fopen('php://temp', 'a+');

                    if (!$this->fileExists($_path)) {
                        $parent = $this->stat($dirName);
                        $node = $this->createFileTreeNode($parent, $fileName, $fileType);
                    } else {
                        $node = $this->stat($_path);
                        $hashFile = $this->getRealPathForHash($node->hash);
                        stream_copy_to_stream(($tmpFh = fopen($hashFile, 'r')), $handle);
                        fclose($tmpFh);
                    }
                    break;

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
                    if (! file_exists($hashFile)) {
                        return false;
                    }
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

        if (is_resource($handle)) {
            $contextOptions = array('tine20' => array(
                'path' => $_path,
                'mode' => $_mode,
                'node' => $node
            ));
            stream_context_set_option($handle, $contextOptions);
        }
        
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
            $this->acquireWriteLock();

            try {
                $node = $this->stat($oldPath);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                return false;
            } catch (Tinebase_Exception_NotFound $tenf) {
                return false;
            }

            $oldParentPath = dirname($oldPath);
            $newParentPath = dirname($newPath);
            try {
                $newParent = $this->stat($newParentPath);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                return false;
            } catch (Tinebase_Exception_NotFound $tenf) {
                return false;
            }

            if ($oldParentPath !== $newParentPath) {
                try {
                    $oldParent = $this->stat($oldParentPath);
                } catch (Tinebase_Exception_InvalidArgument $teia) {
                    return false;
                } catch (Tinebase_Exception_NotFound $tenf) {
                    return false;
                }

                if (null === $node->acl_node || ($node->acl_node === $oldParent->acl_node &&
                        $newParent->acl_node !== $node->acl_node)) {
                    $node->acl_node = $newParent->acl_node;
                    if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $node->type) {
                        if (null === $node->acl_node) {
                            $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($newPath);
                            switch ($pathRecord->containerType) {
                                case self::FOLDER_TYPE_PERSONAL:
                                case self::FOLDER_TYPE_SHARED:
                                    $node->grants = $this->getDefaultGrantsForContainerType($pathRecord);
                                    $this->_nodeAclController->setGrants($node);
                                    $node->acl_node = $node->getId();
                            }
                        }
                        $this->_recursiveInheritPropertyUpdate($node, 'acl_node', $newParent->acl_node, $oldParent->acl_node, true, true);
                    }
                }
                if ($node->pin_protected_node === $oldParent->pin_protected_node
                        && $newParent->pin_protected_node !== $node->pin_protected_node) {
                    $node->pin_protected_node = $newParent->pin_protected_node;
                    if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $node->type) {
                        $this->_recursiveInheritPropertyUpdate($node, 'pin_protected_node',
                            $newParent->pin_protected_node, $oldParent->pin_protected_node);
                    }
                }

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
                    $this->_recursiveInheritPropertyUpdate($node, Tinebase_Model_Tree_Node::XPROPS_REVISION, $newValue, $oldValue, false);
                }

                $this->_checkQuotaAndRegisterRefLog($node, 0 - (int)$node->size, 0 - (int)$node->revision_size);
                $node->parent_id = $newParent->getId();
                $this->_checkQuotaAndRegisterRefLog($node, (int)$node->size, (int)$node->revision_size);
            } else {
                $this->_checkQuotaAndRegisterRefLog($node, 0, 0);
            }

            if (basename($oldPath) !== basename($newPath)) {
                $node->name = basename($newPath);
            }

            try {
                $deletedNewPathNode = $this->stat($newPath, null, true);
                $this->_updateDeletedNodeName($deletedNewPathNode);
            } catch (Tinebase_Exception_NotFound $tenf) {}

            $node = $this->_getTreeNodeBackend()->update($node, true);

            $fObj = $this->_fileObjectBackend->get($node->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER ?
                $newParent->object_id : $node->object_id);
            if ($node->type === Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                $fObj->hash = Tinebase_Record_Abstract::generateUID();
            }
            Tinebase_Timemachine_ModificationLog::getInstance()->setRecordMetaData($fObj, 'update', $fObj);
            $this->_fileObjectBackend->update($fObj);

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

    protected function _updateDeletedNodeName(Tinebase_Model_Tree_Node $_node)
    {
        $treeNodeBackend = $this->_getTreeNodeBackend();
        $parentId = $_node->parent_id;
        do {
            $id = uniqid();
            $name = $_node->name . $id;
            if (($len = mb_strlen($name)) > 255) {
                $name = mb_substr($name, $len - 255);
            }
        } while (null !== $treeNodeBackend->getChild($parentId, $name, true, false));
        $_node->name = $name;
        $this->_getTreeNodeBackend()->update($_node, true);
    }
    
    /**
     * create directory
     * needs to be /appid/folders/...asYouLikeFromHereOn
     *
     * @param string $path
     * @return Tinebase_Model_Tree_Node
     */
    public function mkdir($path)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Creating path ' . $path);
        
        $currentPath = array();
        $parentNode  = null;
        $pathParts   = $this->_splitPath($path);
        $node = null;

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            foreach ($pathParts as $pathPart) {
                $pathPart = trim($pathPart);
                $currentPath[]= $pathPart;

                try {
                    $node = $this->stat('/' . implode('/', $currentPath));
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' .
                        __LINE__ . ' Creating directory ' . $pathPart);

                    $node = $this->_createDirectoryTreeNode($parentNode, $pathPart);
                    $this->_addStatCache($currentPath, $node);
                }

                $parentNode = $node;
            }

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
        if (!$recursion) {
            $this->acquireWriteLock();
        }

        try {
            try {
                $node = $this->stat($path);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // we don't want a roll back here, we didn't do anything, nothing went really wrong
                // if the TENF is catched outside gracefully, a roll back in here would kill it!
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
                throw $tenf;
            }

            // if modlog is not active, we want to hard delete all soft deleted childs if there are any
            $children = $this->getTreeNodeChildren($node, !$this->_modLogActive);

            // check if child entries exists and delete if $_recursive is true
            if (count($children) > 0) {
                if ($recursive !== true) {
                    throw new Tinebase_Exception_InvalidArgument('directory not empty');
                } else {
                    /** @var Tinebase_Model_Tree_Node $child */
                    foreach ($children as $child) {
                        if (Tinebase_Model_Tree_FileObject::TYPE_FOLDER === $child->type) {
                            $this->rmdir($path . '/' . $child->name, true, true);
                        } else {
                            $this->unlink($path . '/' . $child->name, true);
                        }
                    }
                }
            }

            if (false === $recursion) {
                $this->_checkQuotaAndRegisterRefLog($node, 0 - (int)$node->size, 0 - (int)$node->revision_size);
            }
            $this->_getTreeNodeBackend()->softDelete($node->getId());
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
     * @param  boolean $getDeleted
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_NotFound
     */
    public function stat($path, $revision = null, $getDeleted = false)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        try {

            $pathParts = $this->_splitPath($path);
            // is pathParts[0] not an id (either 40 characters or only digits), then its an application name to resolve
            if (strlen($pathParts[0]) !== 40 && !ctype_digit($pathParts[0])) {
                $oldPart = $pathParts[0];
                $pathParts[0] = Tinebase_Application::getInstance()->getApplicationByName($pathParts[0])->getId();
                // + 1 in mb_substr offset because of the leading / char
                $path = '/' . $pathParts[0] . mb_substr('/' . ltrim($path, '/'), mb_strlen($oldPart) + 1);
            }
            $cacheId = $this->_getCacheId($pathParts, $revision);

            // let's see if the path is cached in statCache
            if (isset($this->_statCache[$cacheId])) {
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

                if (isset($this->_statCache[$cacheId])) {
                    $node = $parentNode = $this->_statCache[$cacheId];
                    break;
                }
            } while (($pathPart = array_pop($pathParts) !== null));

            $missingPathParts = array_diff_assoc($this->_splitPath($path), $pathParts);

            foreach ($missingPathParts as $pathPart) {
                $node = $this->_getTreeNodeBackend()->getChild($parentNode, $pathPart, $getDeleted);

                if ($node->is_deleted && null !== $parentNode && $parentNode->is_deleted) {
                    throw new Tinebase_Exception_NotFound('cascading deleted nodes');
                }

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
        if (!$recursion) {
            $this->acquireWriteLock();
        }

        try {
            try {
                $node = $this->stat($path);
            } catch (Tinebase_Exception_NotFound $tenf) {
                Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
                $transactionId = null;
                return false;
            }
            $this->deleteFileNode($node, false === $recursion);

            $this->clearStatCache($path);

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
     * undelete file node
     *
     * @param string $id
     * @param bool $updateDirectoryNodesHash
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function unDeleteFileNode($id, $updateDirectoryNodesHash = true)
    {
        if (false === $this->_modLogActive ) {
            return;
        }

        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $node = $this->get($id, true);

            Tinebase_Timemachine_ModificationLog::setRecordMetaData($node, 'undelete', $node);
            $this->_getTreeNodeBackend()->update($node);

            /** @var Tinebase_Model_Tree_FileObject $object */
            $object = $this->_fileObjectBackend->get($node->object_id, true);

            if ($object->is_deleted) {
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($object, 'undelete', $object);
                $object->indexed_hash = '';
                $this->_fileObjectBackend->update($object);
            }

            if (true === $updateDirectoryNodesHash) {
                $this->_checkQuotaAndRegisterRefLog($node, (int)$node->size, (int)$node->revision_size);
            }

            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            $transactionId = null;
        } finally {
            if (null !== $transactionId) {
                Tinebase_TransactionManager::getInstance()->rollBack();
            }
        }
    }

    public function isPreviewActive()
    {
        if ($this->_previewActive === null) {
            $this->_previewActive = true ===
                   Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS}
                && Tinebase_Config::getInstance()->featureEnabled(Tinebase_Config::FEATURE_CREATE_PREVIEWS);
        }
        return $this->_previewActive;
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
            $this->acquireWriteLock();

            if (true === $updateDirectoryNodesHash) {
                $this->_checkQuotaAndRegisterRefLog($node, 0 - (int)$node->size, 0 - (int)$node->revision_size);
            }

            $this->_getTreeNodeBackend()->softDelete($node->getId());

            if (false === $this->_modLogActive && $this->isPreviewActive()) {
                $hashes = $this->_fileObjectBackend->getHashes(array($node->object_id));
            } else {
                $hashes = array();
            }
            $this->_fileObjectBackend->softDelete($node->object_id);
            if (false === $this->_modLogActive ) {
                if (true === $this->_indexingActive) {
                    Tinebase_Fulltext_Indexer::getInstance()->removeFileContentsFromIndex($node->object_id);
                }
                if ($this->isPreviewActive()) {
                    $existingHashes = $this->_fileObjectBackend->checkRevisions($hashes);
                    $hashesToDelete = array_diff($hashes, $existingHashes);
                    Tinebase_FileSystem_Previews::getInstance()->deletePreviews($hashesToDelete);
                }
            }

            Tinebase_Record_PersistentObserver::getInstance()->fireEvent(new Tinebase_Event_Observer_DeleteFileNode(array(
               'observable' => $node
            )));

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
     * needs to be /appid/folders/...asYouLikeFromHereOn
     *
     * @param  string|Tinebase_Model_Tree_Node  $_parentId
     * @param  string                           $name
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _createDirectoryTreeNode($_parentId, $name)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;

            if (null !== ($deletedNode = $this->_getTreeNodeBackend()->getChild($parentId, $name, true, false)) &&
                    $deletedNode->is_deleted) {
                $deletedNode->is_deleted = 0;
                $deletedNode->deleted_time = null;
                $deletedNode->deleted_by = null;
                $object = $this->_fileObjectBackend->get($deletedNode->object_id, true);
                if ($object->is_deleted) {
                    $object->is_deleted = 0;
                    $object->deleted_time = null;
                    $object->deleted_by = null;
                    $this->_fileObjectBackend->update($object);
                }
                $treeNode = $this->_getTreeNodeBackend()->update($deletedNode);
            } else {

                $parentNode = $_parentId instanceof Tinebase_Model_Tree_Node
                    ? $_parentId
                    : ($_parentId ? $this->get($_parentId) : null);

                if (null === $parentNode) {
                    try {
                        $appId = Tinebase_Application::getInstance()->getApplicationById($name)->getId();
                        if ($appId !== $name) {
                            throw new Tinebase_Exception_InvalidArgument('path needs to start with /appId/folders/...');
                        }
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        throw new Tinebase_Exception_InvalidArgument('path needs to start with /appId/folders/...');
                    }
                } elseif (null === $parentNode->parent_id && $name !== 'folders') {
                    throw new Tinebase_Exception_InvalidArgument('path needs to start with /appId/folders/...');
                }

                $directoryObject = new Tinebase_Model_Tree_FileObject(array(
                    'type' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
                    'contentytype' => null,
                    'hash' => Tinebase_Record_Abstract::generateUID(),
                    'size' => 0
                ));
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($directoryObject, 'create');
                $directoryObject = $this->_fileObjectBackend->create($directoryObject);

                $treeNode = new Tinebase_Model_Tree_Node(array(
                    'name' => $name,
                    'object_id' => $directoryObject->getId(),
                    'parent_id' => $parentId,
                    'acl_node' => $parentNode && !empty($parentNode->acl_node) ? $parentNode->acl_node : null,
                    'pin_protected_node' => $parentNode && !empty($parentNode->pin_protected_node) ?
                        $parentNode->pin_protected_node : null,
                    Tinebase_Model_Tree_Node::XPROPS_REVISION => $parentNode &&
                        !empty($parentNode->{Tinebase_Model_Tree_Node::XPROPS_REVISION}) ?
                        $parentNode->{Tinebase_Model_Tree_Node::XPROPS_REVISION} : null
                ));
                $treeNode = $this->_getTreeNodeBackend()->create($treeNode);

                $this->_checkQuotaAndRegisterRefLog($treeNode, 0, 0);
            }

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
     * @param  string                           $_mimeType
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    public function createFileTreeNode($_parentId, $_name, $_fileType = Tinebase_Model_Tree_FileObject::TYPE_FILE,
        $_mimeType = null)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;

            if (null !== ($deletedNode = $this->_getTreeNodeBackend()->getChild($parentId, $_name, true, false)) &&
                    $deletedNode->is_deleted) {
                $deletedNode->is_deleted = 0;
                $deletedNode->deleted_by = null;
                $deletedNode->deleted_time = null;
                /** @var Tinebase_Model_Tree_FileObject $object */
                $object = $this->_fileObjectBackend->get($deletedNode->object_id, true);
                if (isset($_SERVER['HTTP_X_OC_MTIME'])) {
                    $object->creation_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
                    $object->last_modified_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
                    Tinebase_Server_WebDAV::getResponse()->setHeader('X-OC-MTime', 'accepted');
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " using X-OC-MTIME: {$object->last_modified_time->format(Tinebase_Record_Abstract::ISO8601LONG)} for {$_name}");
                    }
                }
                $object->hash = Tinebase_Record_Abstract::generateUID();
                $object->size = 0;
                $object->is_deleted = 0;
                $object->deleted_by = null;
                $object->deleted_time = null;
                $object->type = $_fileType;
                $object->preview_count = 0;
                $object->contenttype = $_mimeType;
                $this->_fileObjectBackend->update($object);
                //we can use _treeNodeBackend as we called get further up
                $treeNode = $this->_treeNodeBackend->update($deletedNode);
            } else {

                $parentNode = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId : $this->get($parentId);

                $fileObject = new Tinebase_Model_Tree_FileObject([
                    'type' => $_fileType,
                ]);
                if (null !== $_mimeType) {
                    $fileObject->contenttype = $_mimeType;
                }
                Tinebase_Timemachine_ModificationLog::setRecordMetaData($fileObject, 'create');

                // quick hack for 2014.11 - will be resolved correctly in 2015.11-develop
                if (isset($_SERVER['HTTP_X_OC_MTIME'])) {
                    $fileObject->creation_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
                    $fileObject->last_modified_time = new Tinebase_DateTime($_SERVER['HTTP_X_OC_MTIME']);
                    Tinebase_Server_WebDAV::getResponse()->setHeader('X-OC-MTime', 'accepted');
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " using X-OC-MTIME: {$fileObject->last_modified_time->format(Tinebase_Record_Abstract::ISO8601LONG)} for {$_name}");
                    }

                }

                $fileObject = $this->_fileObjectBackend->create($fileObject);

                $treeNode = new Tinebase_Model_Tree_Node(array(
                    'name' => $_name,
                    'object_id' => $fileObject->getId(),
                    'parent_id' => $parentId,
                    'acl_node' => $parentNode && empty($parentNode->acl_node) ? null : $parentNode->acl_node,
                    'pin_protected_node' => $parentNode && empty($parentNode->pin_protected_node) ? null :
                        $parentNode->pin_protected_node,
                ));

                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                    Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
                        ' ' . print_r($treeNode->toArray(), true));
                }

                $treeNode = $this->_getTreeNodeBackend()->create($treeNode);
            }

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
     * @param string $hash
     * @return bool
     */
    public function checkHashFile(string $hash)
    {
        $file = $this->_basePath . DIRECTORY_SEPARATOR . substr($hash, 0, 3) . DIRECTORY_SEPARATOR . substr($hash, 3);
        if (!($fh = fopen($file, 'r'))) {
            throw new Tinebase_Exception_Backend('could not open file: ' . $file);
        }
        
        $ctx = hash_init('sha1');
        hash_update_stream($ctx, $fh);
        $fileHash = hash_final($ctx);
        fclose($fh);
        
        return $hash === $fileHash;
    }

    /**
     * places contents into a file blob
     * 
     * @param  resource $contents
     * @return array [hash, hashFilePath, Tinebase_FileSystem_AVScan_Result]
     * @throws Tinebase_Exception_NotImplemented
     * @throws Tinebase_Exception_UnexpectedValue
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
            if (@mkdir($hashDirectory, 0700) === false) {
                if (!is_dir($hashDirectory)) {
                    throw new Tinebase_Exception_UnexpectedValue('failed to create directory');
                }
            }
        }
        
        $hashFile      = $hashDirectory . '/' . substr($hash, 3);
        $avResult = new Tinebase_FileSystem_AVScan_Result(Tinebase_FileSystem_AVScan_Result::RESULT_ERROR, null);
        $fileCreated = false;

        while (!file_exists($hashFile)) {
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create hash file: ' . $hashFile);
            $hashHandle = fopen($hashFile, 'x');
            if (! $hashHandle) {
                if (file_exists($hashFile)) {
                    break;
                }
                throw new Tinebase_Exception_UnexpectedValue('failed to create hash file');
            }
            rewind($handle);
            stream_copy_to_stream($handle, $hashHandle);
            fclose($hashHandle);
            $fileCreated = true;

            // AV scan
            if (Tinebase_FileSystem_AVScan_Factory::MODE_OFF !== Tinebase_Config::getInstance()
                    ->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_AVSCAN_MODE}) {
                if (false === ($fileSize = filesize($hashFile))) {
                    throw new Tinebase_Exception_UnexpectedValue('failed to get hash file size');
                }
                if ($fileSize <= Tinebase_Config::getInstance()
                        ->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_AVSCAN_MAXFSIZE}) {
                    if (!($hashHandle = fopen($hashFile, 'r'))) {
                        throw new Tinebase_Exception_UnexpectedValue('failed to repoen hash file');
                    }
                    $avResult = Tinebase_FileSystem_AVScan_Factory::getScanner()->scan($hashHandle);
                    fclose($hashHandle);
                }
            }
            break;
        }

        $tries = 0;
        $currentFilesHash = null;
        $previousCurrentFilesHash = null;
        while (!$fileCreated && ($currentFilesHash = sha1_file($hashFile)) !== $hash && $tries++ < 10) {
            if ($previousCurrentFilesHash === $currentFilesHash) {
                // hash did not change -> we will start cleaning up then
                break;
            }
            $previousCurrentFilesHash = $currentFilesHash;
            // hash file mismatch! First lets sleep some time, maybe somebody is writting the file...
            usleep(100000); // 100ms
        }
        if (!$fileCreated && $currentFilesHash !== $hash) {
            // we have a corrupt hash file, lets remove it.
            if (!unlink($hashFile)) {
                throw new Tinebase_Exception_UnexpectedValue('failed to unlink corrupt hash file');
            }

            return $this->createFileBlob($contents);
        }
        
        return array($hash, $hashFile, $avResult);
    }
    
    /**
     * get tree node children
     * 
     * @param string|Tinebase_Model_Tree_Node|Tinebase_Record_RecordSet  $nodeId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     *
     * TODO always ignore acl here?
     */
    public function getTreeNodeChildren($nodeId, $getDeleted = false)
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

        $filter = [
            ['field' => 'parent_id', 'operator' => $operator, 'value' => $nodeId]
        ];
        if ($getDeleted) {
            $filter[] = ['field' => 'is_deleted', 'operator' => 'equals',
                'value' => Tinebase_Model_Filter_Bool::VALUE_NOTSET];
        }
        $searchFilter = new Tinebase_Model_Tree_Node_Filter($filter, Tinebase_Model_Filter_FilterGroup::CONDITION_AND,
            array('ignoreAcl' => true));
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
        if (null === $revision) {
            $this->_statCacheById[$node->getId()] = $node;
        }
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
            /** @var Tinebase_Model_Tree_FileObject $fileObject */
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
            $this->_updateFileObject($this->get($currentNodeObject->parent_id), $currentNodeObject, $fileObject, $_node->hash);

            if ($currentNodeObject->acl_node !== $_node->acl_node) {
                // update acl_node of subtree if changed
                $this->_recursiveInheritPropertyUpdate($_node, 'acl_node', $_node->acl_node, $currentNodeObject->acl_node, true, true);
            }
            if ($currentNodeObject->pin_protected_node !== $_node->pin_protected_node) {
                // update pin_protected_node of subtree if changed
                $this->_recursiveInheritPropertyUpdate($_node, 'pin_protected_node', $_node->pin_protected_node,
                    $currentNodeObject->pin_protected_node);
            }

            $oldValue = $currentNodeObject->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION);
            $newValue = $_node->xprops(Tinebase_Model_Tree_Node::XPROPS_REVISION);
            if (!empty($newValue) && !isset($newValue[Tinebase_Model_Tree_Node::XPROPS_REVISION_NODE_ID])) {
                $newValue[Tinebase_Model_Tree_Node::XPROPS_REVISION_NODE_ID] = $_node->getId();
            }

            if ($oldValue != $newValue) {
                $oldValue = count($oldValue) > 0 ? json_encode($oldValue) : null;
                $newValue = count($newValue) > 0 ? json_encode($newValue) : null;

                // update revisionProps of subtree if changed
                $this->_recursiveInheritPropertyUpdate($_node, Tinebase_Model_Tree_Node::XPROPS_REVISION, $newValue, $oldValue, false);
            }

            /** @var Tinebase_Model_Tree_Node $newNode */
            $newNode = $this->_getTreeNodeBackend()->update($_node, false);

            if (isset($_node->grants)) {
                $newNode->grants = $_node->grants;
            }

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
     * @param bool   $_ignoreACL
     * @param bool   $_updateDeleted
     */
    protected function _recursiveInheritPropertyUpdate(Tinebase_Model_Tree_Node $_node, $_property, $_newValue, $_oldValue, $_ignoreACL = true, $_updateDeleted = false)
    {
        $childIds = $this->getAllChildIds(array($_node->getId()), [
                ['field' => $_property,   'operator'  => 'equals', 'value' => $_oldValue],
                ['field' => 'is_deleted', 'operator'  => 'equals', 'value' => $_updateDeleted ?
                    Tinebase_Model_Filter_Bool::VALUE_NOTSET : false]
            ], $_ignoreACL);
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
     * returns all children nodes, allows to set additional filters
     * be careful with additional filters! The recursion only works on folders that match the filter!
     * e.g. filter for type !== FOLDER do not work as recursion never starts!
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
        $filter = [
            ['field' => 'parent_id',  'operator'  => 'in',     'value' => $_ids],
        ];
        foreach($_additionalFilters as $aF) {
            $filter[] = $aF;
        }
        $searchFilter = new Tinebase_Model_Tree_Node_Filter($filter,  /* $_condition = */ '', /* $_options */ array(
            'ignoreAcl' => $_ignoreAcl,
        ));
        if (null !== $_requiredGrants) {
            $searchFilter->setRequiredGrants((array) $_requiredGrants);
        }
        $children = $this->search($searchFilter, null, true);
        if (count($children) > 0) {
            $result = array_merge($children, $this->getAllChildIds($children, $_additionalFilters, $_ignoreAcl));
        }

        return $result;
    }

    /**
     * get path of node
     * 
     * @param Tinebase_Model_Tree_Node|string $node
     * @param boolean $getPathAsString
     * @param boolean $getFromStatCache
     * @return array|string
     */
    public function getPathOfNode($node, $getPathAsString = false, $getFromStatCache = false)
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {
            $node = $node instanceof Tinebase_Model_Tree_Node ? $node : $this->get($node);

            $nodesPath = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($node));
            while ($node->parent_id) {
                if ($getFromStatCache && isset($this->_statCacheById[$node->parent_id])) {
                    $node = $this->_statCacheById[$node->parent_id];
                } else {
                    $node = $this->get($node->parent_id);
                }
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
     *
     * @return bool
     */
    public function clearDeletedFiles()
    {
        $this->clearDeletedFilesFromFilesystem();
        return true;
    }

    /**
     * removes deleted files that no longer exist in the database from the filesystem
     *
     * @param bool $_sleep
     * @return int number of deleted files
     * @throws Tinebase_Exception_AccessDenied
     */
    public function clearDeletedFilesFromFilesystem($_sleep = true)
    {
        try {
            $dirIterator = new DirectoryIterator($this->_basePath);
        } catch (Exception $e) {
            throw new Tinebase_Exception_AccessDenied('Could not open files directory.');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Scanning ' . $this->_basePath . ' for deleted files ...');
        
        $deleteCount = 0;
        $hashesToDelete = [];

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
            $hashesToDelete = array_merge($hashesToDelete, array_diff($hashsToCheck, $existingHashes));

            Tinebase_Lock::keepLocksAlive();
        }

        // to avoid concurrency problems we just sleep a second to give concurrent write processes time to make their
        // update query. As we are not in a transaction, we should read uncommited(?). Any suggestions how to improve?
        if ($_sleep) {
            sleep(1);
        }

        $existingHashes = $this->_fileObjectBackend->checkRevisions($hashesToDelete);
        $hashesToDelete = array_diff($hashesToDelete, $existingHashes);
        // remove from filesystem if not existing any more
        foreach ($hashesToDelete as $hashToDelete) {
            $filename = $this->_basePath . '/' . substr($hashToDelete, 0, 3) . '/' . substr($hashToDelete, 3);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Deleting ' . $filename);
            unlink($filename);
            $deleteCount++;

            Tinebase_Lock::keepLocksAlive();
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Deleted ' . $deleteCount . ' obsolete file(s).');
        
        return $deleteCount;
    }
    
    /**
     * removes deleted files that no longer exist in the filesystem from the database
     *
     * @param bool $dryRun
     * @return integer number of deleted files
     */
    public function clearDeletedFilesFromDatabase($dryRun = true)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Scanning database for deleted files ...');

        // get all file objects (thus, no folders) from db and check filesystem existence
        $filter = new Tinebase_Model_Tree_FileObjectFilter([
            ['field' => 'type', 'operator' => 'not', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER]
        ]);
        $start = 0;
        $limit = 500;
        $hashesToDelete = [];
        $baseDir = Tinebase_Core::getConfig()->filesdir;

        do {
            $pagination = new Tinebase_Model_Pagination([
                'start' => $start,
                'limit' => $limit,
                'sort' => 'id',
            ]);

            $fileObjectIds = $this->_fileObjectBackend->search($filter, $pagination, true);
            foreach ($this->_fileObjectBackend->getHashes($fileObjectIds) as $hash) {
                if (!file_exists($baseDir . DIRECTORY_SEPARATOR . substr($hash, 0, 3) . DIRECTORY_SEPARATOR .
                        substr($hash, 3))) {
                    $hashesToDelete[] = $hash;
                }
            }

            $start += $limit;
        } while (count($fileObjectIds) >= $limit);

        if (($count = count($hashesToDelete)) === 0) {
            return 0;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
            . ' deleting these hashes: ' . join(', ', $hashesToDelete));

        if (true === $dryRun) {
            return $count;
        }

        if ($this->isPreviewActive()) {
            Tinebase_FileSystem_Previews::getInstance()->deletePreviews($hashesToDelete);
        }

        // first delete all old revisions
        foreach ($this->_fileObjectBackend->getRevisionForHashes($hashesToDelete) as $fileObjectId => $revisions) {
            $this->_fileObjectBackend->deleteRevisions($fileObjectId, $revisions);
        }

        // now find hashes that have not been deleted (because they are the current revision)
        // then delete those file objects completely
        $existingHashes = $this->_fileObjectBackend->checkRevisions($hashesToDelete);

        if (count($existingHashes) === 0) {
            return $count;
        }

        $fileObjectIds = array_keys($this->_fileObjectBackend->getRevisionForHashes($existingHashes));

        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
            . ' deleting these file objects: ' . join(', ', $fileObjectIds));

        $nodeIdsToDelete = $this->_getTreeNodeBackend()->search(
            new Tinebase_Model_Tree_Node_Filter(array(array(
                'field'     => 'object_id',
                'operator'  => 'in',
                'value'     => $fileObjectIds
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

        // hard delete is ok here
        $this->_fileObjectBackend->delete($fileObjectIds);
        if (true === $this->_indexingActive) {
            Tinebase_Fulltext_Indexer::getInstance()->removeFileContentsFromIndex($fileObjectIds);
        }

        return $count;
    }

    /**
     * copy tempfile data to file path
     * 
     * @param mixed $tempFile
         Tinebase_Model_Tree_Node     with property hash, tempfile or stream
         Tinebase_Model_Tempfile      tempfile
         string                       with tempFile id
         array                        with [id] => tempFile id (this is odd IMHO)
         stream                       stream ressource
         null                         create empty file
     * @param string $path
     * @param boolean $deleteTempFileAfterCopy
     * @return Tinebase_Model_Tree_Node
     * @throws Tinebase_Exception_AccessDenied
     */
    public function copyTempfile($tempFile, $path, $deleteTempFileAfterCopy = false)
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

        if ($tempFile instanceof Tinebase_Model_TempFile && $deleteTempFileAfterCopy) {
            Tinebase_TempFile::getInstance()->deleteTempFile($tempFile);
        }

        return $node;
    }

    public function setAclFromParent($path, $ifNotNull = false)
    {
        $node = $this->stat($path);
        $parent = $this->get($node->parent_id);
        if (!$ifNotNull || !empty($parent->acl_node)) {
            $node->acl_node = $parent->acl_node;
            $this->update($node);
        }

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
        $deleteFile = !$this->fileExists($path);
        try {
            if (!$handle = $this->fopen($path, 'w')) {
                throw new Tinebase_Exception_AccessDenied('Permission denied to create file (filename ' . $path . ')');
            }

            if (!is_resource($in)) {
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

        } catch (Exception $e) {
            if ($deleteFile) {
                $this->unlink($path);
            }
            throw $e;
        }
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
        if (false === $this->_indexingActive) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Indexing disabled');
            return true;
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting to check indexing');

        $success = true;
        foreach($this->_fileObjectBackend->getNotIndexedObjectIds() as $objectId) {
            $success = $this->indexFileObject($objectId) && $success;

            Tinebase_Lock::keepLocksAlive();
        }

        return $success;
    }

    /**
     * check acl of path
     *
     * @param Tinebase_Model_Tree_Node_Path $_path
     * @param string $_action get|add
     * @param boolean $_topLevelAllowed
     * @throws Tinebase_Exception_AccessDenied
     * @return boolean
     */
    public function checkPathACL(Tinebase_Model_Tree_Node_Path $_path, $_action = 'get', $_topLevelAllowed = true, $_throw = true)
    {
        switch ($_path->containerType) {
            case Tinebase_FileSystem::FOLDER_TYPE_PERSONAL:
                if ($_path->containerOwner && ($_topLevelAllowed || ! $_path->isToplevelPath())) {
                    if ($_path->isToplevelPath()) {
                        $hasPermission = ($_path->containerOwner === Tinebase_Core::getUser()->accountLoginName || $_action === 'get');
                    } else {
                        $hasPermission = $this->_checkACLNode($_path->getNode(), $_action);
                    }
                } else {
                    $hasPermission = ($_action === 'get');
                }
                break;
            case Tinebase_FileSystem::FOLDER_TYPE_SHARED:
                // if is toplevel path and action is add, allow manage_shared_folders right
                // if it is toplevel path and action is get allow
                // else just do normal ACL node check
                if ($_path->isToplevelPath() && $_action !== 'get' && !$_topLevelAllowed) {
                    $hasPermission = false;
                    break;
                }
                if (true === ($hasPermission = Tinebase_Acl_Roles::getInstance()->hasRight(
                        $_path->application->name,
                        Tinebase_Core::getUser()->getId(),
                        Tinebase_Acl_Rights::ADMIN
                    ))) {
                    // admin, go ahead
                    break;
                }
                if ($_path->isToplevelPath()) {
                    if ('add' === $_action) {
                        $hasPermission = Tinebase_Acl_Roles::getInstance()->hasRight(
                            $_path->application->name,
                            Tinebase_Core::getUser()->getId(),
                            Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS
                        );
                    } else {
                        $hasPermission = 'get' === $_action;
                    }
                } else {
                    $hasPermission = $this->_checkACLNode($_path->getNode(), $_action);
                }
                break;
            case Tinebase_Model_Tree_Node_Path::TYPE_ROOT:
                $hasPermission = ($_action === 'get');
                break;
            case self::FOLDER_TYPE_RECORDS:
                if ($_action !== 'get') {
                    throw new Tinebase_Exception_InvalidArgument('only "get" action supported here');
                }
                $model = $_path->getRecordModel();
                try {
                    $controller = Tinebase_Core::getApplicationInstance($_path->application, $model);
                    $recordId = $_path->getRecordId();
                    $controller->get($recordId);
                    $hasPermission = true;
                } catch (Tinebase_Exception_AccessDenied $tead) {
                    $hasPermission = false;
                }
                break;
            default:
                $hasPermission = $this->_checkACLNode($_path->getNode(), $_action);
        }

        if (true === $_throw && ! $hasPermission) {
            throw new Tinebase_Exception_AccessDenied('No permission to ' . $_action . ' nodes in path ' . $_path->flatpath);
        }

        return $hasPermission;
    }

    /**
     * DO NOT USE THIS FUNCTION! checkPathACL is what you want to use!
     * this function may only be used by checkPathACL (or if you are really sure of what you are doing!)
     *
     * check if user has the permissions for the node
     *
     * does not start a transaction!
     *
     * @param Tinebase_Model_Tree_Node $_node
     * @param string $_action get|update|...
     * @return boolean
     */
    protected function _checkACLNode(Tinebase_Model_Tree_Node $_node, $_action = 'get')
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
            case 'admin':
                return false;
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
     * @param   int|Tinebase_Record_Interface $_containerId
     * @param   array|string                 $_grant
     * @return  boolean
     */
    public function hasGrant($_accountId, $_containerId, $_grant)
    {
        // always refetch node to have current acl_node & pin_protected_node value
        $node = $this->get($_containerId);
        if (!isset($this->_areaLockCache[$node->getId()])
            && null !== $node->pin_protected_node
            && Tinebase_AreaLock::getInstance()->hasLock(Tinebase_Model_AreaLockConfig::AREA_DATASAFE)
            && Tinebase_AreaLock::getInstance()->isLocked(Tinebase_Model_AreaLockConfig::AREA_DATASAFE)
        ) {
            return false;
        }
        $this->_areaLockCache[$node->getId()] = true;
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
                    'user' => $_accountId instanceof Tinebase_Record_Interface
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
                'user' => $_accountId instanceof Tinebase_Record_Interface
                    ? $_accountId->getId()
                    : $_accountId
            )
        );
        $filter->setRequiredGrants((array)$_grant);
        // get shared folders of other users
        $sharedFoldersOfOtherUsers = $this->searchNodes($filter);

        foreach ($otherAccountNodes as $otherAccount) {
            if (count($sharedFoldersOfOtherUsers->filter('parent_id', $otherAccount->getId())) > 0) {
                $result->addRecord($otherAccount);
                /** @noinspection PhpUndefinedMethodInspection */
                $account = Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend(
                    'accountId',
                    $otherAccount->name,
                    'Tinebase_Model_FullUser'
                );
                $otherAccount->name = $account->accountLoginName;
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
     * @return  Tinebase_Record_Interface
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
     * @param   int|Tinebase_Record_Interface        $_containerId
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
                Tinebase_Model_Grants::GRANT_EDIT => false,
                Tinebase_Model_Grants::GRANT_DELETE => false,
                Tinebase_Model_Grants::GRANT_EXPORT => true,
                Tinebase_Model_Grants::GRANT_SYNC => true,
            ));
        } else if ($pathRecord->isToplevelPath() && $pathRecord->containerType === Tinebase_FileSystem::FOLDER_TYPE_SHARED) {
            /** @noinspection PhpUndefinedMethodInspection */
            $account = $_accountId instanceof Tinebase_Model_FullUser
                ? $_accountId
                : Tinebase_User::getInstance()->getUserByPropertyFromSqlBackend('accountId', $_accountId, 'Tinebase_Model_FullUser');
            $hasManageSharedRight = $account->hasRight($pathRecord->application->name, Tinebase_Acl_Rights::MANAGE_SHARED_FOLDERS);
            return new Tinebase_Model_Grants(array(
                'account_id' => $accountId,
                'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER,
                Tinebase_Model_Grants::GRANT_READ => true,
                Tinebase_Model_Grants::GRANT_ADD => $hasManageSharedRight,
                Tinebase_Model_Grants::GRANT_EDIT => false,
                Tinebase_Model_Grants::GRANT_DELETE => false,
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
     * @param   int|Tinebase_Record_Interface $_containerId
     * @param   bool                         $_ignoreAcl
     * @param   string                       $_grantModel
     * @return  Tinebase_Record_RecordSet subtype Tinebase_Model_Grants
     * @throws  Tinebase_Exception_AccessDenied
     *
     * TODO add to interface
     */
    public function getGrantsOfContainer($_containerId, $_ignoreAcl = false, /** @noinspection PhpUnusedParameterInspection */$_grantModel = 'Tinebase_Model_Grants')
    {
        $record = $_containerId instanceof Tinebase_Model_Tree_Node ? $_containerId : $this->get($_containerId);

        if (! $_ignoreAcl) {
            if (! Tinebase_Core::getUser()->hasGrant($record, Tinebase_Model_Grants::GRANT_READ)) {
                throw new Tinebase_Exception_AccessDenied('not allowed to read grants');
            }
        }

        return $this->_nodeAclController->getGrantsForRecord($record);
    }

    public function clearFileObjects()
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' starting to clear file objects');

        $this->_fileObjectBackend->deletedUnusedObjects();

        return true;
    }
    /**
     * remove file revisions based on settings:
     * Tinebase_Config::FILESYSTEM -> Tinebase_Config::FILESYSTEM_NUMKEEPREVISIONS
     * Tinebase_Config::FILESYSTEM -> Tinebase_Config::FILESYSTEM_MONTHKEEPREVISIONS
     * or folder specific settings
     *
     * @return bool
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
                        $count += $this->_fileObjectBackend->deleteRevisions($fileNode->object_id, array_slice($revisions, 0, count($revisions) - $numRev));
                    }
                }

                if (1 !== $numRev && $monthRev > 0) {
                    $count += $this->_fileObjectBackend->clearOldRevisions($fileNode->object_id, $monthRev);
                }

            } catch(Tinebase_Exception_NotFound $tenf) {}

            Tinebase_Lock::keepLocksAlive();
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' cleared ' . $count . ' file revisions');

        return true;
    }

    /**
     * @return array
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function reportPreviewStatus()
    {
        $status = ['missing' => 0, 'created' => 0, 'missing_files' => []];

        if (! $this->isPreviewActive()) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' previews are disabled');
            return $status;
        }

        $created = &$status['created'];
        $missing = &$status['missing'];
        $missingFilenames = &$status['missing_files'];

        $treeNodeBackend = $this->_getTreeNodeBackend();
        $previewController = Tinebase_FileSystem_Previews::getInstance();

        foreach ($treeNodeBackend->search(
            new Tinebase_Model_Tree_Node_Filter([
                ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE]
            ], '', ['ignoreAcl' => true])
            , null, true) as $id) {

            /** @var Tinebase_Model_Tree_Node $node */
            try {
                $treeNodeBackend->setRevision(null);
                $node = $treeNodeBackend->get($id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                continue;
            }

            $availableRevisions = $node->available_revisions;
            if (!is_array($availableRevisions)) {
                $availableRevisions = explode(',', $availableRevisions);
            }
            foreach ($availableRevisions as $revision) {
                if ($node->revision != $revision) {
                    $treeNodeBackend->setRevision($revision);
                    try {
                        $actualNode = $treeNodeBackend->get($id);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        continue;
                    } finally {
                        $treeNodeBackend->setRevision(null);
                    }
                } else {
                    $actualNode = $node;
                }

                if (!$previewController->canNodeHavePreviews($actualNode)) {
                    continue;
                }

                if ($previewController->hasPreviews($actualNode)) {
                    $created++;
                } else {
                    $missing++;
                    $missingFilenames[] = $actualNode->name;
                }
            }
        }

        return $status;
    }

    /**
     * create preview for files without a preview, delete previews for already deleted files
     *
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function sanitizePreviews()
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' starting to sanitize previews');

        if (! $this->isPreviewActive()) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' previews are disabled');
            return true;
        }

        $treeNodeBackend = $this->_getTreeNodeBackend();
        $previewController = Tinebase_FileSystem_Previews::getInstance();
        $validHashes = array();
        $invalidHashes = array();
        $created = 0;
        $deleted = 0;

        foreach ($treeNodeBackend->search(
                new Tinebase_Model_Tree_Node_Filter([
                    ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FILE]
                ], '', ['ignoreAcl' => true])
                , null, true) as $id) {

            /** @var Tinebase_Model_Tree_Node $node */
            try {
                $treeNodeBackend->setRevision(null);
                $node = $treeNodeBackend->get($id);
            } catch (Tinebase_Exception_NotFound $tenf) {
                continue;
            }

            $availableRevisions = $node->available_revisions;
            if (!is_array($availableRevisions)) {
                $availableRevisions = explode(',', $availableRevisions);
            }
            foreach ($availableRevisions as $revision) {
                if ($node->revision != $revision) {
                    $treeNodeBackend->setRevision($revision);
                    try {
                        $actualNode = $treeNodeBackend->get($id);
                    } catch (Tinebase_Exception_NotFound $tenf) {
                        continue;
                    } finally {
                        $treeNodeBackend->setRevision(null);
                    }
                } else {
                    $actualNode = $node;
                }

                if (!$previewController->canNodeHavePreviews($actualNode)) {
                    continue;
                }

                if ($previewController->hasPreviews($actualNode)) {
                    $validHashes[$actualNode->hash] = true;
                    continue;
                }

                if (! $previewController->createPreviews($actualNode)) {
                    continue;
                }

                $validHashes[$actualNode->hash] = true;
                ++$created;
            }

            Tinebase_Lock::keepLocksAlive();
        }

        $treeNodeBackend->setRevision(null);


        $parents = array();
        foreach($treeNodeBackend->search(
                new Tinebase_Model_Tree_Node_Filter([
                    ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_PREVIEW]
                ], '', ['ignoreAcl' => true])
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

            Tinebase_Lock::keepLocksAlive();
        }

        $validHashes = $this->_fileObjectBackend->checkRevisions($invalidHashes);
        $hashesToDelete = array_diff($invalidHashes, $validHashes);
        if (count($hashesToDelete) > 0) {
            $deleted = count($hashesToDelete);
            $previewController->deletePreviews($hashesToDelete);
        }

        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' created ' . $created . ' new previews, deleted ' . $deleted . ' previews.');

        // check for empty preview folders and delete them
        $baseNode = $previewController->getBasePathNode();
        foreach($treeNodeBackend->search(
                new Tinebase_Model_Tree_Node_Filter([
                    ['field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER],
                    ['field' => 'parent_id', 'operator' => 'equals', 'value' => $baseNode->getId()],
                ], '', ['ignoreAcl' => true])
                , null, true) as $id) {
            if (count($treeNodeBackend->search(
                    new Tinebase_Model_Tree_Node_Filter([
                        ['field' => 'parent_id', 'operator' => 'equals', 'value' => $id],
                    ], '', ['ignoreAcl' => true])
                    , null, true)) === 0) {
                try {
                    $this->rmdir($this->getPathOfNode($id, true));
                } catch (Exception $e) {}

                Tinebase_Lock::keepLocksAlive();
            }
        }

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
     * @param string $_hash
     * @param integer $_status
     */
    public function updatePreviewStatus($_hash, $_status)
    {
        $this->_fileObjectBackend->updatePreviewStatus($_hash, $_status);
    }

    /**
     * @param string $_hash
     * @param integer $_count
     */
    public function updatePreviewErrorCount($_hash, $_count)
    {
        $this->_fileObjectBackend->updatePreviewErrorCount($_hash, $_count);
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
        if (! Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE}) {
            // only done with FILESYSTEM_MODLOGACTIVE
            return true;
        }

        $nodeId = $_fileNodeId;
        $foundUsers = array();
        $foundGroups = array();
        $alarmController = Tinebase_Alarm::getInstance();

        do {
            $node = $this->get($nodeId, true);
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
                                'options'       => json_encode(array(
                                    'files'     => array($_fileNodeId => array($_crudAction => true)),
                                    'accountId' => $accountId
                                )),
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
            $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE, $accountId));
            $translate = Tinebase_Translation::getTranslation('Tinebase', $locale);

            try {
                $user = Tinebase_User::getInstance()->getFullUserById($accountId);
            } catch(Tinebase_Exception_NotFound $tenf) {
                continue;
            }

            $translatedMsgHeader = $translate->_('The following files have changed:'); // _('The following files have changed:')
            $fileStr = $translate->_('File'); // _('File')
            $createdStr = $translate->_('has been created.'); // _('has been created.')
            $updatedStr = $translate->_('has been changed.'); // _('has been changed.')
            $deleteStr = $translate->_('has been deleted.'); // _('has been deleted.')

            $messageBody = '<html><body><p>' . $translatedMsgHeader . '</p>';
            foreach($_crudActions as $fileNodeId => $changes) {

                try {
                    $fileNode = $fileSystem->get($fileNodeId, true);
                } catch(Tinebase_Exception_NotFound $tenf) {
                    continue;
                }

                $path = Filemanager_Model_Node::getDeepLink($fileNode);

                $messageBody .= '<p>';

                foreach ($changes as $change => $foo) {
                    switch($change) {
                        case 'created':
                            $messageBody .= $fileStr . ' <a href="' . $path . '">' . $fileNode->name . '</a> ' . $createdStr . '<br/>';
                            break;
                        case 'updated':
                            $messageBody .= $fileStr . ' <a href="' . $path . '">' . $fileNode->name . '</a> ' . $updatedStr . '<br/>';
                            break;
                        case 'deleted':
                            $messageBody .= $fileStr . ' <a href="' . $path . '">' . $fileNode->name . '</a> ' . $deleteStr . '<br/>';
                            break;
                        default:
                            // should not happen!
                    }
                }

                $messageBody .= '</p>';
            }
            $messageBody .= '</body></html>';

            $translatedSubject = $translate->_('filemanager notification'); // _('filemanager notification')

            Tinebase_Notification::getInstance()->send($accountId, array($user->contact_id), $translatedSubject, '', $messageBody);
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

    /**
     * @return bool
     */
    public function avScan()
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' starting... ');

        if (Tinebase_FileSystem_AVScan_Factory::MODE_OFF ===
                Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
                    ->{Tinebase_Config::FILESYSTEM_AVSCAN_MODE}) {
            return true;
        }

        $lockId = __METHOD__;
        if (!Tinebase_Core::acquireMultiServerLock($lockId)) {
            return true;
        }
        $raii = new Tinebase_RAII(function() use($lockId) {
            Tinebase_Core::releaseMultiServerLock($lockId);
        });

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' updating... ');

        $avScanner = Tinebase_FileSystem_AVScan_Factory::getScanner();

        $result = true;
        $fh = null;
        $db = Tinebase_Core::getDb();
        $transManager = Tinebase_TransactionManager::getInstance();

        if (!($baseDir = opendir($this->_basePath))) {
            Tinebase_Exception::log(new Tinebase_Exception_UnexpectedValue('can not open basedir'));
            return false;
        }

        while (false !== ($hashDir = readdir($baseDir))) {
            if (strlen($hashDir) !== 3 || !is_dir($this->_basePath . '/' . $hashDir)) continue;
            if (!($fileDir = opendir($this->_basePath . '/' . $hashDir))) {
                Tinebase_Exception::log(new Tinebase_Exception_UnexpectedValue('can not open filedir: ' .
                    $this->_basePath . '/' . $hashDir));
                $result = false;
                continue;
            }
            while (false !== ($file = readdir($fileDir))) {
                $path = $this->_basePath . '/' . $hashDir . '/' . $file;
                if (!is_file($path)) continue;

                if (false === ($fileSize = filesize($path))) {
                    Tinebase_Exception::log(new Tinebase_Exception_UnexpectedValue('failed to get hash file size: ' .
                        $path));
                    $result = false;
                    continue;
                }
                if ($fileSize > Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
                        ->{Tinebase_Config::FILESYSTEM_AVSCAN_MAXFSIZE}) {
                    continue;
                }

                $fh = null;
                $scanResult = null;
                $transId = $transManager->startTransaction($db);
                try {
                    $fileObjectRevisions = $this->_fileObjectBackend->getRevisionForHashes([$hashDir . $file], true);

                    if (count($fileObjectRevisions) === 0) {
                        $transManager->commitTransaction($transId);
                        $transId = null;
                        continue;
                    }

                    foreach ($fileObjectRevisions as $fObjId => $revisions) {
                        foreach ($revisions as $revision) {
                            $this->_fileObjectBackend->setRevision($revision);
                            /** @var \Tinebase_Model_Tree_FileObject $fObj */
                            $fObj = $this->_fileObjectBackend->get($fObjId, true);
                            if ($fObj->lastavscan_time && Tinebase_DateTime::now()->subHour(12)
                                    ->isEarlier(new Tinebase_DateTime($fObj->lastavscan_time))) {
                                continue;
                            }

                            if ($fObj->hash !== ($hashDir . $file)) {
                                Tinebase_Exception::log(new Tinebase_Exception_UnexpectedValue(
                                    'file objects hash not as expected: ' . $fObj->getId() . ' rev: ' . $fObj->revision
                                    . ' hash: ' . $fObj->hash . ' expected hash: ' . $hashDir . $file));
                                $result = false;
                                continue;
                            }
                            if (null === $scanResult) {
                                if (false === ($fh = fopen($path, 'r'))) {
                                    Tinebase_Exception::log(new Tinebase_Exception_UnexpectedValue(
                                        'could not open file ' . $path . ' for reading... skipping'));
                                    $result = false;
                                    $fh = null;
                                    continue 3;
                                }

                                $scanResult = $avScanner->scan($fh);
                                fclose($fh);
                                $fh = null;
                                if (Tinebase_FileSystem_AVScan_Result::RESULT_ERROR === $scanResult->result) {
                                    $result = false;
                                }
                            }

                            $fObj->lastavscan_time = Tinebase_DateTime::now();
                            $fObj->is_quarantined =
                                Tinebase_FileSystem_AVScan_Result::RESULT_FOUND === $scanResult->result;
                            $this->_fileObjectBackend->update($fObj);
                        }
                    }

                    $transManager->commitTransaction($transId);
                    $transId = null;
                } finally {
                    $this->_fileObjectBackend->setRevision(null);
                    if (null !== $transId) {
                        $transManager->rollBack();
                    }
                    if (null !== $fh) {
                        fclose($fh);
                        $fh = null;
                    }
                }
            }
            closedir($fileDir);
        }
        closedir($baseDir);

        // only for unused variable check
        unset($raii);

        return $result;
    }

    /**
     * @return bool
     */
    public function notifyQuota()
    {
        $treeNodeBackend = $this->_getTreeNodeBackend();

        $quotaConfig = Tinebase_Config::getInstance()->{Tinebase_Config::QUOTA};
        $quotaIncludesRevisions = $quotaConfig->{Tinebase_Config::QUOTA_INCLUDE_REVISION};
        $total = $quotaConfig->{Tinebase_Config::QUOTA_TOTALINMB} * 1024 * 1024;
        $softQuota = $quotaConfig->{Tinebase_Config::QUOTA_SOFT_QUOTA};
        $this->_quotaNotificationRoleMembers = array();

        if (!empty($notificationRole = $quotaConfig->{Tinebase_Config::QUOTA_SQ_NOTIFICATION_ROLE})) {
            try {
                $role = Tinebase_Role::getInstance()->getRoleByName($notificationRole);
                $this->_quotaNotificationRoleMembers =
                    Tinebase_Role::getInstance()->getRoleMembersAccounts($role->getId());
            } catch (Tinebase_Exception_NotFound $tenf) {}
        }

        if ($total > 0) {
            /** @var Tinebase_Model_Application $tinebaseApplication */
            $tinebaseApplication = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');

            if ($quotaIncludesRevisions) {
                $totalUsage = intval(Tinebase_Application::getInstance()->getApplicationState($tinebaseApplication,
                    Tinebase_Application::STATE_FILESYSTEM_ROOT_REVISION_SIZE));
            } else {
                $totalUsage = intval(Tinebase_Application::getInstance()->getApplicationState($tinebaseApplication,
                    Tinebase_Application::STATE_FILESYSTEM_ROOT_SIZE));
            }

            if ($totalUsage >= ($total * 0.99)) {
                $this->_sendQuotaNotification(null, false);
            } elseif($softQuota > 0 && $totalUsage > ($total * $softQuota / 100)) {
                $this->_sendQuotaNotification();
            }
        }

        $totalByUser = $quotaConfig->{Tinebase_Config::QUOTA_TOTALBYUSERINMB} * 1024 * 1024;
        $personalNode = null;
        $notifiedNodes = [];
        if ($totalByUser > 0 && Tinebase_Application::getInstance()->isInstalled('Filemanager')) {
            $personalNode = $this->stat('/Filemanager/folders/personal');
            /** @var Tinebase_Model_Tree_Node $node */
            foreach ($treeNodeBackend->search(new Tinebase_Model_Tree_Node_Filter(array(
                        array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER),
                        array('field' => 'parent_id', 'operator' => 'quals', 'value' => $personalNode->getId())
                    ), '', array('ignoreAcl' => true))) as $node) {
                if ($quotaIncludesRevisions) {
                    $size = $node->revision_size;
                } else {
                    $size = $node->size;
                }
                if ($size >= ($totalByUser * 0.99)) {
                    $this->_sendQuotaNotification($node, false);
                    $notifiedNodes[$node->getId()] = true;
                } elseif($softQuota > 0 && $size > ($totalByUser * $softQuota / 100)) {
                    $this->_sendQuotaNotification($node);
                    $notifiedNodes[$node->getId()] = true;
                }

                Tinebase_Lock::keepLocksAlive();
            }
        }

        foreach ($treeNodeBackend->search(new Tinebase_Model_Tree_Node_Filter(array(
                    array('field' => 'type', 'operator' => 'equals', 'value' => Tinebase_Model_Tree_FileObject::TYPE_FOLDER),
                    array('field' => 'quota', 'operator' => 'greater', 'value' => 0)
                ), '', array('ignoreAcl' => true))) as $node) {
            if (isset($notifiedNodes[$node->getId()])) {
                continue;
            }
            if ($quotaIncludesRevisions) {
                $size = $node->revision_size;
            } else {
                $size = $node->size;
            }
            if ($size >= ($node->quota * 0.99)) {
                $this->_sendQuotaNotification($node, false);
            } elseif($softQuota > 0 && $size > ($node->quota * $softQuota / 100)) {
                $this->_sendQuotaNotification($node);
            }

            Tinebase_Lock::keepLocksAlive();
        }

        $this->_notifyImapQuota($quotaConfig);

        return true;
    }

    protected function _notifyImapQuota($quotaConfig)
    {
        if ($quotaConfig->{Tinebase_Config::QUOTA_SKIP_IMAP_QUOTA}) {
            return true;
        }

        if (! Tinebase_EmailUser::manages(Tinebase_Config::IMAP)) {
            return true;
        }

        /** @var Tinebase_EmailUser_Imap_Dovecot $imapBackend */
        $imapBackend = null;
        try {
            $imapBackend = Tinebase_EmailUser::getInstance();
        } catch (Tinebase_Exception_NotFound $tenf) {
            return true;
        }

        if (!$imapBackend instanceof Tinebase_EmailUser_Imap_Dovecot) {
            return true;
        }

        $softQuota = $quotaConfig->{Tinebase_Config::QUOTA_SOFT_QUOTA};

        /** @var Tinebase_Model_EmailUser $emailUser */
        foreach ($imapBackend->getAllEmailUsers() as $emailUser) {
            if ($emailUser->emailMailQuota < 1) {
                continue;
            }
            $alert = false;
            $softAlert = false;
            if ($emailUser->emailMailSize >= ($emailUser->emailMailQuota * 0.99)) {
                $alert = true;
            } elseif ($softQuota > 0 && $emailUser->emailMailSize > ($emailUser->emailMailQuota * $softQuota / 100)) {
                $alert = true;
                $softAlert = true;
            }

            if (true === $alert) {
                /** @var Tinebase_Model_FullUser $user */
                foreach (Tinebase_User::getInstance()->getMultiple(array_unique(array_merge(
                    $this->_quotaNotificationRoleMembers, array($emailUser->emailUserId)))) as $user) {
                    $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE,
                        $user->accountId));
                    $translate = Tinebase_Translation::getTranslation('Filemanager', $locale);

                    // _('email quota notification')
                    // _('email soft quota notification')
                    $translatedSubject = $translate->_('email ' . ($softAlert ? 'soft ' : '') . 'quota notification');

                    Tinebase_Notification::getInstance()->send($user, array($user->contact_id), $translatedSubject,
                        $emailUser->emailUsername . ' exceeded email ' . ($softAlert ? 'soft ' : '') . 'quota');
                }
            }

            Tinebase_Lock::keepLocksAlive();
        }
    }

    protected function _sendQuotaNotification(Tinebase_Model_Tree_Node $node = null, $softQuota = true)
    {
        try {
            $path = $this->getPathOfNode($node, true);
            if (null === $node || null === $node->acl_node) {
                $accountIds = Tinebase_Group::getInstance()->getDefaultAdminGroup()->members;
            } else {
                $accountIds = array();
                $acl_node = $node;
                if ($node->acl_node !== $node->getId()) {
                    $acl_node = $this->get($node->acl_node);
                }
                /** @var Tinebase_Model_Grants $grants */
                foreach ($this->getGrantsOfContainer($acl_node, true) as $grants) {
                    if (!$grants->{Tinebase_Model_Grants::GRANT_ADMIN}) {
                        continue;
                    }
                    switch($grants->account_type) {
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                            $accountIds[] = $grants->account_id;
                            break;
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                            $accountIds = array_merge($accountIds, Tinebase_Group::getInstance()->getGroupMembers($grants->account_id));
                            break;
                        case Tinebase_Acl_Rights::ACCOUNT_TYPE_ROLE:
                            foreach (Tinebase_Role::getInstance()->getRoleMembers($grants->account_id) as $role) {
                                switch($role['account_type']) {
                                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_USER:
                                        $accountIds[] = $role['account_id'];
                                        break;
                                    case Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP:
                                        $accountIds = array_merge($accountIds, Tinebase_Group::getInstance()->getGroupMembers($role['account_id']));
                                        break;
                                }
                            }
                            break;
                    }
                }
            }

            $accountIds = array_merge($accountIds, $this->_quotaNotificationRoleMembers);
            $accountIds = array_unique($accountIds);

            /** @var Tinebase_Model_FullUser $user */
            foreach (Tinebase_User::getInstance()->getMultiple($accountIds) as $user) {
                $locale = Tinebase_Translation::getLocale(Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::LOCALE,
                    $user->accountId));
                $translate = Tinebase_Translation::getTranslation('Filemanager', $locale);

                // _('filemanager quota notification')
                // _('filemanager soft quota notification')
                $translatedSubject = $translate->_('filemanager ' . ($softQuota ? 'soft ' : '') . 'quota notification');

                Tinebase_Notification::getInstance()->send($user, array($user->contact_id), $translatedSubject, $path . ' exceeded ' . ($softQuota ? 'soft ' : '') . 'quota');
            }
        } catch(Exception $e) {
            // LOG
        }
    }

    public function purgeDeletedNodes()
    {
        $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        try {

            $treeNodes = $this->_getTreeNodeBackend()->search(new Tinebase_Model_Tree_Node_Filter([
                ['field' => 'is_deleted', 'operator' => 'equals', 'value' => 1]
            ]));

            $counter = 0;
            while ($treeNodes->count() > 0 && ++$counter < 1000) {
                $data = $treeNodes->getClone(true);
                /** @var Tinebase_Model_Tree_Node $node */
                foreach ($data as $node) {
                    if ($treeNodes->find('parent_id', $node->getId()) !== null) {
                        continue;
                    }
                    $treeNodes->removeById($node->getId());

                    $this->_treeNodeBackend->delete($node->getId());

                    if ($this->_treeNodeBackend->getObjectCount($node->object_id) == 0) {
                        $this->_fileObjectBackend->delete($node->object_id);
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
     * checks if a records with identifiers $_ids exists, returns array of identifiers found
     *
     * @param array $_ids
     * @param bool $_getDeleted
     * @return array
     */
    public function has(array $_ids, $_getDeleted = false)
    {
        return $this->_getTreeNodeBackend()->has($_ids, $_getDeleted);
    }

    public function repairTreeIsDeletedState()
    {
        $fileobjectsTableName = SQL_TABLE_PREFIX . 'tree_fileobjects';
        $nodesTableName = SQL_TABLE_PREFIX . 'tree_nodes';
        $db = Tinebase_Core::getDb();
        $ids = $db->query('SELECT id FROM ' . $nodesTableName . ' WHERE is_deleted = 0 and deleted_time != "1970-01-01 00:00:00"')
            ->fetchAll(Zend_Db::FETCH_COLUMN, 0);

        if (count($ids)) {
            Tinebase_Exception::log(new Exception('found ' . count($ids) . ' treenodes with broken deltime: ' .
                print_r($ids, true)));

            $db->query('update ' . $nodesTableName . ' set deleted_time = "1970-01-01 00:00:00" where is_deleted = 0 and deleted_time != "1970-01-01 00:00:00"');
        }

        $ids = $db->query('SELECT id FROM ' . $fileobjectsTableName . ' WHERE is_deleted = 0 and deleted_time != "1970-01-01 00:00:00"')
            ->fetchAll(Zend_Db::FETCH_COLUMN, 0);

        if (count($ids)) {
            Tinebase_Exception::log(new Exception('found ' . count($ids) . ' fileobjects with broken deltime: ' .
                print_r($ids, true)));

            $db->query('update ' . $fileobjectsTableName . ' set deleted_time = "1970-01-01 00:00:00" where is_deleted = 0 and deleted_time != "1970-01-01 00:00:00"');
        }

        $ids = $db->query('SELECT F.id FROM ' . $fileobjectsTableName . ' as F join ' . $nodesTableName
            . ' as N ON F.id = N.object_id AND F.is_deleted != N.is_deleted')
            ->fetchAll(Zend_Db::FETCH_COLUMN, 0);

        if (count($ids)) {
            Tinebase_Exception::log(new Exception('found ' . count($ids) .
                ' fileobjects is_deleted differ from treenodes: ' . print_r($ids, true)));

            $db->query('update ' . $fileobjectsTableName . ' as F join ' . $nodesTableName
                . ' as N ON F.id = N.object_id AND F.is_deleted != N.is_deleted set F.deleted_time = N.deleted_time, F.is_deleted = N.is_deleted');
        }

        $ids = $db->query('SELECT N.id FROM ' . $nodesTableName . ' as N join ' . $nodesTableName
            . ' as P ON N.parent_id = P.id AND N.is_deleted = 0 AND P.is_deleted = 1')
            ->fetchAll(Zend_Db::FETCH_COLUMN, 0);

        if (count($ids)) {
            Tinebase_Exception::log(new Exception('found ' . count($ids) .
                ' treenodes are not deleted but parent is: ' . print_r($ids, true)));

            $db->query('update ' . $fileobjectsTableName . ' as F join ' . $nodesTableName
                . ' as N ON F.id = N.object_id set F.deleted_time = NOW(), N.deleted_time = NOW(), F.is_deleted = 1, N.is_deleted = 1 WHERE '
                . $db->quoteInto('N.id IN (?)', $ids));
        }

        return true;
    }
}
