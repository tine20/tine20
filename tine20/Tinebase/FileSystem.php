<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo 0007376: Tinebase_FileSystem / Node model refactoring: move all container related functionality to Filemanager
 */

/**
 * filesystem controller
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 */
class Tinebase_FileSystem implements Tinebase_Controller_Interface
{
    /**
     * @var Tinebase_Tree_FileObject
     */
    protected $_fileObjectBackend;
    
    /**
     * @var Tinebase_Tree_Node
     */
    protected $_treeNodeBackend;
    
    /**
     * path where physical files gets stored
     * 
     * @var string
     */
    protected $_basePath;
    
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
    private static $_instance = NULL;
    
    /**
     * the constructor
     */
    public function __construct() 
    {
        $this->_fileObjectBackend  = new Tinebase_Tree_FileObject();
        $this->_treeNodeBackend    = new Tinebase_Tree_Node();
        
        if (empty(Tinebase_Core::getConfig()->filesdir) || !is_writeable(Tinebase_Core::getConfig()->filesdir)) {
            throw new Tinebase_Exception_Backend('No base path (filesdir) configured or path not writeable');
        }
        
        $this->_basePath = Tinebase_Core::getConfig()->filesdir;
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_FileSystem
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Tinebase_FileSystem;
        }
        
        return self::$_instance;
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
        
        $sharedBasePath = $this->getApplicationBasePath($_application, Tinebase_Model_Container::TYPE_SHARED);
        if (!$this->fileExists($sharedBasePath)) {
            $this->mkdir($sharedBasePath);
        }
        
