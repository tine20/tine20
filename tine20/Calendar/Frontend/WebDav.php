<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 */

/**
 * class to handle webdav requests for calendar
 * 
 * @package     Calendar
 */
class Calendar_Frontend_WebDav extends Sabre_DAV_Directory
{
    protected $_path;
    
    public function __construct($_path)
    {
        $this->_path = $_path;
    }
    
    public function getChildren() 
    {
        $children = array();
        
        $children[] = $this->getChild(Sabre_CalDAV_Plugin::CALENDAR_ROOT);
        $children[] = $this->getChild(Sabre_DAV_Auth_PrincipalCollection::NODENAME);
        
        return $children;            
    }
    
    public function getChild($_name) 
    {
        switch ($_name) {
            case Sabre_CalDAV_Plugin::CALENDAR_ROOT:
                $authBackend = new Tinebase_WebDav_Auth();
                $pdo = new PDO('sqlite:/tmp/caldav.sqlite');
                $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
                
                $calendarBackend = new Sabre_CalDAV_Backend_PDO($pdo);
                
                return new Sabre_CalDAV_CalendarRootNode($authBackend, $calendarBackend);
                
                break;
                
            case Sabre_DAV_Auth_PrincipalCollection::NODENAME:
                $authBackend = new Tinebase_WebDav_Auth();
                
                return new Sabre_DAV_Auth_PrincipalCollection($authBackend);
                
                break;
                
            default:
                throw new Sabre_DAV_Exception_FileNotFound();
        }
    }

    public function getName()
    {
        return basename($this->_path);
    }
    
    public function getNodeForPath() 
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' exists: ' . $this->_path);
        
        $pathParts = explode('/', $this->_path);
        
        if (count($pathParts) > 1) {
            return $this->getChild($pathParts[1]);
        } else {
            return new Calendar_Frontend_WebDav($this->_path);
        }
    }
}