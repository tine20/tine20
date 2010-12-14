<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class to handle webdav root
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Root extends Sabre_DAV_Directory 
{
    protected $_path;
    
    public function __construct($_path) 
    {
        $this->_path = $_path;
    }
    
    public function getChildren() 
    {
        $children = array();
        
        // Loop through the directory, and create objects for each node
        foreach(Tinebase_Core::getUser()->getApplications() as $application) {
            $className = $application . '_Frontend_WebDav';
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' WebDav classname: ' . $className);
            if (@class_exists($className)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .' adding WebDav application: ' . $application);
                $children[] = $this->getChild($application);
            }
        }
        
        return $children;            
    }
    
    public function getChild($name) 
    {
        return new Tinebase_WebDav_Application($name);
    }
    
    public function getName() 
    {
        return basename($this->_path);
    }    
}