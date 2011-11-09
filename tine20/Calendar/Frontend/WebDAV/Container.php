<?php
/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle containers in CalDAV tree
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_WebDAV_Container extends Tinebase_WebDav_Container_Abstract implements Sabre_CalDAV_ICalendar
{
    protected $_applicationName = 'Calendar';
    
    protected $_model = 'Event';
    
    protected $_suffix = '.ics';
    
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties) 
    {
        $displayName = $this->_container->type == Tinebase_Model_Container::TYPE_SHARED ? $this->_container->name . ' (shared)' : $this->_container->name;
        
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => round(time()),
            'id'                => $this->_container->getId(),
            'uri'               => $this->_useIdAsName == true ? $this->_container->getId() : $this->_container->name,
        	'{DAV:}resource-id'	=> 'urn:uuid:' . $this->_container->getId(),
        	'{DAV:}owner'       => new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id),
            #'principaluri'      => $principalUri,
            '{DAV:}displayname' => $displayName,
            '{http://apple.com/ns/ical/}calendar-color' => (empty($this->_container->color)) ? '#000000' : $this->_container->color,
            
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT')),
        	'{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-data'          => new Sabre_CalDAV_Property_SupportedCalendarData(),
        	'{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-description'		       => 'Calendar ' . $displayName,
    		'{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-timezone'                => $this->_getCalendarVTimezone()
        );
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $properties['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-user-address-set'	] = new Sabre_DAV_Property_HrefList(array('mailto:' . Tinebase_Core::getUser()->accountEmailAddress), false); 
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . print_r($properties, true));
        
        $response = array();
    
        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . print_r($response, true));
        
        return $response;
    }
    
    protected function _getController()
    {
        if ($this->_controller === null) {
            $this->_controller = Calendar_Controller_MSEventFacade::getInstance();
        }
        
        return $this->_controller;
    }
    
    protected function _getCalendarVTimezone()
    {
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, Tinebase_Core::getUser()->getId());

        // create vcalendar object with timezone information
        $vcalendar = new Sabre_VObject_Component('CALENDAR');
        $vcalendar->add(new Sabre_VObject_Component_VTimezone($timezone));
        
        // Taking out \r to not screw up the xml output
        return str_replace("\r","", $vcalendar->serialize());
    }
}
