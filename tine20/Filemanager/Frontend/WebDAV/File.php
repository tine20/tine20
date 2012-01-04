<?php
/**
 * Tine 2.0
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for filemanager
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_WebDAV_File extends Filemanager_Frontend_WebDAV_Node implements Sabre_DAV_IFile
{
    public function get() 
    {
        return Tinebase_FileSystem::getInstance()->fopen($this->_path, 'r');
    }

    public function getSize() 
    {
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