        $personalBasePath = $this->getApplicationBasePath($_application, Tinebase_Model_Container::TYPE_PERSONAL);
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
    public function getApplicationBasePath($_application, $_type = NULL)
    {
        $application = $_application instanceof Tinebase_Model_Application ? $_application : Tinebase_Application::getInstance()->getApplicationById($_application);
        
        $result = '/' . $application->getId();
        if ($_type !== NULL) {
            if (! in_array($_type, array(Tinebase_Model_Container::TYPE_SHARED, Tinebase_Model_Container::TYPE_PERSONAL))) {
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
     * @param $_getDeleted get deleted records
     * @return Tinebase_Record_Interface
     */
    public function get($_id, $_getDeleted = FALSE)
    {
        $node = $this->_treeNodeBackend->get($_id, $_getDeleted);
        $fileObject = $this->_fileObjectBackend->get($node->object_id);
        $node->description = $fileObject->description;
        
        return $node;
    }
    
    /**
     * Get multiple tree nodes identified by id
     *
     * @param string|array $_id Ids
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function getMultipleTreeNodes($_id) 
    {
        return $this->_treeNodeBackend->getMultiple($_id);
    }
    
    /**
     * create container node
     * 
     * @param Tinebase_Model_Container $_container
     */
    public function createContainerNode(Tinebase_Model_Container $_container)
    {
        $path = $this->getContainerPath($_container);
        if (!$this->fileExists($path)) {
            $this->mkdir($path);
        }
    }

    /**
     * get container path
     * 
     * @param Tinebase_Model_Container $container
     * @return string
     */
    public function getContainerPath(Tinebase_Model_Container $container)
    {
        $path = $this->getApplicationBasePath($container->application_id, $container->type) . '/' . $container->getId();
        
        return $path;
    }
    
    /**
     * clear stat cache
     * 
     * @param string $_path if given, only remove this path from statcache
     */
    public function clearStatCache($_path = NULL)
    {
        if ($_path !== NULL) {
            unset($this->_statCache[$_path]);
        } else {
            // clear the whole cache
            $this->_statCache = array();
        }
    }
    
    /**
     * get modification timestamp
     * 
     * @param string $_path
     * @return string  UNIX timestamp
     */
    public function getMTime($_path)
    {
        $node = $this->stat($_path);
        
        $timestamp = $node->last_modified_time instanceof Tinebase_DateTime ? $node->last_modified_time->getTimestamp() : $node->creation_time->getTimestamp();
        
        return $timestamp;
    }
    
    /**
     * check if file exists
     * 
     * @param string $_path
     * @return boolean
     */
    public function fileExists($_path) 
    {
        return $this->_treeNodeBackend->pathExists($_path);
    }
    
    /**
     * close file handle
     * 
     * @param handle $_handle
     * @return boolean
     */
    public function fclose($_handle)
    {
        if (!is_resource($_handle)) {
            return false;
        }
        
        $options = stream_context_get_options($_handle);
        
        switch ($options['tine20']['mode']) {
            case 'w':
            case 'wb':
            case 'x':
            case 'xb':
                rewind($_handle);
                
                $ctx = hash_init('sha1');
                hash_update_stream($ctx, $_handle);
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
                    rewind($_handle);
                    $hashHandle = fopen($hashFile, 'x');
                    stream_copy_to_stream($_handle, $hashHandle);
                    fclose($hashHandle);
                }
                
                $this->_updateFileObject($options['tine20']['node']->object_id, $hash, $hashFile);
                
                $this->clearStatCache($options['tine20']['path']);
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Writing to file : ' . $options['tine20']['path'] . ' successful.');
                
                break;
                
            default:
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got mode : ' . $options['tine20']['mode'] . ' - nothing to do.');
        }
        
        fclose($_handle);
        
        return true;
    }
    
    /**
     * update file object with hash file info
     * 
     * @param string $_id
     * @param string $_hash
     * @param string $_hashFile
     * @return Tinebase_Model_Tree_FileObject
     */
    protected function _updateFileObject($_id, $_hash, $_hashFile)
    {
        $currentFileObject = $this->_fileObjectBackend->get($_id);
        $updatedFileObject = clone($currentFileObject);
        $updatedFileObject->hash = $_hash;
        $updatedFileObject->size = filesize($_hashFile);
        if (version_compare(PHP_VERSION, '5.3.0', '>=') && function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_hashFile);
            if ($mimeType !== false) {
                $updatedFileObject->contenttype = $mimeType;
            }
            finfo_close($finfo);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' finfo_open() is not available: Could not get file information.');
        }
        
        $modLog = Tinebase_Timemachine_ModificationLog::getInstance();
        $modLog->setRecordMetaData($updatedFileObject, 'update', $currentFileObject);
        
        // sanitize file size, somehow filesize() seems to return empty strings on some systems
        if (empty($updatedFileObject->size)) {
            $updatedFileObject->size = 0;
        }
        
        return $this->_fileObjectBackend->update($updatedFileObject);
    }
    
    /**
     * open file
     * 
     * @param string $_path
     * @param string $_mode
     * @return handle
     */
    public function fopen($_path, $_mode)
    {
        $dirName = dirname($_path);
        $fileName = basename($_path);
        
        switch ($_mode) {
            // Create and open for writing only; place the file pointer at the beginning of the file. 
            // If the file already exists, the fopen() call will fail by returning FALSE and generating 
            // an error of level E_WARNING. If the file does not exist, attempt to create it. This is 
            // equivalent to specifying O_EXCL|O_CREAT flags for the underlying open(2) system call.
            case 'x':
            case 'xb':
                if (!$this->isDir($dirName) || $this->fileExists($_path)) {
                    return false;
                }
                
                $parent = $this->stat($dirName);
                $node = $this->createFileTreeNode($parent, $fileName);
                
                $handle = tmpfile();
                
                break;
                
            // Open for reading only; place the file pointer at the beginning of the file.
            case 'r':
            case 'rb':
                if ($this->isDir($_path) || !$this->fileExists($_path)) {
                    return false;
                }
                
                $node = $this->stat($_path);
                $hashFile = $this->_basePath . '/' . substr($node->hash, 0, 3) . '/' . substr($node->hash, 3);
                
                $handle = fopen($hashFile, $_mode);
                
                break;
                
            // Open for writing only; place the file pointer at the beginning of the file and truncate the 
            // file to zero length. If the file does not exist, attempt to create it.
            case 'w':
            case 'wb':
                if (!$this->isDir($dirName)) {
                    return false;
                }
                
                if (!$this->fileExists($_path)) {
                    $parent = $this->stat($dirName);
                    $node = $this->createFileTreeNode($parent, $fileName);
                } else {
                    $node = $this->stat($_path);
                }
                
                $handle = tmpfile();
                
                break;
                
            default:
                return false;
        }
        
        stream_context_set_option ($handle, 'tine20', 'path', $_path);
        stream_context_set_option ($handle, 'tine20', 'mode', $_mode);
        stream_context_set_option ($handle, 'tine20', 'node', $node);
        
        return $handle;
    }
    
