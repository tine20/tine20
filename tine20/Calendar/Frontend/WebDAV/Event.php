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
 * class to handle a single event
 *
 * This class handles the creation, update and deletion of vevents
 *
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_WebDAV_Event extends Sabre_DAV_File implements Sabre_CalDAV_ICalendarObject, Sabre_DAVACL_IACL
{
    /**
     * @var Calendar_Model_Event
     */
    protected $_event;
    
    /**
     * holds the vevent returned to the client
     * 
     * @var string
     */
    protected $_vevent;
    
    /**
     * @var Calendar_Convert_Event_VCalendar
     */
    protected $_converter;
    
    /**
     * Constructor 
     * 
     * @param  string|Calendar_Model_Event  $_event  the id of a event or the event itself 
     */
    public function __construct($_event = null) 
    {
        $this->_event = $_event;
        $this->_converter = new Calendar_Convert_Event_VCalendar();
    }
    
    /**
     * this function creates a Calendar_Model_Event and stores it in the database
     * 
     * @todo the header handling does not belong here. It should be moved to the DAV_Server class when supported
     * 
     * @param  Tinebase_Model_Container  $container
     * @param  stream|string             $vobjectData
     */
    public static function create(Tinebase_Model_Container $container, $vobjectData)
    {
        $converter = new Calendar_Convert_Event_VCalendar();
        
        $event = $converter->toTine20Model($vobjectData);
        $event->container_id = $container->getId();
        
        $event = Calendar_Controller_MSEventFacade::getInstance()->create($event);
        
        $vevent = new self($event);
        
        return $vevent;
    }
    
    /**
     * Deletes the card
     *
     * @return void
     */
    public function delete() 
    {
        Calendar_Controller_MSEventFacade::getInstance()->delete($this->_event);
    }
    
    /**
     * Returns the VCard-formatted object 
     * 
     * @return stream
     */
    public function get() 
    {
        $s = fopen('php://temp','r+');
        fwrite($s, $this->_getVEvent());
        rewind($s);
        
        return $s;
    }
    
    /**
     * Returns the uri for this object 
     * 
     * @return string 
     */
    public function getName() 
    {
        return $this->getRecord()->getId() . '.ics';
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner 
     * 
     * @todo add real owner
     * @return string|null
     */
    public function getOwner() 
    {
        return null;
        return $this->addressBookInfo['principaluri'];
    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo add real group
     * @return string|null 
     */
    public function getGroup() 
    {
        return null;
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
     * @todo add the real logic
     * @return array 
     */
    public function getACL() 
    {
        return null;
        
        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
        );

    }
    
    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType() {
    
        return 'text/calendar';
    
    }
    
    /**
     * Returns an ETag for this object
     *
     * @return string
     */
    public function getETag() 
    {
        return '"' . md5($this->getRecord()->getId() . $this->getLastModified()) . '"';
    }
    
    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return time
     */
    public function getLastModified() 
    {
        return ($this->getRecord()->last_modified_time instanceof Tinebase_DateTime) ? $this->getRecord()->last_modified_time->toString() : $this->getRecord()->creation_time->toString();
    }
    
    /**
     * Returns the size of the vcard in bytes
     *
     * @return int
     */
    public function getSize() 
    {
        return strlen($this->_getVEvent());
    }
    
    /**
     * Updates the VCard-formatted object
     *
     * @param string $cardData
     * @return void
     */
    public function put($cardData) 
    {
        $event = $this->_converter->toTine20Model($cardData, $this->getRecord());
        
        $this->_event = Calendar_Controller_MSEventFacade::getInstance()->update($event);
        
        // avoid sending headers during unit tests
        if (php_sapi_name() != 'cli') {
            // @todo this belong to DAV_Server, but it currently not supported
            header('ETag: ' . $this->getETag());
        }
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
     * return Calendar_Model_Event and convert contact id to model if needed
     * 
     * @return Calendar_Model_Event
     */
    public function getRecord()
    {
        if (! $this->_event instanceof Calendar_Model_Event) {
            $this->_event = str_replace('.ics', '', $this->_event);
            $this->_event = Calendar_Controller_MSEventFacade::getInstance()->get($this->_event);
        }
        
        return $this->_event;
    }
    
    /**
     * return vcard and convert Calendar_Model_Event to vcard if needed
     * 
     * @return string
     */
    protected function _getVEvent()
    {
        if ($this->_vevent == null) {
            $this->_vevent = $this->_converter->fromTine20Model($this->getRecord());
        }
        
        return $this->_vevent;
    }
}
