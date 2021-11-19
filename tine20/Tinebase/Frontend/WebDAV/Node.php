<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav requests for Tinebase
 * 
 * @package     Tinebase
 * 
 * @todo extend Tinebase_Frontend_WebDAV_Record? or maybe add a common ancestor
 */
abstract class Tinebase_Frontend_WebDAV_Node implements Sabre\DAV\INode, \Sabre\DAV\IProperties,
    Tinebase_Frontend_WebDAV_IRenamable
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
        $this->_path      = $_path;

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filesystem path: ' . $_path);
        
        try {
            $this->_node = Tinebase_FileSystem::getInstance()->stat($_path);
        } catch (Tinebase_Exception_NotFound $tenf) {}
        
        if (! $this->_node) {
            throw new Sabre\DAV\Exception\NotFound('Filesystem path: ' . $_path . ' not found');
        }
    }
    
    public function getId()
    {
        return $this->_node->getId();
    }
    
    public function getName() 
    {
        list(, $basename) = Sabre\DAV\URLUtil::splitPath($this->_path);
        
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
     * @throws Sabre\DAV\Exception\Forbidden
     * @param string $name The new name
     * @return void
     */
    public function setName($name) 
    {
        self::checkForbiddenFile($name);
        
        if (!Tinebase_Core::getUser()->hasGrant($this->_getContainer(), Tinebase_Model_Grants::GRANT_EDIT)) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        
        list($dirname, $basename) = Sabre\DAV\URLUtil::splitPath($this->_path);

        if (!($result = Tinebase_FileSystem::getInstance()->rename($this->_path, $dirname . '/' . $name))) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        $this->_node = $result;
        $this->_path = $result->path;
    }

    public function rename(string $newPath)
    {
        if (!($result = Tinebase_FileSystem::getInstance()->rename($this->_path, $newPath))) {
            throw new Sabre\DAV\Exception\Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        $this->_node = $result;
        $this->_path = $result->path;
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
            

            if ($this->_node instanceof Tinebase_Model_Tree_Node) {
                $this->_container = Tinebase_FileSystem::getInstance()->get($this->_node->parent_id);
            } else if ($this->_node instanceof Tinebase_Model_Container) {
                if ($pathParts[2] == Tinebase_Model_Container::TYPE_SHARED) {
                    $containerId = $pathParts[3];
                } else {
                    $containerId = $pathParts[4];
                }
                $this->_container = Tinebase_Container::getInstance()->get($containerId);
            }
        }
        
        return $this->_container;
    }
    
   /**
    * checks if filename is acceptable
    *
    * @param  string $name
    * @throws Sabre\DAV\Exception\Forbidden
    */
    public static function checkForbiddenFile($name)
    {
        if (in_array($name, self::$_forbiddenNames)) {
            throw new Sabre\DAV\Exception\Forbidden('forbidden name');
        } else if (substr($name, 0, 2) == '._') {
            throw new Sabre\DAV\Exception\Forbidden('no resource files accepted');
        }
    }

    /**
     * return etag
     *
     * @return string
     */
    public function getETag()
    {
        return '"' . (empty($this->_node->hash) ? sha1($this->_node->object_id) : $this->_node->hash) . '"';
    }

    /**
     * Returns the content sequence for this container
     *
     * @return string
     */
    public function getSyncToken()
    {
        // this only returns null if the container is not found or if container.content_seq = NULL, this does not look up the content history!
        return $this->_node->seq;
    }

    /**
     * returns the nodes size
     *
     * @return integer
     */
    public function getSize()
    {
        return (int)$this->_node->size;
    }

    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties)
    {
        $response = array();

        foreach ($requestedProperties as $prop) {
            switch($prop) {
                case '{DAV:}getcontentlength':
                    if ($this->_node->type !== Tinebase_Model_Tree_FileObject::TYPE_FOLDER) {
                        $response[$prop] = $this->getSize();
                    }
                    break;

                case '{http://owncloud.org/ns}size':
                    $response[$prop] = $this->getSize();
                    break;

                case '{DAV:}getetag':
                    $response[$prop] = $this->getETag();
                    break;

                case '{DAV:}sync-token':
                    if (Tinebase_Config::getInstance()->get(Tinebase_Config::WEBDAV_SYNCTOKEN_ENABLED)) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport enabled');
                        $response[$prop] = $this->getSyncToken();
                    } else {
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' SyncTokenSupport disabled');
                    }
                    break;
            }
        }

        return $response;
    }

    public function updateProperties($mutations)
    {
        return false;
    }

    public function getNode()
    {
        return $this->_node;
    }
}
