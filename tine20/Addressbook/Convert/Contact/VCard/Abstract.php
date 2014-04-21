<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * abstract class to convert a single contact (repeating with exceptions) to/from VCARD
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
abstract class Addressbook_Convert_Contact_VCard_Abstract implements Tinebase_Convert_Interface
{
    /**
     * use servers modlogProperties instead of given DTSTAMP & SEQUENCE
     * use this if the concurrency checks are done differntly like in CardDAV 
     * where the etag is checked
     */
    const OPTION_USE_SERVER_MODLOG = 'useServerModlog';
    
    /**
     * the version string
     * 
     * @var string
     */
    protected $_version;
    
    /**
     * @param  string  $_version  the version of the client
     */
    public function __construct($_version = null)
    {
        $this->_version = $_version;
    }
    
    /**
     * returns VObject of input data
     * 
     * @param   mixed  $blob
     * @return  \Sabre\VObject\Component\VCard
     */
    public static function getVObject($blob)
    {
        if ($blob instanceof \Sabre\VObject\Component\VCard) {
            return $blob;
        }
        
        if (is_resource($blob)) {
            $blob = stream_get_contents($blob);
        }
        
        return \Sabre\VObject\Reader::read($blob);
    }
 
    /**
     * converts vcard to Addressbook_Model_Contact
     * 
     * @param  \Sabre\VObject\Component|stream|string  $blob       the vcard to parse
     * @param  Tinebase_Record_Abstract                $_record    update existing contact
     * @param  array                                   $options    array of options
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($blob, Tinebase_Record_Abstract $_record = null, $options = array())
    {
        $vcard = self::getVObject($blob);
        
        if ($_record instanceof Addressbook_Model_Contact) {
            $contact = $_record;
        } else {
            $contact = new Addressbook_Model_Contact(null, false);
        }
        
        $data = $this->_emptyArray;
        
        foreach ($vcard->children() as $property) {
            switch ($property->name) {
                case 'VERSION':
                case 'PRODID':
                case 'UID':
                    // do nothing
                    break;
                    
                case 'ADR':
                    $type = null;
                    
                    foreach ($property['TYPE'] as $typeProperty) {
                        $typeProperty = strtolower($typeProperty);
                        
                        if (in_array($typeProperty, array('home','work'))) {
                            $type = $typeProperty;
                            break;
                        }
                    }
                    
                    $parts = $property->getParts();
                    
                    if ($type == 'home') {
                        // home address
                        $data['adr_two_street2']     = $parts[1];
                        $data['adr_two_street']      = $parts[2];
                        $data['adr_two_locality']    = $parts[3];
                        $data['adr_two_region']      = $parts[4];
                        $data['adr_two_postalcode']  = $parts[5];
                        $data['adr_two_countryname'] = $parts[6];
                    } elseif ($type == 'work') {
                        // work address
                        $data['adr_one_street2']     = $parts[1];
                        $data['adr_one_street']      = $parts[2];
                        $data['adr_one_locality']    = $parts[3];
                        $data['adr_one_region']      = $parts[4];
                        $data['adr_one_postalcode']  = $parts[5];
                        $data['adr_one_countryname'] = $parts[6];
                    }
                    break;
                    
                case 'CATEGORIES':
                    $tags = Tinebase_Model_Tag::resolveTagNameToTag($property->getParts(), 'Addressbook');
                    if (! isset($data['tags'])) {
                        $data['tags'] = $tags;
                    } else {
                        $data['tags']->merge($tags);
                    }
                    break;
                    
                case 'EMAIL':
                    $this->_toTine20ModelParseEmail($data, $property, $vcard);
                    break;
                    
                case 'FN':
                    $data['n_fn'] = $property->getValue();
                    break;
                    
                case 'N':
                    $parts = $property->getParts();
                    
                    $data['n_family'] = $parts[0];
                    $data['n_given']  = $parts[1];
                    $data['n_middle'] = isset($parts[2]) ? $parts[2] : null;
                    $data['n_prefix'] = isset($parts[3]) ? $parts[3] : null;
                    $data['n_suffix'] = isset($parts[4]) ? $parts[4] : null;
                    break;
                    
                case 'NOTE':
                    $data['note'] = $property->getValue();
                    break;
                    
                case 'ORG':
                    $parts = $property->getParts();
                    
                    $data['org_name'] = $parts[0];
                    $data['org_unit'] = isset($parts[1]) ? $parts[1] : null;
                    break;
                    
                case 'PHOTO':
                    $data['jpegphoto'] = $property->getValue();
                    break;
                    
                case 'TEL':
                    $this->_toTine20ModelParseTel($data, $property);
                    break;
                    
                case 'URL':
                    switch (strtoupper($property['TYPE'])) {
                        case 'HOME':
                            $data['url_home'] = $property->getValue();
                            break;
                            
                        case 'WORK':
                        default:
                            $data['url'] = $property->getValue();
                            break;
                    }
                    break;
                    
                case 'TITLE':
                    $data['title'] = $property->getValue();
                    break;

                case 'BDAY':
                    $this->_toTine20ModelParseBday($data, $property);
                    break;
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                    break;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($data, true));
        
        // Some email clients will only set a contact with FN (formatted name) without surname
        if (empty($data['n_family']) && empty($data['org_name']) && (!empty($data['n_fn']))) {
            if (strpos($data['n_fn'], ",") > 0) {
                list($lastname, $firstname) = explode(",", $data['n_fn'], 2);
                $data['n_family'] = trim($lastname);
                $data['n_given']  = trim($firstname);
                
            } elseif (strpos($data['n_fn'], " ") > 0) {
                list($firstname, $lastname) = explode(" ", $data['n_fn'], 2);
                $data['n_family'] = trim($lastname);
                $data['n_given']  = trim($firstname);
                
            } else {
                $data['n_family'] = $data['n_fn'];
            }
        }
        
        $contact->setFromArray($data);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($contact->toArray(), true));
        
        if (isset($options[self::OPTION_USE_SERVER_MODLOG]) && $options[self::OPTION_USE_SERVER_MODLOG] === true) {
            $contact->creation_time = $_record->creation_time;
            $contact->last_modified_time = $_record->last_modified_time;
            $contact->seq = $_record->seq;
        }
        
        return $contact;
    }

    /**
     * converts Tinebase_Record_Abstract to external format
     *
     * @param  Tinebase_Record_Abstract  $record
     * @return mixed
     */ 
    public function fromTine20Model(Tinebase_Record_Abstract $record)
    {
    }
    
