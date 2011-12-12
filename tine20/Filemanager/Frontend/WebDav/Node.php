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
abstract class Filemanager_Frontend_WebDav_Node implements Sabre_DAV_INode
{
    protected $_path;
    
    /**
     * @deprecated
     * @var unknown_type
     */
    protected $_applicationName;
    
    /**
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    /**
     * the current container
     * 
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    protected $_containerPath;
    
    protected $_fileSystemPath;
    
    protected $_fileSystemBasePath;
    
    /**
     * @var Tinebase_Model_Tree_Node
     */
    protected $_node;
    
    public function __construct($_path) 
    {
        $this->_path               = $_path;
        $this->_application        = Tinebase_Application::getInstance()->getApplicationByName('Filemanager');
        $this->_fileSystemBasePath = 'tine20:///' . $this->_application->getId() . '/folders';
        $this->_fileSystemPath     = $this->_fileSystemBasePath;
        
        $this->_parsePath();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filesystem path: ' . $this->_fileSystemPath);
    }
    
    public function getName() 
    {
        list(, $basename) = Sabre_DAV_URLUtil::splitPath($this->_path);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' name: ' . $basename);
        
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
        if (!Tinebase_Core::getUser()->hasGrant($this->_container, Tinebase_Model_Grants::GRANT_EDIT)) {
            throw new Sabre_DAV_Exception_Forbidden('Forbidden to rename file: ' . $this->_path);
        }
        
        rename($this->_fileSystemPath, dirname($this->_fileSystemPath) . '/' . $name);
    }
    
    /**
     * parse the path
     * path can be: 
     * 	 /applicationname/shared/containername(/*)
     *   /applicationname/personal/username/containername(/*)
     *   
     * @todo use Filemanager_Controller_Node::getContainer to fetch container
     */
    protected function _parsePath()
    {
        // split path into parts
        $pathParts = explode('/', trim($this->_path, '/'), 4);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' PATH PARTS: ' . print_r($pathParts, true));
        
        if (!empty($pathParts[1])) {
            $containerType          = strtolower($pathParts[1]);
            $this->_fileSystemPath .= '/' . $containerType;
            
            switch($containerType) {
                case Tinebase_Model_Container::TYPE_SHARED:
                    if (!empty($pathParts[2])) {
                        try {
                            $this->_container = Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $pathParts[2], $containerType, Tinebase_Core::getUser());
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $pathParts[2] . ' could not be found');
                        }
                        $this->_containerPath   = $this->_fileSystemPath . '/' . $this->_container->getId();
                        $this->_fileSystemPath .= '/' . $this->_container->getId();
                        
                        if (!empty($pathParts[3])) {
                            $this->_fileSystemPath .= '/' . $pathParts[3];
                        }
                    }
                    
                    break;
                    
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    if (!empty($pathParts[2])) {
                        if ($pathParts[2] != Tinebase_Core::getUser()->accountLoginName) {
                            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $pathParts[2] . ' could not be found');
                        }
                        $this->_fileSystemPath .= '/' . Tinebase_Core::getUser()->accountId;
                        
                        if (!empty($pathParts[3])) {
                            // explode again
                            $subPathParts = explode('/', $pathParts[3], 2);
                            
                            try {
                                $this->_container = Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $subPathParts[0], $containerType, Tinebase_Core::getUser());
                            } catch (Tinebase_Exception_NotFound $tenf) {
                                throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $subPathParts[0] . ' could not be foundd');
                            }
                            $this->_containerPath   = $this->_fileSystemPath . '/' . $this->_container->getId();
                            $this->_fileSystemPath .= '/' . $this->_container->getId();
                            
                            if (!empty($subPathParts[1])) {
                                $this->_fileSystemPath .= '/' . $subPathParts[1];
                            }
                        }
                    }
                    break;
                    
                default:
                    throw new Sabre_DAV_Exception_FileNotFound();
                    break;
            }
        }
    }
}
