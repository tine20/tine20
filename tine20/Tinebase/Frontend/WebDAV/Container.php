<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2012-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in WebDAV tree
 *
 * @package     Tinebase
 * @subpackage  Frontend
 * 
 * @TODO extend from Tinebase_Frontend_WebDAV_Directory 
 *       and remove Tinebase_WebDav_Container_Abstract
 */
class Tinebase_Frontend_WebDAV_Container extends Tinebase_WebDav_Container_Abstract
{
    protected $_applicationName = 'Tinebase';
    
    protected $_model = 'File';
    
    protected $_suffix = null;
    
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
     * contructor
     * 
     * @param  string|Tinebase_Model_Application  $_application  the current application
     * @param  string                             $_container    the current path
     */
    public function __construct(Tinebase_Model_Container $_container, $_useIdAsName = false)
    {
        parent::__construct($_container, $_useIdAsName);
        
        $this->_path = '/' . $this->_application->getId() . '/folders/' . $this->_container->type . '/';
        
        if ($this->_container->type == Tinebase_Model_Container::TYPE_SHARED) {
            $this->_path .= $this->_container->getId();
        } else {
            $this->_path .= Tinebase_Core::getUser()->accountId . '/' . $this->_container->getId();
        }
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
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);
        
        if (!Tinebase_Core::getUser()->hasGrant($this->_getContainer(), Tinebase_Model_Grants::GRANT_ADD)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to create folder: ' . $name);
        }
    
        $path = $this->_path . '/' . $name;
    
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' create directory: ' . $path);
    
        Tinebase_FileSystem::getInstance()->mkdir($path);
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
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);
        
        if (!Tinebase_Core::getUser()->hasGrant($this->_getContainer(), Tinebase_Model_Grants::GRANT_ADD)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to create file: ' . $this->_path . '/' . $name);
        }
        
        $path = $this->_path . '/' . $name;
    
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE))
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $path);
    
        if (!$handle = Tinebase_FileSystem::getInstance()->fopen($path, 'x')) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename file://' . $path . ')');
        }
    
        if (is_resource($data)) {
            stream_copy_to_stream($data, $handle);
        }
    
        Tinebase_FileSystem::getInstance()->fclose($handle);
    }
    
    /**
     * Deleted the current container
     *
     * @todo   use filesystem controller to delete directories recursive
     * @throws Sabre_DAV_Exception_Forbidden
     * @return void
     */
    public function delete()
    {
        if (!Tinebase_Core::getUser()->hasGrant($this->_getContainer(), Tinebase_Model_Grants::GRANT_DELETE)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to delete directory: ' . $this->_path);
        }
    
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' delete directory: ' . $this->_path);
    
        foreach ($this->getChildren() as $child) {
            $child->delete();
        }
    
        if (!Tinebase_FileSystem::getInstance()->rmdir($this->_path)) {
            throw new Sabre_DAV_Exception_Forbidden('Permission denied to delete node');
        }
    
        Tinebase_Container::getInstance()->delete($this->_getContainer());
    }
    
    public function getChild($name)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path . '/' . $name);
        
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);
        
        try {
            $childNode = Tinebase_FileSystem::getInstance()->stat($this->_path . '/' . $name);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre_DAV_Exception_FileNotFound('file not found: ' . $this->_path . '/' . $name);
        }
        
        if ($childNode->type == Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
            return new $this->_directoryClass($this->_path . '/' . $name);
        } else {
            return new $this->_fileClass($this->_path . '/' . $name);
        }
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
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
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
    #    $displayName = $this->_container->name;
    #    
    #    $properties = array(
    #        'id'                                     => $this->_container->getId(),
    #        'uri'                                    => $this->_container->name,
    #        '{DAV:}displayname'                      => $displayName,
    #    );
    #    
        $response = array();
    # 
    #    foreach($requestedProperties as $prop) {
    #        if (isset($properties[$prop])) {
    #            $response[$prop] = $properties[$prop];
    #        }
    #    }
    #    
        return $response;
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
        Tinebase_Frontend_WebDAV_Node::checkForbiddenFile($name);
        
        if (!Tinebase_Core::getUser()->hasGrant($this->_getContainer(), Tinebase_Model_Grants::GRANT_EDIT)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        
        $this->_getContainer()->name = $name;
        Tinebase_Container::getInstance()->update($this->_getContainer());
    }
    
    /**
     * return container for given path
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getContainer()
    {
        return $this->_container;
    }
}
