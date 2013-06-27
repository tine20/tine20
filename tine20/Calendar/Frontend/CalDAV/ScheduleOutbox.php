<?php

use Sabre\VObject;
use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\CalDAV;

/**
 * Tine 2.0
 *
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle schedule inbox in CalDAV tree
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_CalDAV_ScheduleOutbox extends DAV\Collection implements DAV\IProperties, CalDAV\ICalendar, CalDAV\Schedule\IOutbox
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
     * @return Sabre\DAV\INode[]
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
            '{DAV:}owner'       => new DAVACL\Property\Principal(DAVACL\Property\Principal::HREF, 'principals/users/' . $this->_user->contact_id),
            #'principaluri'      => $principalUri,
            '{DAV:}displayname' => 'Schedule Outbox',
            '{http://apple.com/ns/ical/}calendar-color' => '#666666',
            '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
            '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-data'          => new CalDAV\Property\SupportedCalendarData(),
            '{' . CalDAV\Plugin::NS_CALDAV . '}calendar-description'               => 'Calendar schedule outbox',
            '{' . CalDAV\Plugin::NS_CALDAV . '}calendar-timezone'                => $this->_getCalendarVTimezone()
        );
    
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $properties['{' . CalDAV\Plugin::NS_CALDAV . '}calendar-user-address-set'    ] = new DAV\Property\HrefList(array('mailto:' . Tinebase_Core::getUser()->accountEmailAddress), false);
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
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-query-freebusy',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            ),
            array(
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-post-vevent',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}read',
                'principal' => 'principals/users/' . $this->_user->contact_id,
                'protected' => true,
            ),
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
    public function setACL(array $acl) {

        throw new DAV\Exception\MethodNotAllowed('You\'re not allowed to update the ACL');

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
        $vcalendar = new VObject\Component('CALENDAR');
        $vcalendar->add(new Sabre_VObject_Component_VTimezone($timezone));
        
        // Taking out \r to not screw up the xml output
        return str_replace("\r","", $vcalendar->serialize());
    }
    
    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    public function getSupportedPrivilegeSet() {

        $default = DAVACL\Plugin::getDefaultSupportedPrivilegeSet();
        $default['aggregates'][] = array(
            'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-query-freebusy',
        );
        $default['aggregates'][] = array(
            'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}schedule-post-vevent',
        );

        return $default;

    }
    
    public function calendarQuery(array $filters)
    {
        return array();
    }
}
