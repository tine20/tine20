<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
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
    const CONTAINER_SHARED = 'shared';
    const CONTAINER_USERS  = 'users';
    
    protected $_path;
    
    protected $_containerType;
    
    protected $_containerName;
    
    protected $_username;
    
    protected $_applicationName;
    
    protected $_modelName = 'Contact';
    
    protected $_contentController;
    
    public function __construct($path) 
    {
        $this->_path = $path;
        error_log(__METHOD__ . ' ' . __LINE__ . ' PATH: ' . $this->_path);

        $this->_parsePath();
        
        error_log(__METHOD__ . ' ' . __LINE__ . ' APPLICATION: ' . $this->_applicationName);
        
        $this->_contentController   = Tinebase_Core::getApplicationInstance($this->_applicationName, $this->_modelName);
        $this->_contentFilterClass  = $this->_applicationName . '_Model_' . $this->_modelName . 'Filter';
    }
    
    protected function _parsePath()
    {
        $pathParts = explode('/', ltrim($this->_path, '/'));
        
        $this->_applicationName = $pathParts[0];
        $this->_containerType   = $pathParts[1];

        switch($this->_containerType) {
            case self::CONTAINER_SHARED:
                if(isset($pathParts[2])) {
                    $this->_containerName = $pathParts[2];
                }
                
                break;
                
            case self::CONTAINER_USERS:
                if(isset($pathParts[2])) {
                    $this->_username = $pathParts[2];
                }
                
                if(isset($pathParts[3])) {
                    $this->_containerName = $pathParts[3];
                }
                
                break;
                
            default:
                throw new Sabre_DAV_Exception_FileNotFound();
                break;
        }
    }
    
    
    public function getChildren() 
    {
        error_log(__METHOD__ . ' ' . __LINE__ . ' PATH: ' . $this->_path);
        error_log(__METHOD__ . ' ' . __LINE__ . ' APPLICATION: ' . $this->_applicationName);
        error_log(__METHOD__ . ' ' . __LINE__ . ' CONTAINERTYPE: ' . $this->_containerType);
        
        $children = array();
        
        if($this->_containerType == self::CONTAINER_SHARED) {
            // get list of containers
            if(!isset($this->_containerName)) {
                $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Grants::GRANT_EXPORT);
                foreach ($containers as $container) {
                    $children[] = $this->getChild($container->name);
                }
            } else {
                $container = Tinebase_Container::getInstance()->getContainerByName($this->_applicationName, $this->_containerName, Tinebase_Model_Container::TYPE_SHARED);

                $filter = new $this->_contentFilterClass();
                $filter->addFilter(new Tinebase_Model_Filter_Container(
                    'container_id', 
                    'equals', 
                    $container->getId(), 
                    array('applicationName' => $this->_applicationName)
                ));
                
                $entries = $this->_contentController->search($filter, null, false, true, 'export');
                
                foreach($entries as $entry) {
                    $children[] = $this->getChild($entry . '.vcf');
                }
            }
        } elseif($containerType == self::CONTAINER_USERS) {
            
        }
                     
        return $children;            
    }
    
    public function getContainerType()
    {
        return $this->_containerType;
    }
    
    public function getChild($name) 
    {
        error_log(__METHOD__ . ' ' . __LINE__ . ' ');
        $path = rtrim($this->_path, '/') . '/' . $name;
        
        error_log(__METHOD__ . ' ' . __LINE__ . ' NAME: ' . $name . ' PATH: ' . $path );
        
        
        #// We have to throw a FileNotFound exception if the file didn't exist
        #if (!file_exists($this->myPath)) throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
        #// Some added security
        
        if($this->_containerType == self::CONTAINER_SHARED && isset($this->_containerName)) {
            return new Tinebase_WebDav_File($path);
        } else {
            return new Tinebase_WebDav_Application_Container($path);
        }
    }
    
    public function getName() 
    {
        return basename($this->_path);
    }    
}