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
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => time(),
            'id'                => $this->_container->getId(),
            'uri'               => $this->_useIdAsName == true ? $this->_container->getId() : $this->_container->name,
        	'{DAV:}resource-id'	=> 'urn:uuid:' . $this->_container->getId(),
        	'{DAV:}owner'       => new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id),
            #'principaluri'      => $principalUri,
            #'{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description' => $this->_container->description,
            '{DAV:}displayname' => $this->_container->type == Tinebase_Model_Container::TYPE_SHARED && $this->_useIdAsName == true ? $this->_container->name . ' (shared)' : $this->_container->name,
            '{http://apple.com/ns/ical/}calendar-color' => $this->_container->color,
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT')),
        	'{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-data'          => new Sabre_CalDAV_Property_SupportedCalendarData(/*'3.0'*/)
        );
        
        $response = array();
    
        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($response, true));
        
        return $response;
    }
}