    /**
     * get content type
     * 
     * @deprecated use Tinebase_FileSystem::stat()->contenttype
     * @param string $_path
     * @return string
     */
    public function getContentType($_path)
    {
        $node = $this->stat($_path);
        
        return $node->contenttype;
    }
    
    /**
     * get etag
     * 
     * @deprecated use Tinebase_FileSystem::stat()->hash
     * @param string $_path
     * @return string
     */
    public function getETag($_path)
    {
        $node = $this->stat($_path);
        
        return $node->hash;
    }
    
    /**
     * return if path is directory
     * 
     * @param  string  $_path
     * @return boolean
     */
    public function isDir($_path)
    {
        try {
            $node = $this->stat($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        
        if ($node->type != Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            return false;
        }
        
        return true;
    }
    
    /**
     * return if path is directory
     *
     * @param  string  $_path
     * @return boolean
     */
    public function isFile($_path)
    {
        try {
            $node = $this->stat($_path);
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
     * @param  string  $_oldPath
     * @param  string  $_newPath
     * @return boolean
     */
    public function rename($_oldPath, $_newPath)
    {
        try {
            $node = $this->stat($_oldPath);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
    
        if (dirname($_oldPath) != dirname($_newPath)) {
            try {
                $newParent = $this->_treeNodeBackend->getLastPathNode(dirname($_newPath));
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                return false;
            } catch (Tinebase_Exception_NotFound $tenf) {
                return false;
            }
    
            $node->parent_id = $newParent->getId();
        }
    
        if (basename($_oldPath) != basename($_newPath)) {
            $node->name = basename($_newPath);
        }
    
        $this->_treeNodeBackend->update($node);
    
        return true;
    }
    
    /**
     * create directory
     * 
     * @param string $_path
     */
    public function mkdir($_path)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Creating directory ' . $_path);
        
        $path = '/';
        $parentNode = null;
        $pathParts = $this->_splitPath($_path);
        
        foreach ($pathParts as $part) {
            $path .= '/' . $part;
            if (!$this->fileExists($path)) {
                $parentNode = $this->createDirectoryTreeNode($parentNode, trim($part));
            } else {
                $parentNode = $this->getTreeNode($parentNode, trim($part));
            }
        }
    }
    
    /**
     * remove directory
     * 
     * @param string $_path
     * @param boolean $_recursive
     * @return boolean
     */
    public function rmdir($_path, $_recursive = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Removing directory ' . $_path);
        
        $node = $this->stat($_path);
        
        $children = $this->_getTreeNodeChildren($node);
        
        // check if child entries exists and delete if $_recursive is true
        if (count($children) > 0) {
            if ($_recursive !== true) {
                throw new Tinebase_Exception_InvalidArgument('directory not empty');
            } else {
                foreach ($children as $child) {
                    if ($this->isDir($_path . '/' . $child->name)) {
                        $this->rmdir($_path . '/' . $child->name, true);
                    } else {
                        $this->unlink($_path . '/' . $child->name);
                    }
                }
            }
        }
        
        $this->_treeNodeBackend->delete($node->getId());
        $this->clearStatCache($_path);

        // delete object only, if no other tree node refers to it
        if ($this->_treeNodeBackend->getObjectCount($node->object_id) == 0) {
            $this->_fileObjectBackend->delete($node->object_id);
        }
        
        return true;
    }
    
    /**
     * scan dir
     * 
     * @param string $_path
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    public function scanDir($_path)
    {
        $node = $this->stat($_path);
        
        $children = $this->_getTreeNodeChildren($node);
        
        foreach ($children as $child) {
            $this->_statCache[$_path . '/' . $child->name] = $child;
        }
        
        return $children;
    }
    
    /**
     * @param  string  $_path
     * @return Tinebase_Model_Tree_Node
     */
    public function stat($_path)
    {
        if (isset($this->_statCache[$_path])) {
            $node = $this->_statCache[$_path];
        } else {
            $node = $this->_treeNodeBackend->getLastPathNode($_path);
            $this->_statCache[$_path] = $node;
        }
        
        return $node;
    }
    
    /**
     * get filesize
     * 
     * @deprecated use Tinebase_FileSystem::stat()->size
     * @param string $_path
     * @return integer
     */
    public function filesize($_path)
    {
        $node = $this->stat($_path);
        
        return $node->size;
    }
    
    /**
     * delete file
     * 
     * @param string $_path
     * @return boolean
     */
    public function unlink($_path)
    {
        $node = $this->stat($_path);
        $this->deleteFileNode($node);
        unset($this->_statCache[$_path]);
        
        return true;
    }
    
    /**
     * delete file node
     * 
     * @param Tinebase_Model_Tree_Node $_node
     */
    public function deleteFileNode(Tinebase_Model_Tree_Node $_node)
    {
        if ($_node->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            throw new Tinebase_Exception_InvalidArgument('can not unlink directories');
        }
        
        $this->_treeNodeBackend->delete($_node->getId());
        
        // delete object only, if no one uses it anymore
        if ($this->_treeNodeBackend->getObjectCount($_node->object_id) == 0) {
            $this->_fileObjectBackend->delete($_node->object_id);
        }
    }
    
    /**
     * create directory
     * 
     * @param string $_parentId
     * @param string $_name
     * @return Tinebase_Model_Tree_Node
     */
    public function createDirectoryTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        $directoryObject = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
            'contentytype'  => null,
        ));
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($directoryObject, 'create');
        $directoryObject = $this->_fileObjectBackend->create($directoryObject);
        
        $treeNode = new Tinebase_Model_Tree_Node(array(
            'name'          => $_name,
            'object_id'     => $directoryObject->getId(),
            'parent_id'     => $parentId
        ));
        $treeNode = $this->_treeNodeBackend->create($treeNode);
        
        return $treeNode;
    }
    
    /**
     * create new file node
     * 
     * @param string $_parentId
     * @param string $_name
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    public function createFileTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        $fileObject = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FILE,
            'contentytype'  => null,
        ));
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($fileObject, 'create');
        $fileObject = $this->_fileObjectBackend->create($fileObject);
        
        $treeNode = new Tinebase_Model_Tree_Node(array(
            'name'          => $_name,
            'object_id'     => $fileObject->getId(),
            'parent_id'     => $parentId
        ));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' ' . print_r($treeNode->toArray(), TRUE));
        
        $treeNode = $this->_treeNodeBackend->create($treeNode);
        
        return $treeNode;
    }
    
    /**
     * get tree node children
     * 
     * @param string $_nodeId
     * @return Tinebase_Record_RecordSet of Tinebase_Model_Tree_Node
     */
    protected function _getTreeNodeChildren($_nodeId)
    {
        $nodeId = $_nodeId instanceof Tinebase_Model_Tree_Node ? $_nodeId->getId() : $_nodeId;
        $children = array();
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => 'equals',
                'value'     => $nodeId
            )
        ));
        
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
    public function searchNodes(Tinebase_Model_Tree_Node_Filter $_filter = NULL, Tinebase_Record_Interface $_pagination = NULL)
    {
        $result = $this->_treeNodeBackend->search($_filter, $_pagination);
        return $result;
    }
    
    /**
    * search tree nodes count
    *
    * @param Tinebase_Model_Tree_Node_Filter $_filter
    * @return integer
    */
    public function searchNodesCount(Tinebase_Model_Tree_Node_Filter $_filter = NULL)
    {
        $result = $this->_treeNodeBackend->searchCount($_filter);
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
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'name',
                'operator'  => 'equals',
                'value'     => $_name
            ),
            array(
                'field'     => 'parent_id',
                'operator'  => $parentId === null ? 'isnull' : 'equals',
                'value'     => $parentId
            )
        ));
        
        $result = $this->_treeNodeBackend->search($searchFilter);
        
        if ($result->count() == 0) {
            throw new Tinebase_Exception_InvalidArgument('directory node not found');
        }
        
        return $result->getFirstRecord();
    }
    
    /**
     * split path
     * 
     * @param  string  $_path
     * @return array
     */
    protected function _splitPath($_path)
    {
        return explode('/', trim($_path, '/'));
    }
    
    /**
     * update node
     * 
     * @param Tinebase_Model_Tree_Node $_node
     * @return Tinebase_Model_Tree_Node
     */
    public function update(Tinebase_Model_Tree_Node $_node)
    {
        $currentNodeObject = $this->get($_node->getId());
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_node, 'update', $currentNodeObject);
        
        // update file object
        $fileObject = $this->_fileObjectBackend->get($currentNodeObject->object_id);
        $fileObject->description = $_node->description;
        $this->_fileObjectBackend->update($fileObject);
        
        return $this->_treeNodeBackend->update($_node);
    }
    
    /**
     * get container of node
     * 
     * @param Tinebase_Model_Tree_Node|string $node
     * @return Tinebase_Model_Container
     */
    public function getNodeContainer($node)
    {
        $nodesPath = $this->getPathOfNode($node);
        
        if (count($nodesPath) < 4) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . 
                ' ' . print_r($nodesPath[0], TRUE));
            throw new Tinebase_Exception_NotFound('Could not find container for node ' . $nodesPath[0]['id']);
        }
        
        $containerNode = ($nodesPath[2]['name'] === Tinebase_Model_Container::TYPE_PERSONAL) ? $nodesPath[4] : $nodesPath[3];
        return Tinebase_Container::getInstance()->get($containerNode['name']);
    }
    
    /**
     * get path of node
     * 
     * @param Tinebase_Model_Tree_Node|string $node
     * @param boolean $getPathAsString
     * @return array|string
     */
    public function getPathOfNode($node, $getPathAsString = FALSE)
    {
        $node = $node instanceof Tinebase_Model_Tree_Node ? $node : $this->get($node);
        
        $nodesPath = new Tinebase_Record_RecordSet('Tinebase_Model_Tree_Node', array($node));
        while ($node->parent_id) {
            $node = $this->get($node->parent_id);
            $nodesPath->addRecord($node);
        }
        
        $result = ($getPathAsString) ? '/' . implode('/', array_reverse($nodesPath->name)) : array_reverse($nodesPath->toArray());
        return $result;
    }
    
    /**
     * removes deleted files that no longer exist in the database from the filesystem
     * 
     * @return integer number of deleted files
     */
    public function clearDeletedFiles()
    {
        try {
            $dirIterator = new DirectoryIterator($this->_basePath);
        } catch (Exception $e) {
            throw new Tinebase_Exception_AccessDenied('Could not open files directory.');
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Scanning ' . $this->_basePath . ' for deleted files.');
        
        $deleteCount = 0;
        foreach ($dirIterator as $item) {
            $subDir = $item->getFileName();
            if ($subDir[0] == '.') continue;
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
                . ' Checking ' . $subDir);
            $subDirIterator = new DirectoryIterator($this->_basePath . '/' . $subDir);
            $hashsToCheck = array();
            // loop dirs + check if files in dir are in tree_filerevisions
            foreach ($subDirIterator as $file) {
                if ($file->isFile()) {
                    $hash = $subDir . $file->getFileName();
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
}
