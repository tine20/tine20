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
 * class to handle webdav root
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_Root extends Sabre_DAV_Directory 
{
    protected $_name;
    
    public function __construct($name) 
    {
        $this->_name = $name;
    }
    
    public function getChildren() 
    {
        $children = array();
        
        // Loop through the directory, and create objects for each node
        foreach(array('Addressbook', 'Calendar') as $application) {
            if(Tinebase_Core::getUser()->hasRight($application, Tinebase_Acl_Rights::RUN)) {
                $children[] = $this->getChild($application);
            }
        }
             
        return $children;            
    }
    
    public function getChild($name) 
    {
        $className = ucfirst($name) . '_WebDav';
        
        return new $className($name);
    }
    
    public function getName() 
    {
        return $this->_name;
    }    
}