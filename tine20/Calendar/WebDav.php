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
class Calendar_WebDav extends Tinebase_WebDav_Application_Abstract 
{
    public function getChildren() 
    {
        $children = array();
        
        $children[] = $this->getChild('foobar');
        
        return $children;            
    }
    
    public function getChild($name) 
    {
        $authBackend = new Tinebase_WebDav_Auth();
        $pdo = new PDO('sqlite:/tmp/caldav.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        
        $calendarBackend = new Sabre_CalDAV_Backend_PDO($pdo);
        
        return new Sabre_CalDAV_CalendarRootNode($authBackend, $calendarBackend);
    }    
}