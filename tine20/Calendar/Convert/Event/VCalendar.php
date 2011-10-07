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
 * class to convert single event to/from VCalendar
 *
 * @package     Calendar
 * @subpackage  Convert
 */
class Calendar_Convert_Event_VCalendar
{
    const CLIENT_AUTODETECT = 'auto';
    const CLIENT_MACOSX     = 'macosx';
    const CLIENT_SOGO       = 'sogo';
    
    /**
     * @var string
     */
    protected $_client;

    /**
     * @var array
     */
    protected $_supportedFields = array(
        self::CLIENT_AUTODETECT => array(),
        self::CLIENT_MACOSX     => array(),
        self::CLIENT_SOGO       => array()
    );
    
    /**
     * @param  string  $_client
     */
    public function __construct($_client = self::CLIENT_AUTODETECT)
    {
        if (!isset($this->_supportedFields[$_client])) {
            throw new Tinebase_Exception_UnexpectedValue('incalid client provided');
        }
        
        $this->_client = $_client;
    }
    
    protected function _parseVevent(Sabre_VObject_Component $_vevent, &$_data)
    {
        foreach($_vevent->children() as $property) {
            switch($property->name) {
                case 'CREATED':
                case 'LAST-MODIFIED':
                case 'DTSTAMP':
                    // do nothing
                    break;
                    
                case 'DTEND':
                    $_data['dtend'] = new Tinebase_DateTime($property->getDateTime()->format("c"), $property->getDateTime()->getTimezone());
                    break;
                    
                case 'DTSTART':
                    $_data['dtstart'] = new Tinebase_DateTime($property->getDateTime()->format("c"), $property->getDateTime()->getTimezone());
                    break;
                    
                case 'UID':
                case 'SUMMARY':
                    $_data[strtolower($property->name)] = $property->value;
                    break;
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                    break;
            }
        }
    }
    
    /**
     * converts vcalendar to Calendar_Model_Event
     * 
     * @param  Sabre_VObject_Component|stream|string  $_blob   the vcalendar to parse
     * @param  Calendar_Model_Event                   $_model  update existing event
     * @return Calendar_Model_Event
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_model = null)
    {
        if ($_blob instanceof Sabre_VObject_Component) {
            $vcalendar = $_blob;
        } else {
            if (is_resource($_blob)) {
                $_blob = stream_get_contents($_blob);
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($_blob, true));
            $vcalendar = Sabre_VObject_Reader::read($_blob);
        }
        
        if ($_model instanceof Calendar_Model_Event) {
            $event = $_model;
        } else {
            $event = new Calendar_Model_Event(null, false);
        }
        
        $data = array();

        foreach($vcalendar->children() as $property) {
            
            switch($property->name) {
                case 'VERSION':
                case 'PRODID':
                    // do nothing
                    break;
                    
                case 'VEVENT':
                    $this->_parseVevent($property, $data);
                    break;
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                    break;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($data, true));
                
        $event->setFromArray($data);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($event->toArray(), true));
        
        return $event;
    }
    
    /**
     * convert Calendar_Model_Event to Sabre_VObject_Component
     * 
     * @param  Calendar_Model_Event  $_model
     * @return Sabre_VObject_Component
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_model)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_model->toArray(), true));
        
        $eventId = $_model->getId();
        $lastModified = $_model->last_modified_time ? $_model->last_modified_time : $_model->creation_time;
        
        // we always use a event set to return exdates at once
        $eventSet = new Tinebase_Record_RecordSet('Calendar_Model_Event', array($_model));
        
        if ($_model->rrule) {
            foreach($_model->exdate as $exEvent) {
                if (! $exEvent->is_deleted) {
                    $eventSet->addRecord($exEvent);
                    $_model->exdate->removeRecord($exEvent);
                }
            }
            
            // remaining exdates are fallouts
            $_model->exdate = $_model->exdate->getOriginalDtStart();
        }
        
        $exporter = new Calendar_Export_Ical();
        $ics = $exporter->eventToIcal($eventSet);
        
        return $ics;
    }
}
