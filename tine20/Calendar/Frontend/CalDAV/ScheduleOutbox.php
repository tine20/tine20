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
 * class to handle schedule inbox in CalDAV tree
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_CalDAV_ScheduleOutbox extends Sabre_DAV_Collection implements Sabre_DAV_IProperties, Sabre_DAVACL_IACL, Sabre_CalDAV_ICalendar, Sabre_CalDAV_Schedule_IOutbox
{
    /**
     * @var Tinebase_Model_FullUser
     */
    protected $_user;
    
    public function __construct($_userId)
    {
        $this->_user = $_userId instanceof Tinebase_Model_FullUser ? $_userId : Tinebase_User::getInstance()->get($_userId);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre_DAV_INode[]
     */
    public function getChildren()
    {
        return array();
    }
    
    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getGroup()
    {
        return null;
    }
    
    /**
     * Returns the name of the node
     *
     * @return string
     */
    public function getName()
    {
        return 'schedule-outbox';
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner 
     * 
     * @return string|null
     */
    public function getOwner() 
    {
        return 'principals/users/' . $this->_user->contact_id;
    }
        
    /**
     * Returns the list of properties
     *
     * @param array $requestedProperties
     * @return array
     */
    public function getProperties($requestedProperties)
    {
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => round(time()/60),
            'id'                => 'schedule-outbox',
            'uri'               => 'schedule-outbox',
            '{DAV:}resource-id'    => 'urn:uuid:schedule-outbox',
            '{DAV:}owner'       => new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF, 'principals/users/' . $this->_user->contact_id),
            #'principaluri'      => $principalUri,
            '{DAV:}displayname' => 'Schedule Outbox',
            '{http://apple.com/ns/ical/}calendar-color' => '#666666',
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre_CalDAV_Property_SupportedCalendarComponentSet(array('VEVENT')),
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-data'          => new Sabre_CalDAV_Property_SupportedCalendarData(),
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-description'               => 'Calendar schedule outbox',
            '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-timezone'                => $this->_getCalendarVTimezone()
        );
    
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $properties['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}calendar-user-address-set'    ] = new Sabre_DAV_Property_HrefList(array('mailto:' . Tinebase_Core::getUser()->accountEmailAddress), false);
        }
    
        $response = array();
    
        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }
    
        return $response;
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL() 
    {
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            ),
            array(
                'privilege' => '{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}schedule-query-freebusy',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            )
        );
    }
    
    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    public function setACL(array $acl) 
    {
        throw new Sabre_DAV_Exception_MethodNotAllowed('Changing ACL is not yet supported');
    }
    
    /**
     * Updates properties on this node,
     *
     * The properties array uses the propertyName in clark-notation as key,
     * and the array value for the property value. In the case a property
     * should be deleted, the property value will be null.
     *
     * This method must be atomic. If one property cannot be changed, the
     * entire operation must fail.
     *
     * If the operation was successful, true can be returned.
     * If the operation failed, false can be returned.
     *
     * Deletion of a non-existant property is always succesful.
     *
     * Lastly, it is optional to return detailed information about any
     * failures. In this case an array should be returned with the following
     * structure:
     *
     * array(
     *   403 => array(
     *      '{DAV:}displayname' => null,
     *   ),
     *   424 => array(
     *      '{DAV:}owner' => null,
     *   )
     * )
     *
     * In this example it was forbidden to update {DAV:}displayname.
     * (403 Forbidden), which in turn also caused {DAV:}owner to fail
     * (424 Failed Dependency) because the request needs to be atomic.
     *
     * @param array $mutations
     * @return bool|array
     */
    public function updateProperties($mutations)
    {
        return false;
    }
    
    protected function _getCalendarVTimezone()
    {
        $timezone = Tinebase_Core::getPreference()->getValueForUser(Tinebase_Preference::TIMEZONE, $this->_user->getId());

        // create vcalendar object with timezone information
        $vcalendar = new Sabre_VObject_Component('CALENDAR');
        $vcalendar->add(new Sabre_VObject_Component_VTimezone($timezone));
        
        // Taking out \r to not screw up the xml output
        return str_replace("\r","", $vcalendar->serialize());
    }
}
