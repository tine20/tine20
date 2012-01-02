<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2010-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
    /**
     * the context
     * 
     * @var resource
     */
    public $context;
    
    /**
     * object backend
     * 
     * @var Tinebase_Tree_FileObject
     */
    protected $_objectBackend;
    
    /**
     * node backend
     * 
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
     * the filesystem
     * 
     * @var Tinebase_FileSystem
     */
    protected $_tinebaseFileSystem;

    /**
     * dir_closedir
     */
    public function dir_closedir() 
    {
        $this->_currentNode      = null;
        $this->_readDirRecordSet = null;
        $this->_readDirIterator  = null;
        
        return true;
    }
    
    /**
     * dir_opendir
     * 
     * @param string $_path
     * @param array $_options [unused]
     */
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
    
    /**
     * dir_readdir
     */
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
    
    /**
     * dir_rewinddir
     */
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
                $parentDirectory = $this->_getTinebaseFileSystem()->getTreeNode($parentDirectory, $directoryName);
            } catch (Tinebase_Exception_InvalidArgument $teia) {
                $parentDirectory = $this->_getTinebaseFileSystem()->createDirectoryTreeNode($parentDirectory, $directoryName);
            }
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
        
        return true;
    }
    
    /**
     * remove dir
     * 
     * @param string $_path
     * @param resource $_context [unused]
     * @return boolean
     */
    public function rmdir($_path, $_context = NULL)
    {
        try {
            $path = $this->_validatePath($_path);
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            return false;
        }
        
        $node = $this->_getTreeNodeBackend()->getLastPathNode($path);
        
        $children = $this->_getTreeNodeBackend()->getChildren($node);
        
        // check if child entries exists and delete if $recursive is true
        if ($children->count() > 0) {
            foreach ($children as $child) {
                if ($this->isDir($_path . '/' . $child->name)) {
                    $this->rmdir($_path . '/' . $child->name, STREAM_MKDIR_RECURSIVE);
                } else {
                    $this->unlink($_path . '/' . $child->name);
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
    
    /**
     * get filesize
     * 
     * @param $_path
     * @return integer
     */
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
    
    /**
     * stream_close
     * 
     * @return boolean
     */
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
                
                $hash = $this->_generateHash();
                
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
                if (empty($fileObject->size)) {
                    $fileObject->size = 0;
                }
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
    
    /**
     * generate hash checksum for file (stream)
     * 
     * - uses system shasum command if it exists
     * 
     * @return string sha1 hash of file
     */
    protected function _generateHash()
    {
        if (function_exists('shell_exec') && shell_exec('command -v shasum > /dev/null && echo 1 || echo 0')) {
            $tempfile = tempnam(Tinebase_Core::getTempDir(), 'tine20_hash');
            $tempfileHandle = fopen($tempfile, 'w');
            stream_copy_to_stream($this->_stream, $tempfileHandle);
            rewind($this->_stream);
            
            $output = shell_exec('shasum ' . escapeshellarg($tempfile));
            list($hash, $filename) = preg_split('/\s/', $output); 
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Generated hash with shasum: ' . $hash);
        } else {
            $ctx = hash_init('sha1');
            hash_update_stream($ctx, $this->_stream);
            $hash = hash_final($ctx);
        }
        
        return $hash;
    }
    
    /**
     * stream_eof
     * 
     * @return boolean
     */
    public function stream_eof()
    {
        if (!is_resource($this->_stream)) {
            return true; // yes, true
        }
        
        return feof($this->_stream);
    }
    
    /**
     * open stream
     * 
     * @param string $_path
     * @param string $_mode
     * @param array $_options
     * @param string $_opened_path
     * @return boolean
     */
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
                
                $this->_currentNode = $this->_getTinebaseFileSystem()->createFileTreeNode($parent, $fileName);
                
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
                    $this->_currentNode = $this->_getTinebaseFileSystem()->createFileTreeNode($parent, $fileName);
                }
                
                $this->_stream = tmpfile();
                $_opened_path = $_path;
                
                break;
                
            case 'rb':
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
                if (!$quiet) {
                    trigger_error('invalid mode: ' . $_mode, E_USER_WARNING);
                }
                return false;
        }
        
        stream_context_set_option ($this->_stream, 'tine20', 'mode', $_mode);
        
        return true;
    }
    
    /**
     * read stream
     * 
     * @param integer $_length
     * @return boolean|string
     */
    public function stream_read($_length)
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        return fread($this->_stream, $_length);
    }
    
    /**
     * stream_seek
     * 
     * @param integer $_offset
     * @param integer $_whence
     * @return boolean|integer
     */
    public function stream_seek($_offset, $_whence = SEEK_SET)
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        return fseek($this->_stream, $_offset, $_whence);
    }
    
    /**
     * stream_stat
     * 
     * @return boolean|array
     */
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
    
    /**
     * stream_write
     * 
     * @param string $_data
     * @return boolean|integer
     */
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
    
    /**
     * stream_tell
     * 
     * @return boolean|integer
     */
    public function stream_tell()
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        
        return ftell($this->_stream);
    }
    
    /**
     * unlink
     * 
     * @param string $_path
     * @return boolean
     */
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
    
    /**
     * url_stat
     * 
     * @param string $_path
     * @param array $_flags
     * @return boolean|array
     */
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
     * get base path
     * 
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
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
     * get object backend
     * 
     * @return Tinebase_Tree_FileObject
     */
    protected function _getObjectBackend()
    {
        if (!$this->_objectBackend instanceof Tinebase_Tree_FileObject) {
            $this->_objectBackend = new Tinebase_Tree_FileObject();
        }
        
        return $this->_objectBackend;
    }
    
    /**
     * get Tinebase filesystem
     * 
     * @return Tinebase_FileSystem
     */
    protected function _getTinebaseFileSystem()
    {
        if (! $this->_tinebaseFileSystem instanceof Tinebase_FileSystem) {
            $this->_tinebaseFileSystem = Tinebase_FileSystem::getInstance();
        }
        
        return $this->_tinebaseFileSystem;
    }
    
    /**
     * get node backend
     * 
     * @return Tinebase_Tree_Node
     */
    protected function _getTreeNodeBackend()
    {
        if (!$this->_treeBackend instanceof Tinebase_Tree_Node) {
            $this->_treeBackend = new Tinebase_Tree_Node();
        }
        
        return $this->_treeBackend;
    }
    
    /**
     * split path
     * 
     * @param  string  $_path
     * @return array
     */
    protected function _splitPath($_path)
    {
        return explode('/', trim(substr($_path, 9), '/'));
    }
    
    /**
     * validate path
     * 
     * @param string $_path
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _validatePath($_path)
    {
        if (substr($_path, 0, 9) != 'tine20://') {
            throw new Tinebase_Exception_InvalidArgument('path must start with tine20://');
        }
        
        return substr($_path, 9);
    }
}
