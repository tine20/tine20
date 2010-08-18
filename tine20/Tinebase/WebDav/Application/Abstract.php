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
    protected $_applicationName;
    
    public function __construct($path) 
    {
        $this->_applicationName = ucfirst($path);
        error_log(__METHOD__ . ' ' . __LINE__ . ' APPLICATION: ' . $this->_applicationName);
    }
    
    public function getChildren() 
    {
        $children = array();
        
        $children[] = $this->getChild(Tinebase_WebDav_Application_Container::CONTAINER_USERS);
        $children[] = $this->getChild(Tinebase_WebDav_Application_Container::CONTAINER_SHARED);
        
        return $children;            
    }
    
    public function getChild($name) 
    {
        $path = $this->_applicationName . '/' . $name;
        
        #// We have to throw a FileNotFound exception if the file didn't exist
        #if (!file_exists($this->myPath)) throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
        #// Some added security
        
        return new Tinebase_WebDav_Application_Container($path);
    }
    
    public function getName() 
    {
        return strtolower($this->_applicationName);
    }    
}