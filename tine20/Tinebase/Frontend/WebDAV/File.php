<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for Tinebase
 * 
 * @package     Tinebase
 */
class Tinebase_Frontend_WebDAV_File extends Tinebase_Frontend_WebDAV_Node implements Sabre\DAV\IFile
{
    public function get() 
    {
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! $pathRecord->isRecordPath() && ! Tinebase_FileSystem::getInstance()->checkPathACL(
                $pathRecord->getParent(),
                Tinebase_Model_Grants::GRANT_DOWNLOAD,
                true, false
            )
        ) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to download file: ' . $this->_path);
        }
        $handle = Tinebase_FileSystem::getInstance()->fopen($this->_path, 'r');
         
        return $handle;
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
     * @throws Sabre\DAV\Exception\Forbidden
     * @return void 
     */
    public function delete() 
    {
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! Tinebase_FileSystem::getInstance()->checkPathACL(
                $pathRecord->getParent(),
                Tinebase_Model_Grants::GRANT_DELETE,
                true, false
            )
        ) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to edit file: ' . $this->_path);
        }
        
        Tinebase_FileSystem::getInstance()->unlink($this->_path);
    }
    
    public function put($data)
    {
        $pathRecord = Tinebase_Model_Tree_Node_Path::createFromStatPath($this->_path);
        if (! Tinebase_FileSystem::getInstance()->checkPathACL(
                $pathRecord->getParent(),
                Tinebase_Model_Grants::GRANT_EDIT,
                true, false
            )) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to edit file: ' . $this->_path);
        }
        
        $handle = Tinebase_FileSystem::getInstance()->fopen($this->_path, 'w');
        
        if (!is_resource($handle)) {
            throw new Sabre\DAV\Exception\Forbidden('Permission denied to create file:' . $this->_path );
        }
        
        if (is_resource($data)) {
            stream_copy_to_stream($data, $handle);
        }

        // save file object
        Tinebase_FileSystem::getInstance()->fclose($handle);

        // refetch data
        $this->_node = Tinebase_FileSystem::getInstance()->stat($this->_path);

        return $this->getETag();
    }
}
