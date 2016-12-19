<?php
/**
 * Tine 2.0
 *
 * @package     Expressodriver
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Marcelo Teixeira <marcelo.teixeira@serpro.gov.br>
 * @author      Edgar de Lucca <edgar.lucca@serpro.gov.br>
 *
 */

/**
 * filesystem streamwrapper for external:// (external storages)
 *
 * @package     Expressodriver
 * @subpackage  Backend
 */
class Expressodriver_Backend_Storage_StreamWrapper extends Tinebase_FileSystem_StreamWrapper
{

    /**
     * open folder in server
     *
     * @param string $_path
     * @param array $_options [unused]
     * @return boolean success
     */
    public function dir_opendir($_path, $_options)
    {
        $nodeController = Expressodriver_Controller_Node::getInstance();
        $backend = $nodeController->getAdapterBackend(substr($_path, 11));

        try {
            $node = $nodeController->stat(substr($_path, 11));
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

        $this->_readDirRecordSet = $backend->scanDir(substr($_path, 11)); // @todo: backend scanDir
        $this->_readDirIterator  = $this->_readDirRecordSet->getIterator();
        reset($this->_readDirIterator);

        return true;
    }

    /**
     * create directory
     *
     * @param  string  $_path     path to create
     * @param  int     $_mode     directory mode (for example 0777)
     * @param  int     $_options  bitmask of options
     * @return boolean success
     */
    public function mkdir($_path, $_mode, $_options)
    {
        $nodeController = Expressodriver_Controller_Node::getInstance();
        $backend = $nodeController->getAdapterBackend(substr($_path, 11));

        $backend->mkdir(substr($_path, 11));

        return true;
    }

    /**
     * rename file/directory
     *
     * @param  string  $_oldPath
     * @param  string  $_newPath
     * @return boolean success
     */
    public function rename($_oldPath, $_newPath)
    {
        $nodeController = Expressodriver_Controller_Node::getInstance();
        $backend = $nodeController->getAdapterBackend(substr($_path, 11));

        return $backend->rename(substr($_oldPath, 11), substr($_newPath, 11));
    }

    /**
     * remove folder
     *
     * @param string $_path
     * @param resource $_context [unused]
     * @return boolean success
     */
    public function rmdir($_path, $_context = NULL)
    {
        $nodeController = Expressodriver_Controller_Node::getInstance();
        $backend = $nodeController->getAdapterBackend(substr($_path, 11));

        return $backend->rmdir(substr($_path, 11), true);
    }

    /**
     * clouse stream of files
     *
     * @return boolean success
     *
     * @todo: get proper adapter backend and call fclose
     */
    public function stream_close()
    {
        if (!is_resource($this->_stream)) {
            return false;
        }
        //$nodeController = Expressodriver_Controller_Node::getInstance();
        //$backend = $nodeController->getAdapterBackend(substr($_path, 11));
        //$backend->fclose($this->_stream);


        return true;
    }

    /**
     * open stream
     *
     * @param string $_path
     * @param string $_mode
     * @param array $_options
     * @param string $_opened_path
     * @return boolean success
     */
    public function stream_open($_path, $_mode, $_options, &$_opened_path)
    {
        $nodeController = Expressodriver_Controller_Node::getInstance();
        $backend = $nodeController->getAdapterBackend(substr($_path, 11));
        $path = $nodeController->removeUserBasePath(substr($_path, 11));

        $quiet    = !(bool)($_options & STREAM_REPORT_ERRORS);

        $stream = $backend->fopen($path, $_mode);

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
     * unlink
     *
     * @param string $_path
     * @return boolean success
     */
    public function unlink($_path)
    {
        $nodeController = Expressodriver_Controller_Node::getInstance();
        $backend = $nodeController->getAdapterBackend(substr($_path, 11));

        try {
            $result = $backend->unlink(substr($_path, 11));
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
        $nodeController = Expressodriver_Controller_Node::getInstance();
        $statLink = (bool)($_flags & STREAM_URL_STAT_LINK);
        $quiet    = (bool)($_flags & STREAM_URL_STAT_QUIET);

        try {
            $node = $nodeController->stat(substr($_path, 11));
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