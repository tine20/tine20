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
class Filemanager_Frontend_WebDav extends Tinebase_WebDav_Application_Container
{
    protected $_root;
    
    public function __construct($path) 
    {
        parent::__construct($path);
        
        if (!file_exists($this->_containerPath)) {
            mkdir($this->_containerPath, 0777, true);
        }
        
        if (!file_exists($this->_fileSystemPath)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $_this->_path . ' could not be found');
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
     * Returns the last modification time 
     *
     * @return int 
     */
    public function getLastModified() 
    {
        return filemtime($this->_fileSystemPath);
    }
    
    public function getName() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__ . ' name: ' . basename($this->_path));
        
        return basename($this->_path);
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
        $path = $this->_fileSystemPath . '/' . $name;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create directory: ' . $path);
        
        mkdir($path);
    }
    
    /**
     * Deleted the current node
     *
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void 
     */
    public function delete() 
    {
        $path = $this->_fileSystemPath;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete directory: ' . $path);
        
        if (!rmdir($path)) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to delete node');
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
        $path = $this->_fileSystemPath;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $path . ' => new name: ' . $name);
        
        throw new Sabre_DAV_Exception_Forbidden('Permission denied to rename file');
    }
}