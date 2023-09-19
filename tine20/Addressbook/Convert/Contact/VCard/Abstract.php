<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * photo size
     *
     * @var integer
     */
    protected $_maxPhotoSize = Addressbook_Model_Contact::SMALL_PHOTO_SIZE;
    
    /**
     * the version string
     * 
     * @var string|null $_version
     */
    protected $_version;

    /**
     * should be overwritten by concrete class
     *
     * @var array
     */
    protected $_emptyArray;
    
    /**
     * @param  string|null $_version  the version of the client
     */
    public function __construct($_version = null)
    {
        $this->_version = $_version;

        if (isset($_REQUEST['max_photo_size'])) {
            $this->_maxPhotoSize = (int) $_REQUEST['max_photo_size'];

            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' maxPhotoSize set to ' . $this->_maxPhotoSize);
        }
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
     * @param  \Sabre\VObject\Component|resource|string  $blob       the vcard to parse
     * @param  Tinebase_Record_Interface                $_record    update existing contact
     * @param  array                                   $options    array of options
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($blob, Tinebase_Record_Interface $_record = null, $options = array())
    {
        $vcard = self::getVObject($blob);

        if ($_record instanceof Addressbook_Model_Contact) {
            $contact = $_record;
        } else {
            $contact = new Addressbook_Model_Contact(null, false);
        }
        
        $data = $this->_emptyArray;

        /** @var \Sabre\VObject\Property $property */
        foreach ($vcard->children() as $property) {
            switch ($property->name) {
                case 'VERSION':
                case 'PRODID':
                case 'UID':
                    // do nothing
                    break;
                    
                case 'ADR':
                    $type = null;

                    if (isset($property['TYPE']) && (is_array($property['TYPE']) || $property['TYPE'] instanceof Traversable)) {
                        foreach ($property['TYPE'] as $typeProperty) {
                            $typeProperty = strtolower($typeProperty);

                            if (in_array($typeProperty, array('home', 'work'))) {
                                $type = $typeProperty;
                                break;
                            }
                        }
                    }

                    if ($type) {
                        $parts = $property->getParts();
                        $partsIndex = 1;

                        if ($type == 'home') {
                            // home address
                            $addressFields = array('adr_two_street2', 'adr_two_street', 'adr_two_locality', 'adr_two_region', 'adr_two_postalcode', 'adr_two_countryname');
                        } elseif ($type == 'work') {
                            // work address
                            $addressFields = array('adr_one_street2', 'adr_one_street', 'adr_one_locality', 'adr_one_region', 'adr_one_postalcode', 'adr_one_countryname');
                        }
                        foreach ($addressFields as $field) {
                            $data[$field] = $parts[$partsIndex++];
                        }
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
                    $data['n_given']  = isset($parts[1]) ? $parts[1] : null;
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

                    $this->_toTine20ModelParseOrgExtra($data,$parts);
                    break;
                    
                case 'PHOTO':
                    $jpegphoto = null;
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Photo: ' . $property['ENCODING'] . ":" . $property['TYPE'] );
                    if (    ( $property['ENCODING'] != "b" )
                         && ( $property['ENCODING'] != "B" ) ) {
                        // pass on for now as is if image is not binary encoding, sabre or whoever would in this case
                        // decode any base 64 or hex string into binary blob  
                        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Photo: passing on non binary image as is (' . strlen($property->getValue()) .')' );
                        $jpegphoto = $property->getValue();
                        break;
                    }
                    switch (strtolower($property['TYPE'])) {
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                            if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                    . ' Photo: passing on invalid ' . print_r($property['TYPE'], true)
                                    . ' image as is (' . strlen((string)$property->getValue()) .')' );
                            $jpegphoto = $property->getValue();
                            break;
                        default:
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                    . ' Photo: recoding to jpeg (' . strlen($property->getValue()) . ')');

                            $info = @getimagesizefromstring($property->getValue());
                            if ($info === false) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                        . ' Photo: is of unknown type. passing on as is');
                                $jpegphoto = $property->getValue();
                                break;
                            }
                            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                    . ' Photo: is of type (' . $info['mime'] . ')');
                            if (in_array($info['mime'], Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                                        . ' Photo: can be handled by tine20 directly, not recoded ');
                                $jpegphoto = $property->getValue();
                                break;
                            }
                            $recode = imagecreatefromstring($property->getValue());
                            if ($recode === false) {
                                // pass on for now as is if image is not binary encoding, sabre or whoever would in this case
                                // decode any base 64 or hex string into binary blob  
                                if (Tinebase_Core::isLogLevel(Zend_Log::WARN))
                                    Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                                        . ' Photo: recoding failed, pass on as is  ');
                                $jpegphoto = $property->getValue();
                                break;
                            }
                            ob_start();
                            if (imagejpeg($recode, null, 90) === true) {
                                $jpegphoto = ob_get_contents();
                            }
                            ob_end_clean();
                            imagedestroy($recode);
                            if ($jpegphoto) {
                                $info = getimagesizefromstring($jpegphoto);
                                if (!in_array($info['mime'], Tinebase_ImageHelper::getSupportedImageMimeTypes())) {
                                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ .
                                            ' Photo: failed to recode to jpeg (' . strlen($jpegphoto) . ')');
                                    $jpegphoto = $property->getValue();
                                    break;
                                }
                                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG))
                                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ .
                                        ' Photo: recoded to jpeg (' . strlen($jpegphoto) . ')');
                            }
                    }
                    break;
                    
                case 'TEL':
                    $this->_toTine20ModelParseTel($data, $property);
                    break;
                    
                case 'URL':
                    switch (strtoupper((string)($property['TYPE']))) { /** @phpstan-ignore-line */
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
                    $this->_toTine20ModelParseOther($data,$property);
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
                $data['n_family'] = empty($data['n_fn']) ? 'VCARD (imported)' : $data['n_fn'];
            }
        }

        $contact->setFromArray($data);

        if (isset($jpegphoto)) {
            $contact->setSmallContactImage($jpegphoto, $this->_maxPhotoSize);
        }
        
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
     * converts Tinebase_Record_Interface to external format
     *
     * @param  Tinebase_Record_Interface  $record
     * @return mixed
     */ 
    public function fromTine20Model(Tinebase_Record_Interface $record)
    {
    }
    
    /**
     * parse extra fields provided with org entry in vcard
     * 
     * @param array $data
     * @param array $orgextra
     */
    protected function _toTine20ModelParseOrgExtra(&$data,$orgextra) {
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
            /** @var \Sabre\VObject\Parameter $typeParameter */
            $typeParameter = $property['TYPE'];
            // comvert all TYPE's to lowercase and ignore voice and pref
            $typeParameter->setParts(array_diff(
                array_map('strtolower', $property['TYPE']->getParts()), 
                array('voice', 'pref')
            ));
            
            // CELL
            if ($typeParameter->has('cell')) {
                if (count($typeParameter->getParts()) == 1 || $typeParameter->has('work')) {
                    $telField = 'tel_cell';
                } elseif ($typeParameter->has('home')) {
                    $telField = 'tel_cell_private';
                }
                
            // PAGER
            } elseif ($typeParameter->has('pager')) {
                $telField = 'tel_pager';
                
            // FAX
            } elseif ($typeParameter->has('fax')) {
                if (count($typeParameter->getParts()) == 1 || $typeParameter->has('work')) {
                    $telField = 'tel_fax';
                } elseif ($typeParameter->has('home')) {
                    $telField = 'tel_fax_home';
                }
                
            // HOME
            } elseif ($typeParameter->has('home')) {
                $telField = 'tel_home';
                
            // WORK
            } elseif ($typeParameter->has('work')) {
                $telField = 'tel_work';
            }
        } else {
            $telField = 'tel_work';
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
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::_toTine20ModelParseOther()
     */
    protected function _toTine20ModelParseOther(&$data, \Sabre\VObject\Property $property) {
       if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
           Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
    }
    
    /**
     * parse BIRTHDAY
     * 
     * @param array                    $data
     * @param \Sabre\VObject\Property  $property
     */
    protected function _toTine20ModelParseBday(&$data, \Sabre\VObject\Property $property)
    {
        $value = $property->getValue();
        if (preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', $value, $matches) == 1) {
            $value = "$matches[1]-$matches[2]-$matches[3]";
        }
        $tzone = new DateTimeZone(Tinebase_Core::getUserTimezone());
        $data['bday'] = new Tinebase_DateTime($value, $tzone);
        $data['bday']->setTimezone(new DateTimeZone('UTC'));
    }
    
    /**
     * add GEO data to VCard
     * 
     * @param  Tinebase_Record_Interface  $record
     * @param  \Sabre\VObject\Component  $card
     */
    protected function _fromTine20ModelAddGeoData(Tinebase_Record_Interface $record, \Sabre\VObject\Component $card)
    {
        /** @var Addressbook_Model_Contact $record */
        if ($record->adr_one_lat && $record->adr_one_lon) {
            $card->add('GEO', array($record->adr_one_lat, $record->adr_one_lon));
            
        } elseif ($record->adr_two_lat && $record->adr_two_lon) {
            $card->add('GEO', array($record->adr_two_lat, $record->adr_two_lon));
        }
    }
    
    /**
     * add birthday data to VCard
     * 
     * @param  Tinebase_Record_Interface  $record
     * @param  \Sabre\VObject\Component  $card
     */
    protected function _fromTine20ModelAddBirthday(Tinebase_Record_Interface $record, \Sabre\VObject\Component $card)
    {
        /** @var Addressbook_Model_Contact $record */
        if ($record->bday instanceof Tinebase_DateTime) {
            $date = clone $record->bday;
            $date->setTimezone(Tinebase_Core::getUserTimezone());
            $date = $date->format('Y-m-d');
            $card->add('BDAY', $date);
        }
    }
    
    /**
     * parse categories from Tine20 model to VCard and attach it to VCard $card
     *
     * @param Tinebase_Record_Interface $record
     * @param Sabre\VObject\Component $card
     */
        protected function _fromTine20ModelAddCategories(Tinebase_Record_Interface $record, Sabre\VObject\Component $card)
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
     * @param  Addressbook_Model_Contact $record
     * @param  \Sabre\VObject\Component  $card
     */
    protected function _fromTine20ModelAddPhoto(Addressbook_Model_Contact $record, \Sabre\VObject\Component $card)
    {
        if (! empty($record->jpegphoto)) {Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__);
            try {
                $jpegData = $record->getSmallContactImage($this->_maxPhotoSize);
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
     * @param  Tinebase_Record_Interface  $record
     * @return \Sabre\VObject\Component
     */
    protected function _fromTine20ModelRequiredFields(Tinebase_Record_Interface $record,$fn = null,$org = null)
    {
        /** @var Addressbook_Model_Contact $record */
        $version = Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->version;
        if ( !isset($fn) || $fn === null ) {
            $fn = $record->n_fileas;
        }
        if ( !isset($org) || $org === null ) {
            $org = array($record->org_name, $record->org_unit);
        }
        $card = new \Sabre\VObject\Component\VCard(array(
            'VERSION' => '3.0',
            'FN'      => $fn,
            'N'       => array($record->n_family, $record->n_given, $record->n_middle, $record->n_prefix, $record->n_suffix),
            'PRODID'  => "-//tine20.com//Tine 2.0 Addressbook V$version//EN",
            'UID'     => $record->getId(),
            'ORG'     => $org,
            'TITLE'   => $record->title
        ));
        
        return $card;
    }
}
