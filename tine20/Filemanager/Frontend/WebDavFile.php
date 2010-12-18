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
class Filemanager_Frontend_WebDavFile extends Sabre_DAV_File
{
    protected $_path;
    
    protected $_applicationName;
    
    /**
     * the current container
     * 
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    protected $_filesystemPath;
    
    public function __construct($path) 
    {
        $this->_path = $path;

        $this->_parsePath();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filesystem path: ' . $this->_filesystemPath);
    }
    
    public function getName() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH: ' . basename($this->_path));
        
        return basename($this->_path);
    }

    public function get() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__);
        
        return fopen($this->_filesystemPath, 'r');
    }

    public function getSize() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $this->_filesystemPath);
        
        return filesize($this->_filesystemPath);
    }
    
    /**
     * Returns the mime-type for a file
     *
     * If null is returned, we'll assume application/octet-stream
     */ 
    public function getContentType() 
    {
        $tinebaseFileSystem = new Tinebase_FileSystem();
        
        $contentType = $tinebaseFileSystem->getContentType(substr($this->_filesystemPath, 9));
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_filesystemPath . ' => ' . $contentType);
        
        return $contentType;
    }
    
    /**
     * Returns the ETag for a file
     *
     * An ETag is a unique identifier representing the current version of the file. If the file changes, the ETag MUST change.
     * The ETag is an arbritrary string, but MUST be surrounded by double-quotes.
     */
    public function getETag() 
    {
        $stat = stat($this->_filesystemPath);
        
        $etag = sha1(sprintf('%u', $stat['ino']) . '-' .$stat['mtime']);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' etag for file: ' . $this->_filesystemPath . ' ' . $etag);
        
        return '"' . $etag . '"';
    }
    
    /**
     * Returns the last modification time 
     *
     * @return int 
     */
    public function getLastModified()
    {
        return filemtime($this->_filesystemPath);
    }
    
    /**
     * Deleted the current node
     *
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void 
     */
    public function delete() 
    {
        unlink($this->_filesystemPath);
    }
    
    /**
     * parse the path
     * path can be: 
     * 	 /applicationname/shared/containername(/*)
     *   /applicationname/personal/username/containername(/*)
     */
    protected function _parsePath()
    {
        // split path into parts
        $pathParts = explode('/', trim($this->_path, '/'), 4);
        
        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' PATH PARTS: ' . print_r($pathParts, true));
        
        $this->_applicationName = ucfirst(strtolower($pathParts[0]));
        $containerType          = strtolower($pathParts[1]);

        switch($containerType) {
            case Tinebase_Model_Container::TYPE_SHARED:
                $containerName         = $pathParts[2];
                $this->_container      = Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $containerName, $containerType);
                $this->_filesystemPath = 'tine20:///' . Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId() . '/folders/' . $this->_container->type . '/' . $this->_container->getId();
                $this->_filesystemPath = isset($pathParts[3]) ? $this->_filesystemPath . '/' . $pathParts[3] : $this->_filesystemPath;
                
                break;
                
            case Tinebase_Model_Container::TYPE_PERSONAL:
                $this->_username      = $pathParts[2];
                // needs more splitting
                $this->_containerName = $pathParts[3];
                break;
                
            default:
                throw new Sabre_DAV_Exception_FileNotFound();
                break;
        }
    }
}