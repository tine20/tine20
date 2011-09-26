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
    
    /**
     * converts vcalendar to Calendar_Model_Event
     * 
     * @param  Sabre_VObject_Component|stream|string  $_blob   the vcalendar to parse
     * @param  Calendar_Model_Event                   $_model  update existing event
     * @return Calendar_Model_Event
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_model = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($_blob, true));
        
        return;
        
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
                case 'UID':
                    // do nothing
                    break;
                
                case 'ADR':
                    $components = Sabre_VObject_Property::splitCompoundValues($property->value);
                    
                    if (isset($property['TYPE']) && $property['TYPE'] == 'home') {
                        // home address
                        $data['adr_two_street2']     = $components[1];
                        $data['adr_two_street']      = $components[2];
                        $data['adr_two_locality']    = $components[3];
                        $data['adr_two_region']      = $components[4];
                        $data['adr_two_postalcode']  = $components[5];
                        $data['adr_two_countryname'] = $components[6];
                    } else {
                        // work address
                        $data['adr_one_street2']     = $components[1];
                        $data['adr_one_street']      = $components[2];
                        $data['adr_one_locality']    = $components[3];
                        $data['adr_one_region']      = $components[4];
                        $data['adr_one_postalcode']  = $components[5];
                        $data['adr_one_countryname'] = $components[6];
                    }
                    break;
                    
                case 'CATEGORIES':
                    $tags = Sabre_VObject_Property::splitCompoundValues($property->value, ',');
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($tags, true));
                    break;
                    
                case 'EMAIL':
                    switch ($property['TYPE']) {
                        case 'home':
                            $data['email_home'] = $property->value;
                            break;
                        case 'work':
                        default:
                            $data['email'] = $property->value;
                            break;
                    }
                    break;
                    
                case 'FN':
                    $data['n_fn'] = $property->value;
                    break;
                    
                case 'N':
                    $components = Sabre_VObject_Property::splitCompoundValues($property->value);
                    
                    $data['n_family'] = $components[0];
                    $data['n_given']  = $components[1];
                    $data['n_middle'] = isset($components[2]) ? $components[2] : null;
                    $data['n_prefix'] = isset($components[3]) ? $components[3] : null;
                    $data['n_suffix'] = isset($components[4]) ? $components[4] : null;
                    break;
                    
                case 'NOTE':
                    $data['note'] = $property->value;
                    break;
                    
                case 'ORG':
                    $components = Sabre_VObject_Property::splitCompoundValues($property->value);
                    
                    $data['org_name'] = $components[0];
                    $data['org_unit'] = isset($components[1]) ? $components[1] : null;
                    break;
                
                case 'PHOTO':
                    $data['jpegphoto'] = base64_decode($property->value);
                    break;
                    
                case 'TEL':
                    switch ($property['TYPE']) {
                        case 'cell':
                            $data['tel_cell'] = $property->value;
                            break;
                        case 'fax':
                            $data['tel_fax'] = $property->value;
                            break;
                        case 'home':
                            $data['tel_home'] = $property->value;
                            break;
                        case 'work':
                            $data['tel_work'] = $property->value;
                            break;
                    }
                    break;
                    
                case 'URL':
                    switch ($property['TYPE']) {
                        case 'home':
                            $data['url_home'] = $property->value;
                            break;
                        case 'work':
                        default:
                            $data['url'] = $property->value;
                            break;
                    }
                    break;
                    
                case 'TITLE':
                    $data['title'] = $property->value;
                    break;
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                    break;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($data, true));
        
        if (empty($data['n_family'])) {
            $parts = explode(' ', $data['n_fn']);
            $data['n_family'] = $parts[count($parts) - 1];
            $data['n_given'] = (count($parts) > 1) ? $parts[0] : null;
        }
        
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
