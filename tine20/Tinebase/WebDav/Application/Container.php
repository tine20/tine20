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
 * class to handle directory structure
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Application_Container extends Sabre_DAV_Directory
{
    protected $_path;
    
    /**
     * the current container
     * 
     * @var Tinebase_Model_Container
     */
    protected $_container;
    
    protected $_username;
    
    protected $_applicationName;
    
    protected $_contentController;
    
    protected $_containerPath;
    
    protected $_fileSystemPath;
    
    public function __construct($path) 
    {
        $this->_path = $path;

        $this->_parsePath();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filesystem path: ' . $this->_fileSystemPath);
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
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . ' PATH PARTS: ' . print_r($pathParts, true));
        
        $this->_applicationName = ucfirst(strtolower($pathParts[0]));
        $containerType          = strtolower($pathParts[1]);

        switch($containerType) {
            case Tinebase_Model_Container::TYPE_SHARED:
                $containerName         = $pathParts[2];
                $this->_container      = Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $containerName, $containerType);
                $this->_containerPath  = 'tine20:///' . Tinebase_Application::getInstance()->getApplicationByName($this->_applicationName)->getId() . '/folders/' . $this->_container->type . '/' . $this->_container->getId();
                $this->_fileSystemPath = isset($pathParts[3]) ? $this->_containerPath . '/' . $pathParts[3] : $this->_containerPath;
                
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
    
    /**
     * @todo rework
     */
    public function getChildren() 
    {
        $children = array();
        
        if($this->_containerType == Tinebase_Model_Container::TYPE_SHARED) {
			$container = Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $this->_containerName, Tinebase_Model_Container::TYPE_SHARED);

            $this->_contentFilterClass  = $this->_applicationName . '_Model_' . $this->_modelName . 'Filter';
            $filter = new $this->_contentFilterClass();
            $filter->addFilter(new Tinebase_Model_Filter_Container(
                'container_id', 
                'equals', 
                $container->getId(), 
                array('applicationName' => $this->_applicationName)
            ));
            
            $this->_contentController   = Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName);
            $entries = $this->_contentController->search($filter, null, false, true, 'export');
            
            foreach($entries as $entry) {
                $children[] = $this->getChild($entry . '.vcf');
            }
        } elseif($containerType == Tinebase_Model_Container::TYPE_PERSONAL) {
            
        }
                     
        return $children;            
    }
    
    public function getChild($name) 
    {
        $path = rtrim($this->_path, '/') . '/' . $name;
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' NAME: ' . $name . ' PATH: ' . $path );
        
        
        #// We have to throw a FileNotFound exception if the file didn't exist
        #if (!file_exists($this->myPath)) throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
        #// Some added security
        
        return new Tinebase_WebDav_File($path);
    }
    
    public function getName() 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' ' . basename($this->_path));
        
        return basename($this->_path);
    }
}
