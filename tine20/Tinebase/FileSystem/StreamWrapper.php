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
class Tinebase_FileSystem_StreamWrapper
{
    /**
     * the context
     * 
     * @var resource
     */
    public $context;
    
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
     * stores the list of directory children for readdir
     *
     * @var ArrayIterator
     */
    protected $_readDirIterator;
    
    /**
     * @var resource
     */
    protected $_stream;
    
    /**
     * dir_closedir
     */
    public function dir_closedir() 
    {
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
        $quiet    = !(bool)($_options & STREAM_REPORT_ERRORS);

        try {
            $node = Tinebase_FileSystem::getInstance()->stat(substr($_path, 9));
        } catch (Tinebase_Exception_NotFound $teia) {
            if (!$quiet) {
                trigger_error($teia->getMessage(), E_USER_WARNING);
            }
            return false;
        }
        
        if ($node->type != Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            trigger_error("$_path isn't a directory", E_USER_WARNING);
            return false;
        }
        
        $this->_readDirRecordSet = Tinebase_FileSystem::getInstance()->scanDir(substr($_path, 9));
        $this->_readDirIterator  = $this->_readDirRecordSet->getIterator();
        $this->_readDirIterator->rewind();
        
        return true;
    }
    
    /**
     * dir_readdir
     */
    public function dir_readdir() 
    {
        if (!$this->_readDirIterator->valid()) {
            return false;
        }
        $node = $this->_readDirIterator->current();
        $this->_readDirIterator->next();
        
        return $node->name;
    }
    
    /**
     * dir_rewinddir
     */
    public function dir_rewinddir()
    {
        $this->_readDirIterator->rewind();
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
        Tinebase_FileSystem::getInstance()->mkdir(substr($_path, 9));
        
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
        return false !== Tinebase_FileSystem::getInstance()->rename(substr($_oldPath, 9), substr($_newPath, 9));
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
        return Tinebase_FileSystem::getInstance()->rmdir(substr($_path, 9), true);
    }

    public function stream_truncate(int $new_size)
    {
        if (!is_resource($this->_stream)) {
            return false;
        }

        $options = stream_context_get_options($this->_stream);

        if (!in_array($options['tine20']['mode'], array('w', 'wb', 'x', 'xb', 'a+'))) {
            // readonly
            return false;
        }

        return ftruncate($this->_stream, $new_size);
    }

    // well this needs improvment!
    // https://www.php.net/manual/en/streamwrapper.stream-lock.php
    public function stream_lock(int $operation)
    {
        if (!is_resource($this->_stream)) {
            return false;
        }

        Tinebase_FileSystem::getInstance()->acquireWriteLock();

        return true;
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
         
        Tinebase_FileSystem::getInstance()->fclose($this->_stream);
        
        return true;
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

        if (!is_resource($this->context)) {
            $context = null;
        } else {
            $context = stream_context_get_options($this->context);
        }
        if (isset($context[__CLASS__]) && isset($context[__CLASS__]['revision'])) {
            $revision = $context[__CLASS__]['revision'];
        } else {
            $revision = null;
        }

        $stream = Tinebase_FileSystem::getInstance()->fopen(substr($_path, 9), $_mode, $revision);
        
        if (!is_resource($stream)) {
            if (!$quiet) {
                trigger_error('falied to open stream', E_USER_WARNING);
            }
            return false;
        }
        
        $this->_stream = $stream;
        $_opened_path = $_path;
        
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
        
        $streamStat = fstat($this->_stream);
         
        $timestamp = Tinebase_DateTime::now()->getTimestamp();
        
        // set node type (file)
        $mode      = 0100000;
        
        $stat =  array(
            0  => 0,
            1  => 1, // inode
            2  => $mode,
            3  => 0,
            4  => 0,
            5  => 0,
            6  => 0,
            7  => $streamStat['size'],
            8  => $timestamp,
            9  => $timestamp,
            10 => $timestamp,
            11 => -1,
            12 => -1,
            'dev'     => 0,
            'ino'     => 1,
            'mode'    => $mode,
            'nlink'   => 0,
            'uid'     => 0,
            'gid'     => 0,
            'rdev'    => 0,
            'size'    => $streamStat['size'],
            'atime'   => $timestamp,
            'mtime'   => $timestamp, 
            'ctime'   => $timestamp,
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
        
        if (!in_array($options['tine20']['mode'], array('w', 'wb', 'x', 'xb', 'a+'))) {
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
            $result = Tinebase_FileSystem::getInstance()->unlink(substr($_path, 9));
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            trigger_error($teia->getMessage(), E_USER_WARNING);
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            trigger_error($tenf->getMessage(), E_USER_WARNING);
            return false;
        }
        
        return $result;
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
            $node = Tinebase_FileSystem::getInstance()->stat(substr($_path, 9));
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            if (!$quiet) {
                trigger_error($teia->getMessage(), E_USER_WARNING);
            }
            return false;
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (!$quiet) {
                trigger_error($tenf->getMessage(), E_USER_WARNING);
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
}
