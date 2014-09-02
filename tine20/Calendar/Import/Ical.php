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
 * Calendar_Import_Ical
 * 
 * @package     Calendar
 * @subpackage  Import
 * 
 * @see for german holidays http://www.sunbird-kalender.de/extension/kalender/
 */
class Calendar_Import_Ical extends Tinebase_Import_Abstract
{
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
         * updates exiting events if sequence number is higher
         * @var boolean
         */
        'forceUpdateExisting'   => FALSE,
        /**
         * container the events should be imported in
         * @var string
         */
        'container_id'     => NULL,
        
        /**
         * Model to be used for import
         * @var string
         */
        'model' => 'Calendar_Model_Event'
    );
    
    /**
     * default timezone from VCALENDAR. If not present, users default tz will be taken
     * @var string
     */
    protected $_defaultTimezoneId;
    
    /**
     * maps tine20 propertynames to ical propertynames
     * @var array
     */
    protected $_eventPropertyMap = array(
        'summary'               => 'SUMMARY',
        'description'           => 'DESCRIPTION',
        'class'                 => 'CLASS',
        'transp'                => 'TRANSP',
        'seq'                   => 'SEQUENCE',
        'uid'                   => 'UID',
        'dtstart'               => 'DTSTART',
        'dtend'                 => 'DTEND',
        'rrule'                 => 'RRULE',
        'creation_time'         => 'CREATED',
        'last_modified_time'    => 'LAST-MODIFIED',
    );
    
    /**
     * creates a new importer from an importexport definition
     * 
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Calendar_Import_Ical
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new Calendar_Import_Ical(self::getOptionsArrayFromDefinition($_definition, $_options));
    }
    
    /**
     * import the data
     *
     * @param  stream $_resource 
     * @param array $_clientRecordData
     * @return array : 
     *  'results'           => Tinebase_Record_RecordSet, // for dryrun only
     *  'totalcount'        => int,
     *  'failcount'         => int,
     *  'duplicatecount'    => int,
     *  
     *  @throws Calendar_Exception_IcalParser
     *  
     *  @see 0008334: use vcalendar converter for ics import
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        $this->_initImportResult();
        
        if (! $this->_options['container_id']) {
            throw new Tinebase_Exception_InvalidArgument('you need to define a container_id');
        }
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        try {
            $events = $converter->toTine20RecordSet($_resource);
        } catch (Exception $e) {
            Tinebase_Exception::log($e);
            $isce = new Calendar_Exception_IcalParser();
            $isce->setParseError($e);
            throw $isce;
        }
        
        // find container or create a new one on the fly
        $containerId = $this->_options['container_id'];
        
        try {
            $container = Tinebase_Container::getInstance()->getContainerById($containerId);
        } catch (Tinebase_Exception_InvalidArgument $e) {
            $container = new Tinebase_Model_Container(array(
                'name'              => $containerId,
                'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
                'backend'           => Tinebase_User::SQL,
                'color'             => '#ffffff',
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'owner_id'          => Tinebase_Core::getUser()->getId(),
                'model'             => 'Calendar_Model_Event',
            ));
            $container = Tinebase_Container::getInstance()->addContainer($container);
        }
        
        $events->container_id = $container->getId();
        
        $cc = Calendar_Controller_MSEventFacade::getInstance();
        $sendNotifications = Calendar_Controller_Event::getInstance()->sendNotifications(FALSE);
        
        // search uid's and remove already existing -> only in import cal?
        $existingEventsFilter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'in', 'value' => array($this->_options['container_id'])),
            array('field' => 'uid', 'operator' => 'in', 'value' => array_unique($events->uid)),
        ));
        $existingEvents = $cc->search($existingEventsFilter);
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
                    $event = $cc->create($event, FALSE);
                    $this->_importResult['totalcount'] += 1;
                    $this->_importResult['results']->addRecord($event);
                } else if ($this->_options['forceUpdateExisting'] || ($this->_options['updateExisting'] && $event->seq > $existingEvent->seq)) {
                    $event->id = $existingEvent->getId();
                    $event->last_modified_time = ($existingEvent->last_modified_time instanceof Tinebase_DateTime) ? clone $existingEvent->last_modified_time : NULL;
                    $cc->update($event, FALSE);
                    $this->_importResult['results']->addRecord($event);
                    $this->_importResult['totalcount'] += 1;
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
        Calendar_Controller_Event::getInstance()->sendNotifications($sendNotifications);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' '
                . ' totalcount: ' . $this->_importResult['totalcount']
                . ' / duplicates: ' . $this->_importResult['duplicatecount']
                . ' / fails: ' . $this->_importResult['failcount']);
        
        return $this->_importResult;
    }
}
