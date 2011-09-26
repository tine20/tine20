<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle webdav root
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Filemanager_Frontend_WebDAV_Collection extends Sabre_DAV_Collection
{
    const ROOT_NODE = 'webdav';
    
    protected $_path;
    
    public function __construct($_path) 
    {
        $this->_path = $_path;
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAV_ICollection::getChildren()
     */
    public function getChildren() 
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' path: ' . $this->_path);
        
        $children = array();
        
        if (empty($this->_path)) {
            $children[] = $this->getChild(self::ROOT_NODE);
        } elseif ($this->_path == self::ROOT_NODE) {
            // Loop through the directory, and create objects for each node
            foreach(Tinebase_Core::getUser()->getApplications() as $application) {
                #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' application: ' . $application);
                try {
                    $children[] = $this->getChild($application);
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' added application: ' . $application);
                } catch (Sabre_DAV_Exception_FileNotFound $sdefnf) {
                    continue;
                }
            }
        }
        
        return $children;            
    }
    
    /**
     * (non-PHPdoc)
     * @see Sabre_DAV_Directory::getChild()
     */
    public function getChild($_name) 
    {
        if (empty($this->_path)) {
            return new Tinebase_WebDav_Root(self::ROOT_NODE);
        } elseif (strtolower($this->_path) == self::ROOT_NODE) {
            return $this->_getApplicationNode($_name);
        }
    }
    
    public function getName() 
    {
        return strtolower(basename($this->_path));
    }    
    
    public function getNodeForPath()
    {
        if (empty($this->_path) || strtolower($this->_path) == self::ROOT_NODE) {
            return new Tinebase_WebDav_Root(strtolower($this->_path));
        } else {
            $applicationNode = $this->_getApplicationNode(substr($this->_path, strlen(self::ROOT_NODE) +1));
            
            return $applicationNode->getNodeForPath();
        }
    }
    
    protected function _getApplicationNode($_appPath)
    {
        // appname[/subdir][/subdir]...
        $pathParts = explode('/', $_appPath, 2);
        list($appName, $appPath) = array(ucfirst($pathParts[0]), isset($pathParts[1]) ? $pathParts[1] : null);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' appname: ' . $appName . ' apppath: ' . $appPath . ' origPath: ' . $_appPath);

        if (!Tinebase_Application::getInstance()->isInstalled($appName)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $_appPath . ' could not be found');
        }
        
        if (!Tinebase_Core::getUser()->hasRight($appName, Tinebase_Acl_Rights::RUN)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $_appPath . ' could not be found');
        }

        $className = $appName . '_Frontend_WebDav';
        if (!@class_exists($className)) {
            throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $_appPath . ' could not be found');
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' classname: ' . $className);
        $applicationNode = new $className($_appPath);
        
        return $applicationNode;
    }
}
