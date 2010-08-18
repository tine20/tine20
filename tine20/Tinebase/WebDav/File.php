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
 * class to handle webdav file
 * 
 * @package     Tinebase
 * @subpackage  WebDav
 */
class Tinebase_WebDav_File extends Sabre_DAV_File 
{
    protected $_path;
    
    protected $_applicationName;
    
    protected $_containerType;
    
    protected $_containerName;
    
    protected $_username;
        
    public function __construct($path) 
    {
        $this->_path = $path;
        error_log(__METHOD__ . ' ' . __LINE__ . ' PATH: ' . $this->_path);

        $this->_parsePath();
        
        error_log(__METHOD__ . ' ' . __LINE__ . ' APPLICATION: ' . $this->_applicationName);
        error_log(__METHOD__ . ' ' . __LINE__ . ' ENTRY ID: ' . $this->_entryId);
    }
    
    public function getContentType()
    {
        return 'text/x-vcard';
    }
    
    protected function _parsePath()
    {
        $pathParts = explode('/', ltrim($this->_path, '/'));
        
        $this->_applicationName = $pathParts[0];
        $this->_containerType   = $pathParts[1];

        switch($this->_containerType) {
            case Tinebase_WebDav_Application_Container::CONTAINER_SHARED:
                $this->_containerName = $pathParts[2];
                $this->_entryId       = substr($pathParts[3], 0, -4);
                
                break;
                
            case Tinebase_WebDav_Application_Container::CONTAINER_USERS:
                $this->_username      = $pathParts[2];
                $this->_containerName = $pathParts[3];
                $this->_entryId       = substr($pathParts[4], 0, -4);
                
                break;
                
            default:
                throw new Sabre_DAV_Exception_FileNotFound();
                break;
        }
    }
    
    function getName() 
    {
      return basename($this->_path);
    }
    
    function get() 
    {    
        $contentController   = Tinebase_Core::getApplicationInstance($this->_applicationName, 'Contact');
        $entry = $contentController->get($this->_entryId);
        return "BEGIN:VCARD                                                                                                                                                                                                
EMAIL:{$entry->email}                                                                                                                                                                                     
FN:{$entry->n_fn}                                                                                                                                                                                           
N:{$entry->n_family};{$entry->n_given};;;
UID:{$entry->id}
VERSION:3.0
END:VCARD";
        
        #return $this->_vcards['lars.vcf'];
        #return fopen($this->myPath,'r');
    }
    
    function getSize() 
    {
        return strlen($this->get());
        #return filesize($this->myPath);
    }    
}