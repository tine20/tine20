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
     * (non-PHPdoc)
     * @see Sabre_DAV_Collection::getChild()
     */
    public function getChild($_name)
    {
        $modelName = $this->_application->name . '_Model_' . $this->_model;
        
        if ($_name instanceof $modelName) {
            $object = $_name;
        } else {
            $filterClass = $this->_application->name . '_Model_' . $this->_model . 'Filter';
            $filter = new $filterClass(array(
                array(
                    'field'     => 'container_id',
                    'operator'  => 'equals',
                    'value'     => $this->_container->getId()
                ),
                array('condition' => 'OR', 'filters' => array(
                    array(
                        'field'     => 'id',
                        'operator'  => 'equals',
                        'value'     => $this->_getIdFromName($_name)
                    ),
                    array(
                        'field'     => 'uid',
                        'operator'  => 'equals',
                        'value'     => $this->_getIdFromName($_name)
                    )
                ))
            ));
            $object = $this->_getController()->search($filter, null, false, false, 'sync')->getFirstRecord();
        
            if ($object == null) {
                throw new Sabre_DAV_Exception_FileNotFound('Object not found');
            }
        }
        
        $httpRequest = new Sabre_HTTP_Request();
        
        // lie about existance of event of request is a PUT request from an ATTENDEE for an already existing event 
        // to prevent ugly (and not helpful) error messages on the client
        if (isset($_SERVER['REQUEST_METHOD']) && $httpRequest->getMethod() == 'PUT' && $httpRequest->getHeader('If-None-Match') === '*') {
            if (
                $object->organizer != Tinebase_Core::getUser()->contact_id && 
                Calendar_Model_Attender::getOwnAttender($object->attendee) !== null
            ) {
                throw new Sabre_DAV_Exception_FileNotFound('Object not found');
            }
        }
        
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;
        
        return new $objectClass($this->_container, $object);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    function getChildren()
    {
        $filterClass = $this->_application->name . '_Model_' . $this->_model . 'Filter';
        $filter = new $filterClass(array(
            array(
                'field'     => 'container_id',
                'operator'  => 'equals',
                'value'     => $this->_container->getId()
            ),
            array(
        		'field'    => 'period', 
        		'operator'  => 'within', 
        		'value'     => array(
        			'from'  => Tinebase_DateTime::now()->subWeek(4),
        			'until' => Tinebase_DateTime::now()->addYear(4)
    			)
            )
        ));
    
        /*
         * see http://forge.tine20.org/mantisbt/view.php?id=5122
        * we must use action 'sync' and not 'get' as
        * otherwise the calendar also return events the user only can see because of freebusy
        */
        $objects = $this->_getController()->search($filter, null, false, false, 'sync');
    
        $children = array();
    
        foreach ($objects as $object) {
            $children[] = $this->getChild($object);
        }
    
        return $children;
    }
    
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
