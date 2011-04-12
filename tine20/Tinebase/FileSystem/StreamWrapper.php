<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * filesystem streamwrapper for tine20://
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_Filesystem_StreamWrapper
{
    public $context;
    
    /**
     * @var Tinebase_Tree_FileObject
     */
    protected $_objectBackend;
    
    /**
     * @var Tinebase_Tree_Node
     */
    protected $_treeBackend;
    
    /**
     * path where to store physical files
     * 
     * @var string
     */
    protected $_physicalBasePath;
    
    /**
     * stores the current treenode
     * 
     * @var Tinebase_Model_Tree_FileObject
     */
    protected $_currentNode;
    
    /**
     * stores the list of directory children for readdir
     * 
     * @var Tinebase_Record_RecordTest
     */
    protected $_readDirRecordSet;
    
    /**
     * @var resource
     */
    protected $_stream;
    
    /**
     * @var Tinebase_FileSystem
     */
    protected $_tinebaseFileSystem;

    public function dir_closedir() 
    {
        $this->_currentNode      = null;
        $this->_readDirRecordSet = null;
        $this->_readDirIterator  = null;
        
        return true;
    }
    
    public function dir_opendir($_path, $_options) 
    {
        try {
            $path = $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            trigger_error("invalid path provided", E_USER_WARNING);
            return false;
        }
        
        try {
            $node = $this->_getTreeNodeBackend()->getLastPathNode($path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (!$quiet) {
                trigger_error('path not found', E_USER_WARNING);
            }
            return false;
        }
        
        if ($node->type != Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            trigger_error("$_path isn't a directory", E_USER_WARNING);
            return false;
        }
        
        $this->_currentNode = $node;
        
        return true;
    }
    
    public function dir_readdir() 
    {
        if ($this->_readDirRecordSet === null) {
            $this->_readDirRecordSet = $this->_getTreeNodeBackend()->getChildren($this->_currentNode);
            $this->_readDirIterator  = $this->_readDirRecordSet->getIterator();
            reset($this->_readDirIterator);
        }
        
        if (($node = current($this->_readDirIterator)) === false) {
            return false;
        }
        next($this->_readDirIterator);
        
        return $node->name;
    }
    
    public function dir_rewinddir()
    {
        reset($this->_readDirIterator);
    }
    
    /**
     * create directory
     * 
     * @param  string  $_path     path to create
     * @param  int     $_mode     directory mode (for example 0777)
     * @param  int     $_options  bitmask of options
     */
    public function mkdir($_path, $_mode, $_options)
    {
        try {
            $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        }
        
        $recursive       = (bool)($_options & STREAM_MKDIR_RECURSIVE);
        $pathParts       = $this->_splitPath($_path);
        $parentDirectory = null;
        
        foreach ($pathParts as $directoryName) {
            try {
                $parentDirectory = $this->_getTreeNode($parentDirectory, $directoryName);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                $parentDirectory = $this->_createDirectoryTreeNode($parentDirectory, $directoryName);
            }
        }
        
        return true;
    }
    
    /**
     * rename file/directory
     * 
     * @param  string  $_oldPath
     * @param  string  $_newPath
     */
    public function rename($_oldPath, $_newPath)
    {
        try {
            $oldPath = $this->_validatePath($_oldPath);
            $newPath = $this->_validatePath($_newPath);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        }
        
        try {
            $node = $this->_getTreeNodeBackend()->getLastPathNode($oldPath);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            trigger_error('path not found', E_USER_WARNING);
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            trigger_error('path not found', E_USER_WARNING);
            return false;
        }

        if (dirname($oldPath) != dirname($newPath)) {
            try {
                $newParent = $this->_getTreeNodeBackend()->getLastPathNode(dirname($newPath));
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                trigger_error('new parent path not found', E_USER_WARNING);
                return false;
            } catch (Tinebase_Exception_NotFound $tenf) {
                trigger_error('new parent path not found', E_USER_WARNING);
                return false;
            }
            
            $node->parent_id = $newParent->getId();
        }
        
        if (basename($oldPath) != basename($newPath)) {
            $node->name = basename($newPath);
        }
        
        $this->_getTreeNodeBackend()->update($node);
    }
    
    public function rmdir($_path, $_options)
    {
        try {
            $path = $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        }
        
        $recursive       = (bool)($_options & 1);
        
        $node = $this->_getTreeNodeBackend()->getLastPathNode($path);
        
        $children = $this->_getTreeNodeBackend()->getChildren($node);
        
        // check if child entries exists and delete if $recursive is true
        if ($children->count() > 0) {
            if ($recursive !== true) {
                trigger_error('directory not empty', E_USER_WARNING);
                return false;
            } else {
                foreach ($children as $child) {
                    if ($this->isDir($_path . '/' . $child->name)) {
                        $this->rmdir($_path . '/' . $child->name, STREAM_MKDIR_RECURSIVE);
                    } else {
                        $this->unlink($_path . '/' . $child->name);
                    }
                }
            }
        }
        
        // delete tree node
        $this->_getTreeNodeBackend()->delete($node->getId());
        
        // delete object only, if no other tree node refers to it
        if ($this->_getTreeNodeBackend()->getObjectCount($node->object_id) == 0) {
            $this->_getObjectBackend()->delete($node->object_id);
        }
        
        return true;
    }
    
    public function filesize($_path)
    {
        try {
            $path = $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        }
        
        $node = $this->_getTreeNodeBackend()->getLastPathNode($path);
        
        return $node->size;
    }
    
    public function stream_close()
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        $options = stream_context_get_options($this->_stream);
        
        switch ($options['tine20']['mode']) {
            case 'w':
            case 'wb':
            case 'x':
            case 'xb':
                rewind($this->_stream);
                
                $ctx = hash_init('sha1');
                hash_update_stream($ctx, $this->_stream);
                $hash = hash_final($ctx);
                
                $hashDirectory = $this->_getBasePath() . '/' . substr($hash, 0, 3);
                $hashFile      = $hashDirectory . '/' . substr($hash, 3);
                
                if (!file_exists($hashDirectory)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create hash directory: ' . $hashDirectory);
                    if(mkdir($hashDirectory, 0700) === false) {
                        throw new Tinebase_Exception_UnexpectedValue('failed to create directory');
                    } 
                }
                
                if (!file_exists($hashFile)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create hash file: ' . $hashFile);
                    rewind($this->_stream);
                    $hashHandle = fopen($hashFile, 'x');
                    stream_copy_to_stream($this->_stream, $hashHandle);
                    fclose($hashHandle);
                }
                
                $fileObject = $this->_getObjectBackend()->get($this->_currentNode->object_id);
                $fileObject->hash = $hash;
                $fileObject->size = filesize($hashFile);
                if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $hashFile);
                    if ($mimeType !== false) {
                        $fileObject->contenttype = $mimeType;
                    }
                    finfo_close($finfo);
                }
                
                $this->_getObjectBackend()->update($fileObject);
                
                break;
        }
        
        fclose($this->_stream);
        
        return true;
    }
    
    public function stream_eof()
    {
        if (!is_resource($this->_stream)) {
            return true; // yes, true
        }
        
        return feof($this->_stream);
    }
    
    public function stream_open($_path, $_mode, $_options, &$_opened_path)
    {
        $quiet    = !(bool)($_options & STREAM_REPORT_ERRORS);
        
        try {
            $path = $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (!$quiet) {
                trigger_error('path must start with tine20://', E_USER_WARNING);
            }
            return false;
        }
        
        switch ($_mode) {
            case 'x':
            case 'xb':
                $dirName  = dirname($path);
                $fileName = basename($path);
                
                try {
                    $parent = $this->_getTreeNodeBackend()->getLastPathNode($dirName);
                    if ($parent->type != Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                        if (!$quiet) {
                            trigger_error('parent must be directory', E_USER_WARNING);
                        }
                        return false;
                    }
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (!$quiet) {
                        trigger_error('parent directory not found', E_USER_WARNING);
                    }
                    return false;
                    
                }
                
                if ($this->_getTreeNodeBackend()->pathExists($path)) {
                    if (!$quiet) {
                        trigger_error('file exists already', E_USER_WARNING);
                    }
                    return false;
                }
                
                $this->_currentNode = $this->_createFileTreeNode($parent, $fileName);
                
                $this->_stream = tmpfile();
                $_opened_path = $_path;
                
                break;
                
            case 'w':
            case 'wb':
                $dirName  = dirname($path);
                $fileName = basename($path);
                
                try {
                    $parent = $this->_getTreeNodeBackend()->getLastPathNode($dirName);
                    if ($parent->type != Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                        if (!$quiet) {
                            trigger_error('parent must be directory', E_USER_WARNING);
                        }
                        return false;
                    }
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (!$quiet) {
                        trigger_error('parent directory not found', E_USER_WARNING);
                    }
                    return false;
                    
                }
                
                try {
                    $this->_currentNode = $this->_getTreeNodeBackend()->getLastPathNode($path);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    $this->_currentNode = $this->_createFileTreeNode($parent, $fileName);
                }
                
                $this->_stream = tmpfile();
                $_opened_path = $_path;
                
                break;
                
            case 'r':
                try {
                    $node = $this->_getTreeNodeBackend()->getLastPathNode($path);
                    if ($node->type != Tinebase_Model_Tree_FileObject::TYPE_FILE) {
                        if (!$quiet) {
                            trigger_error('path is not a file', E_USER_WARNING);
                        }
                        return false;
                    }
                } catch (Tinebase_Exception_NotFound $tenf) {
                    if (!$quiet) {
                        trigger_error('file not found', E_USER_WARNING);
                    }
                    return false;
                    
                }
                $this->_currentNode = $node;
                
                $hashFile = $this->_getBasePath() . '/' . substr($this->_currentNode->hash, 0, 3) . '/' . substr($this->_currentNode->hash, 3);
                
                $this->_stream = fopen($hashFile, 'r');
                $_opened_path = $_path;
                
                break;
                
            default:
                return false;
        }
        
        stream_context_set_option ($this->_stream, 'tine20', 'mode', $_mode);
        
        return true;
    }
    
    public function stream_read($_length)
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        return fread($this->_stream, $_length);
    }
    
    public function stream_seek($_offset, $_whence = SEEK_SET)
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        return fseek($this->_stream, $_offset, $_whence);
    }
    
    public function stream_stat()
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        $node = $this->_currentNode;
        $streamStat = fstat($this->_stream);
         
        $timestamp = $node->last_modified_time instanceof Tinebase_DateTime ? $node->last_modified_time->getTimestamp() : $node->creation_time->getTimestamp();
        
        $mode      = 0;
        // set node type (directory, file, link)
        $mode      = $node->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER ? $mode | 0040000 : $mode | 0100000;
        
        $stat =  array(
            0  => 0,
            1  => crc32($node->object_id),
            2  => $mode,
            3  => 0,
            4  => 0,
            5  => 0,
            6  => 0,
            7  => $streamStat['size'],
            8  => $timestamp,
            9  => $timestamp,
            10 => $node->creation_time->getTimestamp(),
            11 => -1,
            12 => -1,
            'dev'     => 0,
            'ino'     => crc32($node->object_id),
            'mode'    => $mode,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => $streamStat['size'],
            'atime'   => $timestamp,
            'mtime'   => $timestamp, 
            'ctime'   => $node->creation_time->getTimestamp(),
            'blksize' => -1,
            'blocks'  => -1
        );
        
        return $stat;
        
    }
    
    public function stream_write($_data)
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        $options = stream_context_get_options($this->_stream);
        
        if (!in_array($options['tine20']['mode'], array('w', 'wb', 'x', 'xb'))) {
            // readonly
            return false;
        }
        
        return fwrite($this->_stream, $_data);
    }
    
    public function stream_tell()
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        return ftell($this->_stream);
    }
    
    public function unlink($_path)
    {
        try {
            $path = $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            trigger_error('path must start with tine20://', E_USER_WARNING);
            return false;
        }
        
        try {
            $node = $this->_getTreeNodeBackend()->getLastPathNode($path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            trigger_error('path not found', E_USER_WARNING);
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            trigger_error('path not found', E_USER_WARNING);
            return false;
        }
        
        if ($node->type !== Tinebase_Model_Tree_FileObject::TYPE_FILE) {
            trigger_error('can unlink files and links only', E_USER_WARNING);
            return false;
        }
        
        $this->_getTreeNodeBackend()->delete($node->getId());
        
        // delete object only, if no one uses it anymore
        if ($this->_getTreeNodeBackend()->getObjectCount($node->object_id) == 0) {
            $this->_getObjectBackend()->delete($node->object_id);
        }
        
        return true;
    }
    
    public function url_stat($_path, $_flags)
    {
        $statLink = (bool)($_flags & STREAM_URL_STAT_LINK);
        $quiet    = (bool)($_flags & STREAM_URL_STAT_QUIET);
        
        try {
            $path = $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (!$quiet) {
                trigger_error('path must start with tine20://', E_USER_WARNING);
            }
            return false;
        }
        
        try {
            $node = $this->_getTreeNodeBackend()->getLastPathNode($path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (!$quiet) {
                trigger_error('path not found', E_USER_WARNING);
            }
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (!$quiet) {
                trigger_error('path not found', E_USER_WARNING);
            }
            return false;
        }
        
        $timestamp = $node->last_modified_time instanceof Tinebase_DateTime ? $node->last_modified_time->getTimestamp() : $node->creation_time->getTimestamp();
        
        $mode      = 0;
        // set node type (directory, file, link)
        $mode      = $node->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER ? $mode | 0040000 : $mode | 0100000;
        
        $stat =  array(
            0  => 0,
            1  => crc32($node->object_id),
            2  => $mode,
            3  => 0,
            4  => 0,
            5  => 0,
            6  => 0,
            7  => $node->size,
            8  => $timestamp,
            9  => $timestamp,
            10 => $node->creation_time->getTimestamp(),
            11 => -1,
            12 => -1,
            'dev'     => 0,
            'ino'     => crc32($node->object_id),
            'mode'    => $mode,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => $node->size,
            'atime'   => $timestamp,
            'mtime'   => $timestamp, 
            'ctime'   => $node->creation_time->getTimestamp(),
            'blksize' => -1,
            'blocks'  => -1
        );
        
        return $stat;
    }
    
    /**
     * @param unknown_type $_parentId
     * @param unknown_type $_name
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Nodenager_Model_Tree
     */
    protected function _createDirectoryTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        $directoryObject = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FOLDER,
            'contentytype'  => null,
            'creation_time' => Tinebase_DateTime::now() 
        ));
        $directoryObject = $this->_getObjectBackend()->create($directoryObject);
        
        $treeNode = new Tinebase_Model_Tree_Node(array(
            'name'          => $_name,
            'object_id'     => $directoryObject->getId(),
            'parent_id'     => $parentId
        ));
        
        $treeNode = $this->_getTreeNodeBackend()->create($treeNode);
        
        return $treeNode;
    }

    /**
     * @param unknown_type $_parentId
     * @param unknown_type $_name
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    protected function _createFileTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        $fileObject = new Tinebase_Model_Tree_FileObject(array(
            'type'          => Tinebase_Model_Tree_FileObject::TYPE_FILE,
            'contentytype'  => null,
            'creation_time' => Tinebase_DateTime::now() 
        ));
        $fileObject = $this->_getObjectBackend()->create($fileObject);
        
        $treeNode = new Tinebase_Model_Tree_Node(array(
            'name'          => $_name,
            'object_id'     => $fileObject->getId(),
            'parent_id'     => $parentId
        ));
        $treeNode = $this->_getTreeNodeBackend()->create($treeNode);
        
        return $treeNode;
    }
    
    protected function _getBasePath()
    {
        if ($this->_physicalBasePath === null) {
            if (empty(Tinebase_Core::getConfig()->filesdir) || !is_writeable(Tinebase_Core::getConfig()->filesdir)) {
                throw new Tinebase_Exception_InvalidArgument('no base path(filesdir) configured');
            }
            
            $this->_physicalBasePath = Tinebase_Core::getConfig()->filesdir;
        }
        
        return $this->_physicalBasePath;
    }
    
    /**
     * 
     * return Tinebase_Tree_Nodeanager_Backend_Tree
     */
    protected function _getObjectBackend()
    {
        if (!$this->_objectBackend instanceof Tinebase_Tree_FileObject) {
            $this->_objectBackend = new Tinebase_Tree_FileObject();
        }
        
        return $this->_objectBackend;
    }
    
    /**
     * 
     * @return Tinebase_FileSystem
     */
    protected function _getTinebaseFileSystem()
    {
        if (!$this->_tinebaseFileSystem instanceof Tinebase_FileSystem) {
            $this->_tinebaseFileSystem = new Tinebase_FileSystem();
        }
        
        return $this->_tinebaseFileSystem;
    }
    
    /**
     * 
     * return Tinebase_Tree_Node
     */
    protected function _getTreeNodeBackend()
    {
        if (!$this->_treeBackend instanceof Tinebase_Tree_Node) {
            $this->_treeBackend = new Tinebase_Tree_Node();
        }
        
        return $this->_treeBackend;
    }
    
    /**
     * @param  string  $_parentId  id of the parent node
     * @param  string  $_name      name of the node
     * @throws Tinebase_Exception_InvalidArgument
     * @return Tinebase_Model_Tree_Node
     */
    protected function _getTreeNode($_parentId, $_name)
    {
        $parentId = $_parentId instanceof Tinebase_Model_Tree_Node ? $_parentId->getId() : $_parentId;
        
        $searchFilter = new Tinebase_Model_Tree_NodeFilter(array(
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
        
        $result = $this->_getTreeNodeBackend()->search($searchFilter);
        
        if ($result->count() == 0) {
            throw new Tinebase_Exception_InvalidArgument('directory node not found');
        }
        
        return $result[0];
    }
    
    /**
     * @param  string  $_path
     * @return array
     */
    protected function _splitPath($_path)
    {
        return explode('/', trim(substr($_path, 9), '/'));
    }
    
    protected function _validatePath($_path)
    {
        if (substr($_path, 0, 9) != 'tine20://') {
            throw new Tinebase_Exception_InvalidArgument('path must start with tine20://');
        }
        
        return substr($_path, 9);
    }
}
