<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        use more functionality of Tinebase_Import_Abstract (import() and other fns)
 */

/**
 * Calendar_Import_Abstract
 * 
 * @package     Calendar
 * @subpackage  Import
 */
abstract class Calendar_Import_Abstract extends Tinebase_Import_Abstract
{
    /**
     * @var Calendar_Controller_Event
     */
    protected $_cc = null;
    
    /**
     * config options
     * 
     * @var array
     */
    protected $_options = array(
        /**
         * force update of existing events 
         * @var boolean
         */
        'updateExisting'        => TRUE,
        /**
         * update exiting events even if imported sequence number isn't higher
         * @var boolean
         */
        'forceUpdateExisting'   => FALSE,
        /**
         * keep existing attendee
         * @var boolean
         */
        'keepExistingAttendee'  => FALSE,
        /**
         * delete events missing in import file (future only)
         */
        'deleteMissing'         => FALSE,
        /**
         * container the events should be imported in
         * @var string
         */
        'container_id'          => NULL,
        /**
         * import only basic data (i.e. without attendee, alarms, uid, ...)
         * @var string
         */
        'onlyBasicData'         => NULL,
        /**
         * TODO needed? if yes: document!
         */
        'allowDuplicateEvents'  => false,
        /**
         * remote url
         * @var string
         */
        'url'                   => null,
        /**
         * username for remote access
         * @var string
         */
        'username'              => null,
        /**
         * password for remote access
         * @var string
         */
        'password'              => null,
        /**
         * TODO needed? if yes: document!
         * @var string
         */
        'cid'                   => null,
        /**
         * credential cache key instead of username/password
         * @var string
         */
        'ckey'                  => null,
        /**
         * the model
         */
        'model'                 => 'Calendar_Model_Event',
        /**
         * credential cache id
         */
        'cc_id'                 => null,
    );

    protected function _getCalendarController()
    {
        if ($this->_cc === null) {
            $this->_cc = Calendar_Controller_Event::getInstance();
        }

        return $this->_cc;
    }

    /**
     * import the data
     *
     * @param  mixed $_resource
     * @param array $_clientRecordData
     * @return array :
     *  'results'           => Tinebase_Record_RecordSet, // for dryrun only
     *  'totalcount'        => int,
     *  'failcount'         => int,
     *  'duplicatecount'    => int,
     *
     *  @see 0008334: use vcalendar converter for ics import
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        $this->_initImportResult();
        $this->_cc = $this->_getCalendarController();

        // make sure container exists
        $container = Tinebase_Container::getInstance()->getContainerById($this->_options['container_id']);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
            . ' Import into calendar: ' . print_r($this->_options['container_id'], true));

        $events = $this->_getImportEvents($_resource, $container);
        $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(FALSE);

        // search uid's and remove already existing -> only in import cal?
        $existingEventsFilter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_options['container_id']),
            array('field' => 'uid', 'operator' => 'in', 'value' => array_unique($events->uid)),
        ));
        $existingEvents = $this->_cc->search($existingEventsFilter);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
            . ' Found ' . count($existingEvents) . ' existing events');
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' ' . __LINE__ . ' '
            . ' Filter: ' . print_r($existingEventsFilter->toArray(), true));

        // insert one by one in a single transaction
        $existingEvents->addIndices(array('uid'));
        foreach ($events as $event) {
            $existingEvent = $existingEvents->find('uid', $event->uid);
            try {
                if (! $existingEvent) {
                    $event->container_id = $this->_options['container_id'];
                    $event = $this->_cc->create($event, FALSE);
                    $this->_importResult['totalcount'] += 1;
                    $this->_importResult['results']->addRecord($event);
                } else if ($this->_doUpdateExisting($event, $existingEvent)) {
                    $this->_updateEvent($event, $existingEvent);
                } else {
                    $this->_importResult['duplicatecount'] += 1;
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__
                    . ' Import failed for Event ' . $event->summary);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' ' . print_r($event->toArray(), TRUE));
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__
                    . ' ' . $e);
                $this->_importResult['failcount'] += 1;
            }
        }

        $this->_deleteMissing($events);

        Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
            . ' totalcount: ' . $this->_importResult['totalcount']
            . ' / duplicates: ' . $this->_importResult['duplicatecount']
            . ' / fails: ' . $this->_importResult['failcount']);

        return $this->_importResult;
    }

    protected function _updateEvent(Calendar_Model_Event $event, Calendar_Model_Event $existingEvent)
    {
        $event->container_id = $this->_options['container_id'];
        $event->id = $existingEvent->getId();
        $event->last_modified_time = ($existingEvent->last_modified_time instanceof Tinebase_DateTime) ? clone $existingEvent->last_modified_time : NULL;
        if ($this->_options['keepExistingAttendee']) {
            $this->_checkForExistingAttendee($event, $existingEvent);
        }

        $diff = $event->diff($existingEvent, array(
            'seq',
            'creation_time',
            'created_by',
            // allow organizer or transp change?
            'transp',
            'organizer',
            'originator_tz',
            // why are they here?
            'freebusyGrant',
            'readGrant',
            'syncGrant',
            'exportGrant',
            'editGrant',
            'deleteGrant',
            'privateGrant',
        ));
        if (! $diff->isEmpty()) {
            $event = $this->_cc->update($event, FALSE);
            $this->_importResult['updatecount'] += 1;
        }
        $this->_importResult['results']->addRecord($event);
    }

    protected function _checkForExistingAttendee(Calendar_Model_Event $event, Calendar_Model_Event $existingEvent)
    {
        $existingAttendee = $existingEvent->attendee instanceof Tinebase_Record_RecordSet
            ? $existingEvent->attendee
            : new Tinebase_Record_RecordSet('Calendar_Model_Attendee');
        foreach ($event->attendee as $attender) {
            if (Calendar_Model_Attender::getAttendee($existingAttendee, $attender) === null) {
                $existingAttendee->addRecord($attender);
            }
        }
        $event->attendee = $existingAttendee;
    }

    /**
     * get import events
     *
     * @param mixed $_resource
     * @return Tinebase_Record_RecordSet
     */
    abstract protected function _getImportEvents($_resource, $container);

    /**
     * @param $event
     * @param $existingEvent
     * @return bool
     *
     * TODO do we always check the seq here?
     */
    protected function _doUpdateExisting($event, $existingEvent)
    {
        return $this->_options['forceUpdateExisting'] || ($this->_options['updateExisting'] && $event->seq > $existingEvent->seq);
    }

    /**
     * delete missing events
     * 
     * @param $importedEvents
     * @throws Exception
     */
    protected function _deleteMissing($importedEvents)
    {
        if ($this->_options['deleteMissing']) {
            $missingEventsFilter = new Calendar_Model_EventFilter(array(
                array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_options['container_id']),
                array('field' => 'uid', 'operator' => 'notin', 'value' => array_unique($importedEvents->uid)),
                array('field' => 'period', 'operator' => 'within', 'value' => array(
                    'from'  => new Tinebase_DateTime('now'),
                    'until' => new Tinebase_DateTime('+ 100 years'),
                ))
            ));
            $missingEvents = Calendar_Controller_Event::getInstance()->search($missingEventsFilter);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Deleting ' . count($missingEvents) . ' missing events');

            Calendar_Controller_Event::getInstance()->delete($missingEvents->id);
        }
    }

    /**
     * function is not used
     *
     * @param  mixed $_resource
     * @return array|boolean|null
     */
    protected function _getRawData(&$_resource)
    {
        return null;
    }
}
