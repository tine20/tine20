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
class Calendar_Frontend_CalDAV_ScheduleOutbox extends Sabre_DAV_Collection implements Sabre_CalDAV_ICalendar, Sabre_CalDAV_Schedule_IOutbox
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
}
