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
 * class to handle webdav directory structure
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Directory extends Sabre_DAV_Directory 
{
    protected $_path;
    
    public function __construct($path) 
    {
        $this->_path = rtrim($path, '/');
        #error_log(__METHOD__ . ' ' . __LINE__ . ' PATH: ' . $this->_path);
    }
    
    public function getChildren() 
    {
        #error_log(__METHOD__ . ' ' . __LINE__ . ' PATH: ' . $this->_path);
        $children = array();
        
        if($this->_path == '') {
            $children[] = $this->getChild('webdav');
        
        } elseif ($this->_path == '/webdav') {
            // Loop through the directory, and create objects for each node
            foreach(array('Addressbook') as $application) {
                if(Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN)) {
                    $children[] = $this->getChild($application);
                }
            }
        }
             
        return $children;            
    }
    
    public function getChild($name) 
    {
        $path = $this->_path . '/' . $name;
        
        #error_log(__METHOD__ . ' ' . __LINE__ . ' NAME: ' . $name . ' PATH: ' . $path );
        
        
        #// We have to throw a FileNotFound exception if the file didn't exist
        #if (!file_exists($this->myPath)) throw new Sabre_DAV_Exception_FileNotFound('The file with name: ' . $name . ' could not be found');
        #// Some added security
        
        if($path == '/webdav') {
            return new Tinebase_WebDav_Directory($path);
        } else {
            return new Tinebase_WebDav_Application($path);
        }
    }
    
    public function getName() 
    {
        return basename($this->_path);
    }    
}