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
 * abstract class to handle applications
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
abstract class Tinebase_WebDav_Application_Abstract extends Sabre_DAV_Directory 
{
    protected $_path;
    protected $_applicationName;
    
    public function __construct($path) 
    {
        $this->_path = $path;
        
        $pathParts = explode('/', $this->_path, 2);
        $this->_applicationName = ucfirst(strtolower($pathParts[0]));
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' PATH: ' . $this->_path);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' APPLICATION: ' . $this->_applicationName);
    }
    
    public function getChildren() 
    {
        $children = array();
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' BASENAME: ' . basename($this->_path));
        
        switch (basename($this->_path)) {
            case Tinebase_WebDav_Application_Container::CONTAINER_USERS:
                break;
                
            case Tinebase_WebDav_Application_Container::CONTAINER_SHARED:
                $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Grants::GRANT_EXPORT);
                foreach ($containers as $container) {
                    $children[] = $this->getChild($container->name);
                }
                break;
                
            default:
                $children = array(
                    $this->getChild(Tinebase_WebDav_Application_Container::CONTAINER_USERS),
                    $this->getChild(Tinebase_WebDav_Application_Container::CONTAINER_SHARED)
                );
                break;
        }
        
        return $children;            
    }
    
    public function getChild($name) 
    {
        $path = $this->_path . '/' . $name;

        switch ($name) {
            case Tinebase_WebDav_Application_Container::CONTAINER_USERS:
            case Tinebase_WebDav_Application_Container::CONTAINER_SHARED:
                return new self($path);
                break;
                
            default:
                #// We have to throw a FileNotFound exception if the file didn't exist
                #if (!file_exists($this->myPath)) throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
                #// Some added security
                
                return new Tinebase_WebDav_Application_Container($path);
                
                break;
        }
    }
    
    public function getName() 
    {
        return strtolower($this->_applicationName);
    }    
}