    /**
     * parse telephone
     * 
     * @param array $data
     * @param \Sabre\VObject\Property $property
     */
    protected function _toTine20ModelParseTel(&$data, \Sabre\VObject\Property $property)
    {
        $telField = null;
        
        if (isset($property['TYPE'])) {
            // comvert all TYPE's to lowercase and ignore voice and pref
            $property['TYPE']->setParts(array_diff(
                array_map('strtolower', $property['TYPE']->getParts()), 
                array('voice', 'pref')
            ));
            
            // CELL
            if ($property['TYPE']->has('cell')) {
                if (count($property['TYPE']->getParts()) == 1 || $property['TYPE']->has('work')) {
                    $telField = 'tel_cell';
                } elseif ($property['TYPE']->has('home')) {
                    $telField = 'tel_cell_private';
                }
                
            // PAGER
            } elseif ($property['TYPE']->has('pager')) {
                $telField = 'tel_pager';
                
            // FAX
            } elseif ($property['TYPE']->has('fax')) {
                if (count($property['TYPE']->getParts()) == 1 || $property['TYPE']->has('work')) {
                    $telField = 'tel_fax';
                } elseif ($property['TYPE']->has('home')) {
                    $telField = 'tel_fax_home';
                }
                
            // HOME
            } elseif ($property['TYPE']->has('home')) {
                $telField = 'tel_home';
                
            // WORK
            } elseif ($property['TYPE']->has('work')) {
                $telField = 'tel_work';
            }
        } else {
            $telField = 'work';
        }
        
        if (!empty($telField)) {
            $data[$telField] = $property->getValue();
        }
    }
    
    /**
     * parse email address field
     *
     * @param  array                           $data      reference to tine20 data array
     * @param  \Sabre\VObject\Property         $property  mail property
     * @param  \Sabre\VObject\Component\VCard  $vcard     vcard object
     */
    protected function _toTine20ModelParseEmail(&$data, \Sabre\VObject\Property $property, \Sabre\VObject\Component\VCard $vcard)
    {
        $type = null;
        
        foreach ($property['TYPE'] as $typeProperty) {
            if (strtolower($typeProperty) == 'home' || strtolower($typeProperty) == 'work') {
                $type = strtolower($typeProperty);
                break;
            } elseif (strtolower($typeProperty) == 'internet') {
                $type = strtolower($typeProperty);
            }
        }
        
        switch ($type) {
            case 'internet':
                if (empty($data['email'])) {
                    // do not replace existing value
                    $data['email'] = $property->getValue();
                }
                break;
            
            case 'home':
                $data['email_home'] = $property->getValue();
                break;
            
            case 'work':
                $data['email'] = $property->getValue();
                break;
        }
    }
    
