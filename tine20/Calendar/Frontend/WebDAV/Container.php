<?php

use Sabre\VObject;
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
 */

/**
 * class to handle containers in CalDAV tree
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_WebDAV_Container extends Tinebase_WebDav_Container_Abstract implements Sabre\CalDAV\ICalendar
{
    protected $_applicationName = 'Calendar';
    
    protected $_model = 'Event';
    
    protected $_suffix = '.ics';
    
    /**
     * (non-PHPdoc)
     * @see Sabre\DAV\Collection::getChild()
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
                throw new Sabre\DAV\Exception\NotFound('Object not found');
            }
        }
        
        $httpRequest = new Sabre\HTTP\Request();
        
        // lie about existence of event of request is a PUT request from an ATTENDEE for an already existing event 
        // to prevent ugly (and not helpful) error messages on the client
        if (isset($_SERVER['REQUEST_METHOD']) && $httpRequest->getMethod() == 'PUT' && $httpRequest->getHeader('If-None-Match') === '*') {
            if (
                $object->organizer != Tinebase_Core::getUser()->contact_id && 
                Calendar_Model_Attender::getOwnAttender($object->attendee) !== null
            ) {
                throw new Sabre\DAV\Exception\NotFound('Object not found');
            }
        }
        
        $objectClass = $this->_application->name . '_Frontend_WebDAV_' . $this->_model;
        
        return new $objectClass($this->_container, $object);
    }
    
    /**
     * Returns an array with all the child nodes
     *
     * @return Sabre\DAV\INode[]
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
    
        /**
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
        
        $ctags = Tinebase_Container::getInstance()->getContentSequence($this->_container);
        
        $properties = array(
            '{http://calendarserver.org/ns/}getctag' => $ctags,
            'id'                => $this->_container->getId(),
            'uri'               => $this->_useIdAsName == true ? $this->_container->getId() : $this->_container->name,
            '{DAV:}resource-id' => 'urn:uuid:' . $this->_container->getId(),
            '{DAV:}owner'       => new Sabre\DAVACL\Property\Principal(Sabre\DAVACL\Property\Principal::HREF, 'principals/users/' . Tinebase_Core::getUser()->contact_id),
            '{DAV:}displayname' => $displayName,
            '{http://apple.com/ns/ical/}calendar-color' => (empty($this->_container->color)) ? '#000000' : $this->_container->color,
            
            '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set' => new Sabre\CalDAV\Property\SupportedCalendarComponentSet(array('VEVENT')),
            '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}supported-calendar-data'          => new Sabre\CalDAV\Property\SupportedCalendarData(),
            '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-description'             => 'Calendar ' . $displayName,
            '{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-timezone'                => Tinebase_WebDav_Container_Abstract::getCalendarVTimezone($this->_application)
        );
        
        if (!empty(Tinebase_Core::getUser()->accountEmailAddress)) {
            $properties['{' . Sabre\CalDAV\Plugin::NS_CALDAV . '}calendar-user-address-set'    ] = new Sabre\DAV\Property\HrefList(array('mailto:' . Tinebase_Core::getUser()->accountEmailAddress), false);
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . print_r($properties, true));
        
        $response = array();
    
        foreach($requestedProperties as $prop) {
            if (isset($properties[$prop])) {
                $response[$prop] = $properties[$prop];
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . print_r($response, true));
        
        return $response;
    }
    
    protected function _getController()
    {
        if ($this->_controller === null) {
            $this->_controller = Calendar_Controller_MSEventFacade::getInstance();
        }
        
        return $this->_controller;
    }
    
    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * @param array $filters
     * @return array
     */
    public function calendarQuery(array $filters)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' filters ' . print_r($filters, true));
        
        $filterArray = array(array(
            'field'    => 'container_id',
            'operator' => 'equals',
            'value'    => $this->_container->getId()
        ));
        
        if (isset($filters['comp-filters']) && isset($filters['comp-filters'][0]['time-range'])) {
            $timeRange = $filters['comp-filters'][0]['time-range'];
            if (isset($timeRange['start'])) {
                if (! isset($timeRange['end'])) {
                    // create default time-range end in 4 years from now 
                    $timeRange['end'] = new DateTime('NOW');
                    $timeRange['end']->add(new DateInterval('P4Y'));
                }
                
                $filterArray[] = array(
                    'field'    => 'period', 
                    'operator' => 'within', 
                    'value'    => array(
                        'from'  => new Tinebase_DateTime($timeRange['start']),
                        'until' => new Tinebase_DateTime($timeRange['end'])
                    )
                );
            }
        }
        
        $filterClass = $this->_application->name . '_Model_' . $this->_model . 'Filter';
        $filter = new $filterClass($filterArray);
    
        /**
         * see http://forge.tine20.org/mantisbt/view.php?id=5122
         * we must use action 'sync' and not 'get' as
         * otherwise the calendar also return events the user only can see because of freebusy
         */
        $ids = $this->_getController()->search($filter, null, false, true, 'sync');
    
        return $ids;
    }
    
    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See \Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    public function getSupportedPrivilegeSet() {

        $default = DAVACL\Plugin::getDefaultSupportedPrivilegeSet();

        // We need to inject 'read-free-busy' in the tree, aggregated under
        // {DAV:}read.
        foreach($default['aggregates'] as &$agg) {

            if ($agg['privilege'] !== '{DAV:}read') continue;

            $agg['aggregates'][] = array(
                'privilege' => '{' . CalDAV\Plugin::NS_CALDAV . '}read-free-busy',
            );

        }
        
        return $default;
    }
}
