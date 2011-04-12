<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * abstract class to handle applications
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
abstract class Tinebase_WebDav_Application extends Sabre_DAV_Directory 
{
    protected $_path;
    
    /**
     * @var Tinebase_Model_Application
     */
    protected $_application;
    
    protected $_applicationName;
    
    /**
     * @var string
     */
    protected $_containerType;
    
    /**
     * username part of the path for personal container
     * Enter description here ...
     * @var unknown_type
     */
    protected $_username;
    
    public function __construct($path) 
    {
        
    }
    
    /**
     * parse the path
     * path can be: 
     * 	 /applicationname/shared/
     *   /applicationname/personal/loginname/
     */
    protected function _parsePath()
    {
        $pathParts = explode('/', trim($this->_path, '/'), 3);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH PARTS: ' . print_r($pathParts, true));
        
        $this->_application   = Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName);
        if (!empty($pathParts[0])) {
            $this->_containerType = strtolower($pathParts[0]);
            
            switch ($this->_containerType) {
                case Tinebase_Model_Container::TYPE_SHARED:
                    break;
                    
                case Tinebase_Model_Container::TYPE_PERSONAL:
                    break;
                    
                default:
                    throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $this->_containerType . ' could not be found');
                    break;
            }
        }
        $this->_containerType = !empty($pathParts[0]) ? strtolower($pathParts[0]) : null;
        $this->_username      = isset($pathParts[1])  ? strtolower($pathParts[1]) : null;
    }
    
    public function getChildren() 
    {
        $children = array();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' BASENAME: ' . basename($this->_path));
        
        switch ($this->_containerType) {
            case Tinebase_Model_Container::TYPE_PERSONAL:
                break;
                
            case Tinebase_Model_Container::TYPE_SHARED:
                $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_application->name, Tinebase_Model_Grants::GRANT_EXPORT);
                foreach ($containers as $container) {
                    $children[] = $this->getChild($container->name);
                }
                break;
                
            default:
                $children = array(
                    $this->getChild(Tinebase_Model_Container::TYPE_PERSONAL),
                    $this->getChild(Tinebase_Model_Container::TYPE_SHARED)
                );
                break;
        }
        
        return $children;            
    }
    
    public function getChild($name) 
    {
        $path = $this->_path . '/' . $name;
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $path);
        
        switch ($name) {
            case Tinebase_Model_Container::TYPE_PERSONAL:
            case Tinebase_Model_Container::TYPE_SHARED:
                return new self($path);
                break;
                
            default:
                # We have to throw a FileNotFound exception if the file didn't exist
                try {
                    Tinebase_Container::getInstance()->getContainerByName($this->_application->name, $name, $this->_containerType);
                } catch (Tinebase_Exception_NotFound $tenf) {
                    throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
                }
                
                $className = $this->_application->name . '_Frontend_WebDav';
                
                return new $className($path);
                
                break;
        }
    }
    
    public function getName() 
    {
        return strtolower(basename($this->_path));
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
        throw new Sabre_DAV_Exception_Forbidden('Permission denied to create file (filename ' . $name . ')');
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
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' name: ' . $name . ' path: ' . $this->_path . ' type: ' . $this->_containerType);

        $container = Tinebase_Container::getInstance()->addContainer(new Tinebase_Model_Container(array(
            'name'           => $name,
            'type'           => $this->_containerType,
            'backend'        => 'sql',
            'application_id' => $this->_application->getId()
        )));
        
        Filemanager_Controller_Filesystem::getInstance()->createContainerNode($container);
    }    
}
