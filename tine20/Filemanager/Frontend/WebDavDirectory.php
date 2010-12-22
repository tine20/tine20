<?php
/**
 * Tine 2.0
 * 
 * @package     Filemanager
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class to handle webdav requests for filemanager
 * 
 * @package     Filemanager
 */
class Filemanager_Frontend_WebDavDirectory extends Filemanager_Frontend_WebDavNode implements Sabre_DAV_ICollection
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
        
        if (!file_exists($this->_fileSystemPath)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $this->_path . ' could not be found');
        }
    }
    
    public function getChildren() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_fileSystemPath);
        
        $children = array();
            
        // Loop through the directory, and create objects for each node
        foreach(scandir($this->_fileSystemPath) as $name) {
            // Ignoring files staring with .
            #if ($node[0]==='.') continue;
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' name: ' . $name);
            $children[] = $this->getChild($name);
        }
        
        return $children;
    }
    
    public function getChild($name) 
    {
        $path = $this->_fileSystemPath . '/' . $name;
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $path);
        
        // We have to throw a FileNotFound exception if the file didn't exist
        if (!$this->childExists($name)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
        }
        // Some added security
        
        if ($name[0]=='.')  {
            throw new Sabre_DAV_Exception_FileNotFound('Access denied');
        }
        
        if (is_dir($path)) {
            return new Filemanager_Frontend_WebDav($this->_path . '/' . $name);
        } else {
            return new Filemanager_Frontend_WebDavFile($this->_path . '/' . $name);
        }
    }
    
    public function childExists($name) 
    {
        $path = $this->_fileSystemPath . '/' . $name;
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__ . ' exists: ' . $path);
        
        return file_exists($path);
    }

    /**
     * Creates a new file in the directory 
     * 
     * @param string $name Name of the file 
     * @param resource $data Initial payload, passed as a readable stream resource. 
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createFile($name, $data = null) 
    {
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_ADD)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to create file: ' . $name);
        }
        
        $path = $this->_fileSystemPath . '/' . $name;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $path);
        #throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $path . ')');

        if (!$handle = fopen($path, 'x')) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $path . ')');
        }
        
        if (is_resource($data)) {
            stream_copy_to_stream($data, $handle);
        }
        
        fclose($handle);
    }

    /**
     * Creates a new subdirectory 
     * 
     * @param string $name 
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function createDirectory($name) 
    {
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_ADD)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to create folder: ' . $name);
        }
        
        $path = $this->_fileSystemPath . '/' . $name;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create directory: ' . $path);
        
        mkdir($path);
    }
    
    /**
     * Deleted the current node
     * 
     * @todo   use filesystem controller to delete directories recursive
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void 
     */
    public function delete() 
    {
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_DELETE)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to delete directory: ' . $this->_path);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete directory: ' . $this->_fileSystemPath);
        
        foreach ($this->getChildren() as $child) {
            $child->delete();
        }
        
        if (!rmdir($this->_fileSystemPath)) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to delete node');
        }
        
        if ($this->_fileSystemPath == $this->_containerPath) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete container');
            Tinebase_Container::getInstance()->delete($this->_container);
        }
    }
    
    /**
     * Renames the node
     * 
     * @throws Sabre_DAV_Exception_Forbidden
     * @param string $name The new name
     * @return void
     */
    public function setName($name) 
    {
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_EDIT)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        
        rename($this->_fileSystemPath, dirname($this->_fileSystemPath) . '/' . $name);
    }
}