<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * 
 */

/**
 * class to handle CalDAV requests for calendar
 * 
 * @package     Calendar
 */
class Calendar_Frontend_CalDAV extends Sabre_DAV_Directory
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
            case 'caldav':
                return $this;
                break;
                
            case Sabre_CalDAV_Plugin::CALENDAR_ROOT:
                $authBackend = new Tinebase_WebDav_Auth();
                $calendarBackend = new Calendar_Frontend_CalDAV_Backend();
                
                return new Sabre_CalDAV_CalendarRootNode($authBackend, $calendarBackend);
                break;
                
            case Sabre_DAV_Auth_PrincipalCollection::NODENAME:
                $authBackend = new Tinebase_WebDav_Auth();
                
                return new Sabre_DAV_Auth_PrincipalCollection($authBackend);
                break;
                
            default:
                throw new Sabre_DAV_Exception_FileNotFound();
                break;
        }
    }

    public function getName()
    {
        return basename($this->_path);
    }
    
    #public function getNodeForPath() 
    #{
    #    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' exists: ' . $this->_path);
    #    
    #    $objectTree = new Sabre_DAV_ObjectTree($this);
    #    return $objectTree->getNodeForPath($this->_path);
    #}
}
