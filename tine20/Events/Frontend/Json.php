<?php
/**
 * Tine 2.0
 * @package     Events
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Stefanie Stamer <s.stamer@metaways.de>
 * @copyright   Copyright (c) 2007-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 *
 * This class handles all Json requests for the Events application
 *
 * @package     Events
 * @subpackage  Frontend
 */
class Events_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var Events_Controller_Event
     */
    protected $_controller = NULL;
    
    /**
     * the models handled by this frontend
     * @var array
     */
    protected $_configuredModels = array('Event');
    
    /**
     * user fields (created_by, ...) to resolve in _multipleRecordsToJson and _recordToJson
     *
     * @var array
     */
    protected $_resolveUserFields = array(
        'Events_Model_Event' => array('created_by', 'last_modified_by')
    );
    
    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'Events';
        $this->_controller = Events_Controller_Event::getInstance();
    }
    
    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchEvents($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'Events_Model_EventFilter', TRUE);
    }
    
    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getEvent($id)
    {
        return $this->_get($id, $this->_controller);
    }
    
    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @param  bool  $checkBusyConflicts
     * @return array created/updated record
     */
    public function saveEvent($recordData, $checkBusyConflicts = true)
    {
        return $this->_save($recordData, $this->_controller, 'Event', 'id', array($checkBusyConflicts));
    }
    
    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteEvents($ids)
    {
        return $this->_delete($ids, $this->_controller);
    }
    
    /**
     * Returns registry data
     *
     * @return array
     */
    public function getRegistryData()
    {
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer(
            'Events_Model_Event',
            NULL,
            Events_Preference::DEFAULT_EVENTS_CONTAINER
        )->toArray();

        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(
            Tinebase_Core::getUser(),
            $defaultContainerArray['id']
        )->toArray();

        $defaultEventsCalendar = Events_Controller_Event::getDefaultEventsCalendar();
        return array(
            'defaultEventContainer' => $defaultContainerArray,
            'defaultEventsCalendar'  => $defaultEventsCalendar->toArray()
        );
    }
}
