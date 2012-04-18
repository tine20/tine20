<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for Tinebase
 * 
 * @package     Tinebase
 */
abstract class Tinebase_Frontend_WebDAV_Node implements Sabre_DAV_INode
{
    protected $_path;
    
    /**
     * @var Tinebase_Model_Tree_Node
     */
    protected $_node;
    
    protected $_container;
    
    /**
     * @var array list of forbidden file names
     */
    protected static $_forbiddenNames = array('.DS_Store', 'Thumbs.db');
    
    public function __construct($_path) 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filesystem path: ' . $_path);
        
        try {
            $this->_node = Tinebase_FileSystem::getInstance()->stat($_path);
        } catch (Tinebase_Exception_NotFound $tenf) {
            throw new Sabre_DAV_Exception_FileNotFound('Filesystem path: ' . $_path . ' not found');
        }
        
        $this->_path      = $_path;
    }
    
    public function getName() 
    {
        list(, $basename) = Sabre_DAV_URLUtil::splitPath($this->_path);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' name: ' . $basename);
        
        return $basename;
    }

    /**
     * Returns the last modification time 
     *
     * @return int 
     */
    public function getLastModified()
    {
        if ($this->_node instanceof Tinebase_Model_Tree_Node) {
            if ($this->_node->last_modified_time instanceof Tinebase_DateTime) {
                $timestamp = $this->_node->last_modified_time->getTimestamp();
            } else {
                $timestamp = $this->_node->creation_time->getTimestamp();
            }
        } else {
            $timestamp = Tinebase_DateTime::now()->getTimestamp();
        }
        
        return $timestamp;
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
        self::checkForbiddenFile($name);
        
        if (!Tinebase_Core::getUser()->hasGrant($this->_getContainer(), Tinebase_Model_Grants::GRANT_EDIT)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        
        list($dirname, $basename) = Sabre_DAV_URLUtil::splitPath($this->_path);
        
        Tinebase_FileSystem::getInstance()->rename($this->_path, $dirname . '/' . $name);
    }    
    
    /**
     * return container for given path
     * 
     * @return Tinebase_Model_Container
     */
    protected function _getContainer()
    {
        if ($this->_container == null) {
            $pathParts = explode('/', substr($this->_path, 1), 7);
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' name: ' . print_r($pathParts, true));
            
            if ($pathParts[2] == Tinebase_Model_Container::TYPE_SHARED) {
                $containerId = $pathParts[3];
            } else {
                $containerId = $pathParts[4];
            }
            
            $this->_container = Tinebase_Container::getInstance()->get($containerId);
        }
        
        return $this->_container;
    }
    
   /**
    * checks if filename is acceptable
    *
    * @param  string $name
    * @throws Sabre_DAV_Exception_Forbidden
    */
    public static function checkForbiddenFile($name)
    {
        if (in_array($name, self::$_forbiddenNames)) {
            throw new Sabre_DAV_Exception_Forbidden('forbidden name');
        } else if (substr($name, 0, 2) == '._') {
            throw new Sabre_DAV_Exception_Forbidden('no resource files accepted');
        }
    }
}
