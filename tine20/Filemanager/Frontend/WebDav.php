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
class Filemanager_Frontend_WebDav extends Sabre_DAV_Directory
{
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
    
    protected $_fileSystemPath;
    
    protected $_fileSystemBasePath;
    
    protected $_root;
    
    public function __construct($path) 
    {
        $this->_path = $path;
        $this->_application = Tinebase_Application::getInstance()->getApplicationByName('Filemanager');
        $this->_fileSystemBasePath = 'tine20:///' . $this->_application->getId() . '/folders';
        
        $this->_parsePath();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $path); 
        
        if ($this->_container instanceof Tinebase_Model_Container && !file_exists($this->_containerPath)) {
            mkdir($this->_containerPath, 0777, true);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filesystem path: ' . $this->_fileSystemPath);
        if ($this->_fileSystemPath !== null && !file_exists($this->_fileSystemPath)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $this->_path . ' could not be found');
        }
    }
    
    /**
     * parse the path
     * path can be: 
     * 	 /shared/....
     *   /personal/loginname/....
     */
    protected function _parsePath()
    {
        $pathParts = explode('/', trim($this->_path, '/'), 4);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH PARTS: ' . print_r($pathParts, true));
        
        $this->_fileSystemPath = $this->_fileSystemBasePath;
        
        if (!empty($pathParts[1])) {
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH PART 1: ' . $pathParts[1]);
            $this->_containerType = strtolower($pathParts[1]);
            $this->_fileSystemPath .= '/' . strtolower($this->_containerType);
            
            switch ($this->_containerType) {
                case Tinebase_Model_Container::TYPE_SHARED:
                    if (!empty($pathParts[2])) {
                        try {
                            $this->_container      = Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $pathParts[2], $this->_containerType);
                        } catch (Tinebase_Exception_NotFound $tenf) {
                            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $pathParts[2] . ' could not be found');
                        }
                        $this->_containerPath  = $this->_fileSystemBasePath . '/' . $this->_container->type . '/' . $this->_container->getId();
                        $this->_fileSystemPath .= '/' . $this->_container->getId();
                        $this->_fileSystemPath = isset($pathParts[3]) ? $this->_fileSystemPath . '/' . $pathParts[3] : $this->_fileSystemPath;
                    }
                    break;
                    
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    break;
                    
                default:
                    throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $this->_containerType . ' could not be found');
                    break;
            }
        }
    }
    
    public function getChildren() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_fileSystemPath);
        
        $children = array();
            
        // top level directory of application
        if ($this->_fileSystemPath == $this->_fileSystemBasePath) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_fileSystemPath);
            $children[] = $this->getChild(Tinebase_Model_Container::TYPE_SHARED);
            #$children[] = $this->getChild(Tinebase_Model_Container::TYPE_PERSONAL);
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/shared') {
            $sharedContainers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_application->name, Tinebase_Model_Grants::GRANT_READ);
            
            foreach ($sharedContainers as $container) {
                $children[] = $this->getChild($container);
            }
        } else {
            // Loop through the directory, and create objects for each node
            foreach(scandir($this->_fileSystemPath) as $name) {
                // Ignoring files staring with .
                #if ($node[0]==='.') continue;
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' name: ' . $name);
                $children[] = $this->getChild($name);
            }
        }
        
        return $children;
    }
    
    public function getChild($name) 
    {
        if ($this->_fileSystemPath == $this->_fileSystemBasePath) {
            if ($name != Tinebase_Model_Container::TYPE_SHARED && $name != Tinebase_Model_Container::TYPE_PERSONAL) {
                throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path . '/' . $name);
            return new Filemanager_Frontend_WebDav($this->_path . '/' . $name);
        } elseif ($this->_fileSystemPath == $this->_fileSystemBasePath . '/shared') {
            try {
                $container = $name instanceof Tinebase_Model_Container ? $name : Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $name, $this->_containerType);
            } catch (Tinebase_Exception_NotFound $tenf) {
                throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
            }
            
            return new Filemanager_Frontend_WebDav($this->_path . '/' . $container->name);
        } else {
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
    }
    
    public function getNodeForCurrentPath() 
    {
        if (is_dir($this->_fileSystemPath)) {
            return new Filemanager_Frontend_WebDav($this->_path);
        } else {
            return new Filemanager_Frontend_WebDavFile($this->_path);
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
        $name = basename($this->_path);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__ . ' name: ' . $name);
        
        return $name;
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
        rename($this->_fileSystemPath, dirname($this->_fileSystemPath) . '/' . $name);
    }
}