    /**
     * parse BIRTHDAY
     * 
     * @param array                    $data
     * @param \Sabre\VObject\Property  $property
     */
    protected function _toTine20ModelParseBday(&$data, \Sabre\VObject\Property $property)
    {
        $tzone = new DateTimeZone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $data['bday'] = new Tinebase_DateTime($property->getValue(), $tzone);
        $data['bday']->setTimezone(new DateTimeZone('UTC'));
    }
    
    /**
     * add GEO data to VCard
     * 
     * @param  Tinebase_Record_Abstract  $record
     * @param  \Sabre\VObject\Component  $card
     */
    protected function _fromTine20ModelAddGeoData(Tinebase_Record_Abstract $record, \Sabre\VObject\Component $card)
    {
        if ($record->adr_one_lat && $record->adr_one_lon) {
            $card->add('GEO', array($record->adr_one_lat, $record->adr_one_lon));
            
        } elseif ($record->adr_two_lat && $record->adr_two_lon) {
            $card->add('GEO', array($record->adr_two_lat, $record->adr_two_lon));
        }
    }
    
    /**
     * add birthday data to VCard
     * 
     * @param  Tinebase_Record_Abstract  $record
     * @param  \Sabre\VObject\Component  $card
     */
    protected function _fromTine20ModelAddBirthday(Tinebase_Record_Abstract $record, \Sabre\VObject\Component $card)
    {
        if ($record->bday instanceof Tinebase_DateTime) {
            $date = clone $record->bday;
            $date->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
            $date = $date->format('Y-m-d');
            $card->add('BDAY', $date);
        }
    }
    
    /**
     * parse categories from Tine20 model to VCard and attach it to VCard $card
     *
     * @param Tinebase_Record_Abstract &$_record
     * @param Sabre\VObject\Component $card
     */
        protected function _fromTine20ModelAddCategories(Tinebase_Record_Abstract &$record, Sabre\VObject\Component $card)
        {
            if (!isset($record->tags)) {
                // If the record has not been populated yet with tags, let's try to get them all and update the record
                $record->tags = Tinebase_Tags::getInstance()->getTagsOfRecord($record);
            }
            if (isset($record->tags) && count($record->tags) > 0) {
                // we have some tags attached, so let's convert them and attach to the VCARD
                $card->add('CATEGORIES', (array) $record->tags->name);
            }
        }
        
    /**
     * add photo data to VCard
     * 
     * @param  Tinebase_Record_Abstract  $record
     * @param  \Sabre\VObject\Component  $card
     */
    protected function _fromTine20ModelAddPhoto(Tinebase_Record_Abstract $record, \Sabre\VObject\Component $card)
    {
        if (!empty($record->jpegphoto)) {Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__);
            try {
                $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $record->getId());
                 $jpegData = $image->getBlob('image/jpeg');      
                $card->add('PHOTO',  $jpegData, array('TYPE' => 'JPEG', 'ENCODING' => 'b'));
            } catch (Exception $e) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) 
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Image for contact {$record->getId()} not found or invalid: {$e->getMessage()}");
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $e->getTraceAsString());
            }
        }
    }
    
    /**
     * initialize vcard object
     * 
     * @param  Tinebase_Record_Abstract  $record
     * @return \Sabre\VObject\Component
     */
    protected function _fromTine20ModelRequiredFields(Tinebase_Record_Abstract $record)
    {
        $version = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->version;
        
        $card = new \Sabre\VObject\Component\VCard(array(
            'VERSION' => '3.0',
            'FN'      => $record->n_fileas,
            'N'       => array($record->n_family, $record->n_given, $record->n_middle, $record->n_prefix, $record->n_suffix),
            'PRODID'  => "-//tine20.com//Tine 2.0 Addressbook V$version//EN",
            'UID'     => $record->getId(),
            'ORG'     => array($record->org_name, $record->org_unit),
            'TITLE'   => $record->title
        ));
        
        return $card;
    }
}
