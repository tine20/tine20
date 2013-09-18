<?php

use Sabre\VObject;

/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * abstract class to convert a vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
abstract class Addressbook_Convert_Contact_VCard_Abstract implements Tinebase_Convert_Interface
{
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
     * converts vcard to Addressbook_Model_Contact
     * 
     * @param  Sabre\VObject\Component|stream|string  $_blob   the vcard to parse
     * @param  Tinebase_Record_Abstract               $_record  update existing contact
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_record = null)
    {
        if ($_blob instanceof VObject\Component) {
            $vcard = $_blob;
        } else {
            if (is_resource($_blob)) {
                $_blob = stream_get_contents($_blob);
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' vcard data: ' . $_blob);
            $vcard = VObject\Reader::read($_blob);
        }
        
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
                    foreach($property['TYPE'] as $typeProperty) {
                        $typeProperty = strtolower($typeProperty);
                        $typeProperty = str_replace(",pref", "", $typeProperty);
                        $typeProperty = preg_replace('/,.+$/', '', $typeProperty);
                        
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
                    $tags = $property->value;
                    
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($tags, true));
                    break;
                    
                case 'EMAIL':
                    $this->_toTine20ModelParseEmail($data, $property, $vcard);
                    break;
                    
                case 'FN':
                    $data['n_fn'] = $property->value;
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
                    $data['note'] = $property->value;
                    break;
                    
                case 'ORG':
                    $parts = $property->getParts();
                    
                    $data['org_name'] = $parts[0];
                    $data['org_unit'] = isset($parts[1]) ? $parts[1] : null;
                    break;
                    
                case 'PHOTO':
                    $data['jpegphoto'] = base64_decode($property->value);
                    break;
                    
                case 'TEL':
                    $this->_toTine20ModelParseTel($data, $property);
                    break;
                    
                case 'URL':
                    switch (strtoupper($property['TYPE'])) {
                        case 'HOME':
                            $data['url_home'] = $property->value;
                            break;
                            
                        case 'WORK':
                        default:
                            $data['url'] = $property->value;
                            break;
                    }
                    break;
                    
                case 'TITLE':
                    $data['title'] = $property->value;
                    break;

                case 'BDAY':
                    $this->_toTine20ModelParseBday($data, $property);
                    break;
                    
                default:
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                        __METHOD__ . '::' . __LINE__ . ' cardData ' . $property->name);
                    break;
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' data ' . print_r($data, true));
        
        if (empty($data['n_family'])) {
            $parts = explode(' ', $data['n_fn']);
            $data['n_family'] = $parts[count($parts) - 1];
            $data['n_given'] = (count($parts) > 1) ? $parts[0] : null;
        }
        
        $contact->setFromArray($data);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
            __METHOD__ . '::' . __LINE__ . ' data ' . print_r($contact->toArray(), true));
        
        return $contact;
    }

    /**
     * converts Tinebase_Record_Abstract to external format
     *
     * @param  Tinebase_Record_Abstract  $_record
     * @return mixed
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
    }
    
    /**
     * parse telephone
     * 
     * @param array $data
     * @param Sabre\VObject\Property $property
     */
    protected function _toTine20ModelParseTel(&$data, VObject\Property $property)
    {
        $telField = null;
        $types    = array();
    
        if (isset($property['TYPE'])) {
            // get all types
            foreach($property['TYPE'] as $typeProperty) {
                foreach(explode(',', $typeProperty->value) as $typeProperty) {
                    if (! in_array(strtoupper($typeProperty), array('VOICE'))) {
                        $types[] = strtoupper($typeProperty);
                    }
                }
            }
            
            // CELL
            if (in_array('CELL', $types)) {
                if (count($types) == 1 || in_array('WORK', $types)) {
                    $telField = 'tel_cell';
                } else if(in_array('HOME', $types)) {
                    $telField = 'tel_cell_private';
                }
    
                // PAGER
            } elseif (in_array('PAGER', $types)) {
                $telField = 'tel_pager';
    
                // FAX
            } elseif (in_array('FAX', $types)) {
                if (count($types) == 1 || in_array('WORK', $types)) {
                    $telField = 'tel_fax';
                } else if(in_array('HOME', $types)) {
                    $telField = 'tel_fax_home';
                }
    
                // HOME
            } elseif (in_array('HOME', $types)) {
                $telField = 'tel_home';
    
                // WORK
            } elseif (in_array('WORK', $types)) {
                $telField = 'tel_work';
            }
        } else {
            $telField = 'work';
        }
    
        if (!empty($telField)) {
            $data[$telField] = $property->value;
        }
    }
    
    /**
     * parse email
     *
     * @param array                   $_data        reference to tine20 data array
     * @param Sabre\VObject\Property  $_property    mail property
     * @param Sabre\VObject\Component $vcard        complete vcard
     */
    protected function _toTine20ModelParseEmail(&$_data, Sabre\VObject\Property $_property, $vcard)
    {
        $type = null;
        foreach ($_property['TYPE'] as $typeProperty) {
            if (strtolower($typeProperty) == 'home' || strtolower($typeProperty) == 'work') {
                $type = strtolower($typeProperty);
                break;
            } else if (strtolower($typeProperty) == 'internet') {
                $type = strtolower($typeProperty);
            }
        }
        
        switch ($type) {
            case 'internet':
                if (empty($_data['email'])) {
                    // do not replace existing value
                    $_data['email'] = $_property->value;
                }
                break;
            
            case 'home':
                $_data['email_home'] = $_property->value;
                break;
            
            case 'work':
                $_data['email'] = $_property->value;
                break;
        }
    }
    
    /**
     * parse birthday
     * 
     * @param array $data
     * @param Sabre\VObject\Property $property
     */
    protected function _toTine20ModelParseBday(&$_data, VObject\Property $_property)
    {
    }
    
    /**
     * add GEO data to VCard
     * 
     * @param  Tinebase_Record_Abstract  $record
     * @param  Sabre\VObject\Component   $card
     */
    protected function _fromTine20ModelAddGeoData(Tinebase_Record_Abstract $record, Sabre\VObject\Component $card)
    {
        if ($record->adr_one_lat && $record->adr_one_lon) {
            $geo = new Sabre\VObject\Property\Compound('GEO');
            $geo->setParts(array($record->adr_one_lat, $record->adr_one_lon));
            $card->add($geo);
            
        } elseif ($record->adr_two_lat && $record->adr_two_lon) {
            $geo = new Sabre\VObject\Property\Compound('GEO');
            $geo->setParts(array($record->adr_two_lat, $record->adr_two_lon));
            $card->add($geo);
        }
    }
}
