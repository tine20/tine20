<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for Tinebase
 * 
 * @package     Tinebase
 */
class Tinebase_Frontend_WebDAV_Directory extends Tinebase_Frontend_WebDAV_Node implements Sabre\DAV\ICollection
{
    /**
    * webdav file class
    *
    * @var string
    */
    protected $_fileClass = 'Tinebase_Frontend_WebDAV_File';
    
    /**
     * webdav directory class
     *
     * @var string
     */
    protected $_directoryClass = 'Tinebase_Frontend_WebDAV_Directory';

    /**
     * return list of children
     * @return array list of children
     */
    public function getChildren() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path);
        
        $children = array();
            
        // Loop through the directory, and create objects for each node
        foreach(Tinebase_FileSystem::getInstance()->scanDir($this->_path) as $node) {
            $children[] = $this->getChild($node->name);
        }
        
        return $children;
    }
    
    /**
     * get child by name
     * 
     * @param  string $name
     * @throws Sabre\DAV\Exception\NotFound
     * @return Tinebase_Frontend_WebDAV_Directory|Tinebase_Frontend_WebDAV_File
     */
    public function getChild($name) 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path . '/' . $name);
        
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);
        
        try {
            $childNode = Tinebase_FileSystem::getInstance()->stat($this->_path . '/' . $name);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre\DAV\Exception\NotFound('file not found: ' . $this->_path . '/' . $name);
        }
        
        if ($childNode->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            return new $this->_directoryClass($this->_path . '/' . $name);
        } else {
            return new $this->_fileClass($this->_path . '/' . $name);
        }
    }
    
    public function childExists($name) 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__ . ' exists: ' . $this->_path . '/' . $name);
        
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);
        
        return Tinebase_FileSystem::getInstance()->fileExists($this->_path . '/' . $name);
    }

    /**
     * Creates a new file in the directory 
     * 
     * @param string $name Name of the file 
     * @param resource $data Initial payload, passed as a readable stream resource. 
     * @throws Sabre\DAV\Exception\Forbidden
     * @return void
     */
    public function createFile($name, $data = null) 
    {
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);

        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (!Tinebase_FileSystem::getInstance()->checkPathACL(
            $pathRecord,
            'add',
            true, false
        )) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to create file: ' . $this->_path . '/' . $name);
        }

        // OwnCloud chunked file upload
        if (isset($_SERVER['HTTP_OC_CHUNKED']) && is_resource($data)) {
            $completeFile = Tinebase_Frontend_WebDAV_Directory::handleOwnCloudChunkedFileUpload($name, $data);

            if (!$completeFile instanceof Tinebase_Model_TempFile) {
                return null;
            }

            $name = $completeFile->name;
            if (false === ($data = fopen($completeFile->path, 'r'))) {
                throw new Tinebase_Exception_Backend('fopen on temp file path failed ' . $completeFile->path);
            }
        }

        if ($this->childExists($name)) {
            $result = $this->getChild($name)->put($data);
            return $result;
        }

        $path = $this->_path . '/' . $name;

        try {

            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
                Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $path);
            }

            if (false === ($handle = Tinebase_FileSystem::getInstance()->fopen($path, 'x'))) {
                throw new Tinebase_Exception_Backend('Tinebase_FileSystem::fopen failed for path ' . $path);
            }

            if (is_resource($data)) {
                if (false === stream_copy_to_stream($data, $handle)) {
                    throw new Tinebase_Exception_Backend('stream_copy_to_stream failed');
                }
            } else {
                throw new Tinebase_Exception_UnexpectedValue('data should be a resource');
            }

            if (true !== Tinebase_FileSystem::getInstance()->fclose($handle)) {
                throw new Tinebase_Exception_Backend('Tinebase_FileSystem::fclose failed for path ' . $path);
            }


        } catch (Exception $e) {
            Tinebase_FileSystem::getInstance()->unlink($path);

            if ($e instanceof Tinebase_Exception_Record_NotAllowed && $e->getMessage() === 'quota exceeded') {
                throw new Sabre\DAV\Exception\InsufficientStorage($e->getMessage());
            }

            throw $e;
        }

        return '"' . Tinebase_FileSystem::getInstance()->getETag($path) . '"';
    }

    /**
     * Creates a new subdirectory 
     * 
     * @param string $name 
     * @throws Sabre\DAV\Exception\Forbidden
     * @return void
     */
    public function createDirectory($name) 
    {
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);

        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! Tinebase_FileSystem::getInstance()->checkPathACL(
            $pathRecord,
            'add',
            true, false
        )) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to create folder: ' . $name);
        }
        
        $path = $this->_path . '/' . $name;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create directory: ' . $path);
        
        Tinebase_FileSystem::getInstance()->mkdir($path);
    }
    
    /**
     * Deleted the current node
     * 
     * @todo   use filesystem controller to delete directories recursive
     * @throws Sabre\DAV\Exception\Forbidden
     * @return void 
     */
    public function delete() 
    {
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! Tinebase_FileSystem::getInstance()->checkPathACL(
            $pathRecord->getParent(),
            'delete',
            true, false
        )) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to delete directory: ' . $this->_path);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete directory: ' . $this->_path);
        
        foreach ($this->getChildren() as $child) {
            $child->delete();
        }

        try {
            if (!Tinebase_FileSystem::getInstance()->rmdir($this->_path)) {
                throw new Sabre\DAV\Exception\Forbidden('Permission denied to delete node');
            }
        } catch (Tinebase_Exception_InvalidArgument $teia) {
            // directory not empty ...
            throw new Sabre\DAV\Exception\Forbidden($teia->getMessage());
        }
    }
    
    /**
     * handle chunked upload
     * 
     * @param string $name Name of the file
     * @param resource $data payload, passed as a readable stream resource.
     * @throws \Sabre\DAV\Exception\BadRequest
     * @return boolean|Tinebase_Model_TempFile
     */
    public static function handleOwnCloudChunkedFileUpload($name, $data)
    {
        if (!isset($_SERVER['CONTENT_LENGTH'])) {
            throw new \Sabre\DAV\Exception\BadRequest('CONTENT_LENGTH header missing!');
        }
        
        $matched = preg_match('/(?P<name>.*)-chunking-(?P<tempId>\d+)-(?P<totalCount>\d+)-(?P<chunkId>\d+)/', $name, $chunkInfo);
        if (!$matched) {
            throw new \Sabre\DAV\Exception\BadRequest('bad filename provided: ' . $name);
        }
        
        // copy chunk to temp file
        $path = Tinebase_TempFile::getTempPath();
        
        $tempfileHandle = fopen($path, "w");
        
        if (! $tempfileHandle) {
            throw new Tinebase_Exception_Backend('Could not open tempfile while uploading! ');
        }

        if (false === stream_copy_to_stream($data, $tempfileHandle)) {
            throw new Tinebase_Exception_Backend('stream_copy_to_stream failed');
        }
        
        $stat = fstat($tempfileHandle);
        
        if ($_SERVER['CONTENT_LENGTH'] != $stat['size']) {
            throw new \Sabre\DAV\Exception\BadRequest('uploaded part incomplete! expected size of: ' . $_SERVER['CONTENT_LENGTH'] . ' got: ' . $stat['size']);
        }
        
        fclose($tempfileHandle);
        
        $tempFileName = sha1(Tinebase_Core::getUser()->accountId . $chunkInfo['name'] . $chunkInfo['tempId']);
        
        Tinebase_TempFile::getInstance()->createTempFile($path, $tempFileName, $chunkInfo['chunkId'] + 1);
        
        // check if the client sent all chunks
        $uploadedChunks = Tinebase_TempFile::getInstance()->search(
            new Tinebase_Model_TempFileFilter(array(
                array('field' => 'name', 'operator' => 'equals', 'value' => $tempFileName)
            )), 
            new Tinebase_Model_Pagination(array('sort' => 'type', 'dir' => 'ASC'))
        );
        
        if ($uploadedChunks->count() != $chunkInfo['totalCount']) {
            return false;
        }
        
        // combine all chunks to one file
        $joinedFile = Tinebase_TempFile::getInstance()->joinTempFiles($uploadedChunks);
        $joinedFile->name = $chunkInfo['name'];
        
        foreach ($uploadedChunks as $chunk) {
            unlink($chunk->path);
        }
        
        return $joinedFile;
    }
}
