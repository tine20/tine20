<?php
/**
 * Tine 2.0
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for filemanager
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_WebDav_File extends Filemanager_Frontend_WebDav_Node implements Sabre_DAV_IFile
{
    public function __construct($_path) 
    {
        parent::__construct($_path);
        
        if ($this->_container == null) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $this->_path . ' could not be found');
        }
        
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_READ)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $this->_path . ' could not be found');
        }

        try {
            $this->_node = Tinebase_FileSystem::getInstance()->stat(substr($this->_fileSystemPath, 9));
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $this->_path . ' could not be found');
        }
    }
    
    public function get() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__);
        
        return fopen($this->_fileSystemPath, 'r');
    }

    public function getSize() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $this->_fileSystemPath);
        
        return $this->_node->size;
    }
    
    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     */ 
    public function getContentType() 
    {
        return $this->_node->contenttype;
    }
    
    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbritrary string, but MUST be surrounded by double-quotes.
     */
    public function getETag() 
    {
        return '"' . $this->_node->hash . '"';
    }
    
    /**
     * Deleted the current node
     *
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void 
     */
    public function delete() 
    {
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_DELETE)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to edit file: ' . $this->_path);
        }
        
        Tinebase_FileSystem::getInstance()->unlink(substr($this->_fileSystemPath, 9));
    }
    
    public function put($data)
    {
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_EDIT)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to edit file: ' . $this->_path);
        }
        
        $path = $this->_fileSystemPath;
        
        if (!$handle = fopen($path, 'w')) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $path . ')');
        }
        
        if (is_resource($data)) {
            stream_copy_to_stream($data, $handle);
        }
        
        fclose($handle);
    }
}
