<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  FileSystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2014 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * folder name/type for record attachments
     * 
     * @var string
     */
    const FOLDER_TYPE_RECORDS = 'records';
    
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
        
        if (! Setup_Controller::getInstance()->isFilesystemAvailable()) {
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
        $application = $_application instanceof Tinebase_Model_Application 
            ? $_application 
            : Tinebase_Application::getInstance()->getApplicationById($_application);
        
        $result = '/' . $application->getId();
        
        if ($_type !== NULL) {
            if (! in_array($_type, array(Tinebase_Model_Container::TYPE_SHARED, Tinebase_Model_Container::TYPE_PERSONAL, self::FOLDER_TYPE_RECORDS))) {
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
     * @return Tinebase_Model_Tree_Node
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
     * @param Tinebase_Model_Container $container
     */
    public function createContainerNode(Tinebase_Model_Container $container)
    {
        $path = $this->getContainerPath($container);
        
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
     * @param string $path if given, only remove this path from statcache
     */
    public function clearStatCache($path = NULL)
    {
        if ($path !== NULL) {
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
        $destinationNode = $this->stat($sourcePath);
        $sourcePathParts = $this->_splitPath($sourcePath);
        
        try {
            // does destinationPath exist ...
            $parentNode = $this->stat($destinationPath);
            
            // ... and is a directory?
            if (! $parentNode->type == Tinebase_Model_Tree_Node::TYPE_FOLDER) {
                throw new Tinebase_Exception_UnexpectedValue("Destination path exists and is a file. Please remove before.");
            }
            
            $destinationNodeName  = basename(trim($sourcePath, '/'));
            $destinationPathParts = array_merge($this->_splitPath($destinationPath), (array)$destinationNodeName);

        } catch (Tinebase_Exception_NotFound $tenf) {
            // does parent directory of destinationPath exist?
            try {
                $parentNode = $this->stat(dirname($destinationPath));
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Tinebase_Exception_UnexpectedValue("Parent directory does not exist. Please create before.");
            }
            
            $destinationNodeName = basename(trim($destinationPath, '/'));
            $destinationPathParts = array_merge($this->_splitPath(dirname($destinationPath)), (array)$destinationNodeName);
        }
        
        if ($sourcePathParts == $destinationPathParts) {
            throw new Tinebase_Exception_UnexpectedValue("Source path and destination path must be different.");
        }
        
        // set new node properties
        $destinationNode->setId(null);
        $destinationNode->parent_id = $parentNode->getId();
        $destinationNode->name      = $destinationNodeName;
        
        $createdNode = $this->_treeNodeBackend->create($destinationNode);
        
        // update hash of all parent folders
        $this->_updateDirectoryNodesHash(dirname(implode('/', $destinationPathParts)));
        
        return $createdNode;
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
     * @return boolean true if file/directory exists
     */
    public function fileExists($path) 
    {
        try {
            $this->stat($path);
        } catch (Tinebase_Exception_NotFound $tenf) {
            return false;
        }
        
        return true;
    }
    
    /**
     * close file handle
     * 
     * @param  handle $handle
     * @return boolean
     */
    public function fclose($handle)
    {
        if (!is_resource($handle)) {
            return false;
        }
        
        $options = stream_context_get_options($handle);
        
        switch ($options['tine20']['mode']) {
            case 'w':
            case 'wb':
            case 'x':
            case 'xb':
                list ($hash, $hashFile) = $this->createFileBlob($handle);
                
                $this->_updateFileObject($options['tine20']['node']->object_id, $hash, $hashFile);
                
                $this->clearStatCache($options['tine20']['path']);
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Writing to file : ' . $options['tine20']['path'] . ' successful.');
                
                break;
                
            default:
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got mode : ' . $options['tine20']['mode'] . ' - nothing to do.');
        }
        
        fclose($handle);
        
        // update hash of all parent folders
        $this->_updateDirectoryNodesHash(dirname($options['tine20']['path']));
        
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
    protected function _updateFileObject($_id, $_hash, $_hashFile = null)
    {
        $currentFileObject = $_id instanceof Tinebase_Record_Abstract ? $_id : $this->_fileObjectBackend->get($_id);
        
        $_hashFile = $_hashFile ?: ($this->_basePath . '/' . substr($_hash, 0, 3) . '/' . substr($_hash, 3));
        
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
        foreach ($parentNodes as $node) {
            $directoryObject = $updatedNodes->getById($node->object_id);
            
            if ($directoryObject) {
                $node->revision = $directoryObject->revision;
                $node->hash     = $directoryObject->hash;
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
                
                $handle = Tinebase_TempFile::getInstance()->openTempFile();
                
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
                
                $handle = Tinebase_TempFile::getInstance()->openTempFile();
                
                break;
                
            default:
                return false;
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
        
        if ($node->type != Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
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
     * @return Tinebase_Model_Tree_Node
     */
    public function rename($oldPath, $newPath)
    {
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
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                return false;
            } catch (Tinebase_Exception_NotFound $tenf) {
                return false;
            }
    
            $node->parent_id = $newParent->getId();
        }
    
        if (basename($oldPath) != basename($newPath)) {
            $node->name = basename($newPath);
        }
    
        $node = $this->_treeNodeBackend->update($node);
        
        $this->clearStatCache($oldPath);
        
        $this->_addStatCache($newPath, $node);
        
        return $node;
    }
    
    /**
     * create directory
     * 
     * @param string $path
     */
    public function mkdir($path)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Creating directory ' . $path);
        
        $currentPath = array();
        $parentNode  = null;
        $pathParts   = $this->_splitPath($path);
        
        foreach ($pathParts as $pathPart) {
            $pathPart = trim($pathPart);
            $currentPath[]= $pathPart;
            
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
        
        return $node;
    }
    
    /**
     * remove directory
     * 
     * @param  string   $path
     * @param  boolean  $recursive
     * @return boolean
     */
    public function rmdir($path, $recursive = FALSE)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Removing directory ' . $path);
        
        $node = $this->stat($path);
        
        $children = $this->getTreeNodeChildren($node);
        
        // check if child entries exists and delete if $_recursive is true
        if (count($children) > 0) {
            if ($recursive !== true) {
                throw new Tinebase_Exception_InvalidArgument('directory not empty');
            } else {
                foreach ($children as $child) {
                    if ($this->isDir($path . '/' . $child->name)) {
                        $this->rmdir($path . '/' . $child->name, true);
                    } else {
                        $this->unlink($path . '/' . $child->name);
                    }
                }
            }
        }
        
        $this->_treeNodeBackend->delete($node->getId());
        $this->clearStatCache($path);

        // delete object only, if no other tree node refers to it
        if ($this->_treeNodeBackend->getObjectCount($node->object_id) == 0) {
            $this->_fileObjectBackend->delete($node->object_id);
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
        $children = $this->getTreeNodeChildren($this->stat($path));
        
        foreach ($children as $node) {
            $this->_addStatCache($path . '/' . $node->name, $node);
        }
        
        return $children;
    }
    
    /**
     * @param  string  $path
     * @return Tinebase_Model_Tree_Node
     */
    public function stat($path)
    {
        $pathParts = $this->_splitPath($path);
        
        $cacheId = $this->_getCacheId($pathParts);
        
        // let's see if the path is cached in statCache
        if ((isset($this->_statCache[$cacheId]) || array_key_exists($cacheId, $this->_statCache))) {
            try {
                // let's try to get the node from backend, to make sure it still exists
                return $this->_treeNodeBackend->get($this->_statCache[$cacheId]);
            } catch (Tinebase_Exception_NotFound $tenf) {
                // something went wrong. let's clear the whole statCache
                $this->clearStatCache();
            }
        }
        
        $parentNode = null;
        $node       = null;
        
        // find out if we have cached any node up in the path
        while ($pathPart = array_pop($pathParts)) {
            $cacheId = $this->_getCacheId($pathParts);
            
            if ((isset($this->_statCache[$cacheId]) || array_key_exists($cacheId, $this->_statCache))) {
                $parentNode = $this->_statCache[$cacheId];
                break;
            }
        }
        
        $missingPathParts = array_diff($this->_splitPath($path), $pathParts);
        
        foreach ($missingPathParts as $pathPart) {
            $node = $this->_treeNodeBackend->getChild($parentNode, $pathPart);
            
            // keep track of current path posistion
            array_push($pathParts, $pathPart);
            
            // add found path to statCache
            $this->_addStatCache($pathParts, $node);
            
            $parentNode = $node;
        }
        
        return $node;
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
     * @param  string  $_path
     * @return boolean
     */
    public function unlink($path)
    {
        $node = $this->stat($path);
        $this->deleteFileNode($node);
        
        $this->clearStatCache($path);
        
        // update hash of all parent folders
        $this->_updateDirectoryNodesHash(dirname($path));
        
        return true;
    }
    
    /**
     * delete file node
     * 
     * @param Tinebase_Model_Tree_Node $node
     */
    public function deleteFileNode(Tinebase_Model_Tree_Node $node)
    {
        if ($node->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            throw new Tinebase_Exception_InvalidArgument('can not unlink directories');
        }
        
        $this->_treeNodeBackend->delete($node->getId());
        
        // delete object only, if no one uses it anymore
        if ($this->_treeNodeBackend->getObjectCount($node->object_id) == 0) {
            $this->_fileObjectBackend->delete($node->object_id);
        }
    }
    
    /**
     * create directory
     * 
     * @param  string|Tinebase_Model_Tree_Node  $parentId
     * @param  string                           $name
     * @return Tinebase_Model_Tree_Node
     */
    public function createDirectoryTreeNode($parentId, $name)
    {
        $parentId = $parentId instanceof Tinebase_Model_Tree_Node ? $parentId->getId() : $parentId;
        
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
            'parent_id'     => $parentId
        ));
        $treeNode = $this->_treeNodeBackend->create($treeNode);
        
        return $treeNode;
    }
    
    /**
     * create new file node
     * 
     * @param  string|Tinebase_Model_Tree_Node  $parentId
     * @param  string                           $name
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    public function createFileTreeNode($parentId, $name)
    {
        $parentId = $parentId instanceof Tinebase_Model_Tree_Node ? $parentId->getId() : $parentId;
        
        $fileObject = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FILE,
            'contentytype'  => null,
        ));
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($fileObject, 'create');
        $fileObject = $this->_fileObjectBackend->create($fileObject);
        
        $treeNode = new Tinebase_Model_Tree_Node(array(
            'name'          => $name,
            'object_id'     => $fileObject->getId(),
            'parent_id'     => $parentId
        ));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .
            ' ' . print_r($treeNode->toArray(), TRUE));
        
        $treeNode = $this->_treeNodeBackend->create($treeNode);
        
        return $treeNode;
    }
    
    /**
     * places contents into a file blob
     * 
     * @param  stream|string|tempFile $contents
     * @return string hash
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
            $nodeId = $_nodeId;
            $operator = 'equals';
        }
        
        $searchFilter = new Tinebase_Model_Tree_Node_Filter(array(
            array(
                'field'     => 'parent_id',
                'operator'  => $operator,
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
     * get nodes by container (or container id)
     * 
     * @param int|Tinebase_Model_Container $container
     * @return Tinebase_Record_RecordSet
     */
    public function getNodesByContainer($container)
    {
        $nodeContainer = ($container instanceof Tinebase_Model_Container) ? $container : Tinebase_Container::getInstance()->getContainerById($container);
        $path = $this->getContainerPath($nodeContainer);
        $parentNode = $this->stat($path);
        $filter = new Tinebase_Model_Tree_Node_Filter(array(
            array('field' => 'parent_id', 'operator' => 'equals', 'value' => $parentNode->getId())
        ));
        
        return $this->searchNodes($filter);
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
        
        return $this->_treeNodeBackend->getChild($_parentId, $_name);
    }
    
    /**
     * add entry to stat cache
     * 
     * @param string|array              $path
     * @param Tinebase_Model_Tree_Node  $node
     */
    protected function _addStatCache($path, Tinebase_Model_Tree_Node $node)
    {
        $this->_statCache[$this->_getCacheId($path)] = $node;
    }
    
    /**
     * generate cache id
     * 
     * @param  string|array  $path
     * @return string
     */
    protected function _getCacheId($path) 
    {
        $pathParts = is_array($path) ? $path : $this->_splitPath($path);
        
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
        $currentNodeObject = $this->get($_node->getId());
        Tinebase_Timemachine_ModificationLog::setRecordMetaData($_node, 'update', $currentNodeObject);
        
        // update file object
        $fileObject = $this->_fileObjectBackend->get($currentNodeObject->object_id);
        $fileObject->description = $_node->description;
        
        $this->_updateFileObject($fileObject, $_node->hash);
        
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
            
            $pathNodes->addRecord($this->stat($subPath));
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
     * 
     * @return integer number of deleted files
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
        $toDeleteIds = array();
        $fileObjects = $this->_fileObjectBackend->getAll();
        foreach ($fileObjects as $fileObject) {
            if ($fileObject->type == Tinebase_Model_Tree_FileObject::TYPE_FILE && $fileObject->hash && ! file_exists($fileObject->getFilesystemPath())) {
                $toDeleteIds[] = $fileObject->getId();
            }
        }
        
        $nodeIdsToDelete = $this->_treeNodeBackend->search(new Tinebase_Model_Tree_Node_Filter(array(array(
            'field'     => 'object_id',
            'operator'  => 'in',
            'value'     => $toDeleteIds
        ))), NULL, Tinebase_Backend_Sql_Abstract::IDCOL);
        
        $deleteCount = $this->_treeNodeBackend->delete($nodeIdsToDelete);
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Removed ' . $deleteCount . ' obsolete filenode(s) from the database.');
        
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
         NULL                         create empty file
     * @param  string  $path
     * @throws Tinebase_Exception_AccessDenied
     */
    public function copyTempfile($tempFile, $path)
    {
        if ($tempFile === NULL) {
            $tempStream = fopen('php://memory', 'r');
        }
        
        else if (is_resource($tempFile)) {
            $tempStream = $tempFile;
        }
        
        else if (is_string($tempFile) || is_array($tempFile)) {
            $tempFile = Tinebase_TempFile::getInstance()->getTempFile($tempFile);
            return $this->copyTempfile($tempFile, $path);
        }
        
        else if ($tempFile instanceof Tinebase_Model_Tree_Node) {
            if (isset($tempFile->hash)) {
                $hashFile = $this->_basePath . '/' . substr($tempFile->hash, 0, 3) . '/' . substr($tempFile->hash, 3);
                $tempStream = fopen($hashFile, 'r');
            } else if (is_resource($tempFile->stream)) {
                $tempStream = $tempFile->stream;
            } else {
                return $this->copyTempfile($tempFile->tempFile, $path);
            }
        }
        
        else if ($tempFile instanceof Tinebase_Model_TempFile) {
            $tempStream = fopen($tempFile->path, 'r');
        }
        
        else {
            throw new Tasks_Exception_UnexpectedValue('unexpected tempfile value');
        }
        
        return $this->copyStream($tempStream, $path);
    }
    
    /**
     * copy stream data to file path
     *
     * @param  stream  $in
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
        
        if (is_resource($in) !== NULL) {
            rewind($in);
            stream_copy_to_stream($in, $handle);
            
            $this->clearStatCache($path);
        }
        
        $this->fclose($handle);
    }
}
