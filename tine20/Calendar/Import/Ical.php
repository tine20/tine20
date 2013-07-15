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
        'importContainerId'     => NULL,
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
//        '' => 'DTSTAMP',
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
        if (! $this->_options['importContainerId']) {
            throw new Tinebase_Exception_InvalidArgument('you need to define a importContainerId');
        }
        
        $converter = Calendar_Convert_Event_VCalendar_Factory::factory(Calendar_Convert_Event_VCalendar_Factory::CLIENT_GENERIC);
        
        try {
            $events = $converter->toTine20RecordSet($_resource);
        } catch (Exception $e) {
            $isce = new Calendar_Exception_IcalParser();
            $isce->setParseError($e);
            throw $isce;
        }
        
        $events->container_id = $this->_options['importContainerId'];
        
        $cc = Calendar_Controller_Event::getInstance();
        $sendNotifications = $cc->sendNotifications(FALSE);
        
        // search uid's and remove already existing -> only in import cal?
        $existingEvents = $cc->search(new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_options['importContainerId']),
            array('field' => 'uid', 'operator' => 'in', 'value' => array_unique($events->uid)),
        )), NULL);
        
        // insert one by one in a single transaction
        $existingEvents->addIndices(array('uid'));
        foreach ($events as $event) {
            $existingEvent = $existingEvents->find('uid', $event->uid);
            try {
                if (! $existingEvent) {
                    $cc->create($event, FALSE);
                    $this->_importResult['totalcount'] += 1;
                } else if ($this->_options['forceUpdateExisting'] || ($this->_options['updateExisting'] && $event->seq > $existingEvent->seq)) {
                    $event->id = $existingEvent->getId();
                    $event->last_modified_time = clone $existingEvent->last_modified_time;
                    $cc->update($event, FALSE);
                    $this->_importResult['totalcount'] += 1;
                } else {
                    $this->_importResult['duplicatecount'] += 1;
                }
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . ' ' . __LINE__ . ' Import failed for Event ' . $event->summary);
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' ' . print_r($event->toArray(), TRUE));
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . ' ' . __LINE__ . ' ' . $e);
                $this->_importResult['failcount'] += 1;
            }
        }
        $cc->sendNotifications($sendNotifications);
        
        return $this->_importResult;
    }
}
