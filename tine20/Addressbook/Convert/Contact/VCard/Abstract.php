<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param  Sabre_VObject_Component|stream|string  $_blob   the vcard to parse
     * @param  Tinebase_Record_Abstract               $_model  update existing contact
     * @return Addressbook_Model_Contact
     */
    public function toTine20Model($_blob, Tinebase_Record_Abstract $_model = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($_blob, true));
        
        if ($_blob instanceof Sabre_VObject_Component) {
            $vcard = $_blob;
        } else {
            if (is_resource($_blob)) {
                $_blob = stream_get_contents($_blob);
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($_blob, true));
            $vcard = Sabre_VObject_Reader::read($_blob);
        }
        
        if ($_model instanceof Addressbook_Model_Contact) {
            $contact = $_model;
        } else {
            $contact = new Addressbook_Model_Contact(null, false);
        }
        
        $data = $this->_emptyArray;
        
        foreach($vcard->children() as $property) {
            
            switch($property->name) {
                case 'VERSION':
                case 'PRODID':
                case 'UID':
                    // do nothing
                    break;
                
                case 'ADR':
                    $components = Sabre_VObject_Property::splitCompoundValues($property->value);

                    $type = null;
                    foreach($property['TYPE'] as $typeProperty) {
                        if(strtolower($typeProperty) == 'home' || strtolower($typeProperty) == 'work') {
                            $type = strtolower($typeProperty);
                            break;
                        }
                    }
                    
                    if ($type == 'home') {
                        // home address
                        $data['adr_two_street2']     = $components[1];
                        $data['adr_two_street']      = $components[2];
                        $data['adr_two_locality']    = $components[3];
                        $data['adr_two_region']      = $components[4];
                        $data['adr_two_postalcode']  = $components[5];
                        $data['adr_two_countryname'] = $components[6];
                    } elseif ($type == 'work') {
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
                    $type = null;
                    foreach($property['TYPE'] as $typeProperty) {
                        if(strtolower($typeProperty) == 'home' || strtolower($typeProperty) == 'work') {
                            $type = strtolower($typeProperty);
                            break;
                        }
                    }
                    
                    switch ($type) {
                        case 'home':
                            $data['email_home'] = $property->value;
                            break;
                            
                        case 'work':
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
                    $telField = null;
                    $types    = array();
                    
                    if (isset($property['TYPE'])) {
                        // get all types
                        foreach($property['TYPE'] as $typeProperty) {
                            $types[] = strtoupper($typeProperty->value);
                        }

                        // CELL
                        if (in_array('CELL', $types)) {
                            if (count($types) == 1 || in_array('WORK', $types)) {
                                $telField = 'tel_cell';
                            } elseif(in_array('HOME', $types)) {
                                $telField = 'tel_cell_home';
                            }
                            
                        // PAGER
                        } elseif (in_array('PAGER', $types)) {
                            $telField = 'tel_pager';
                            
                        // FAX
                        } elseif (in_array('FAX', $types)) {
                            if (count($property['TYPE']) == 1 || in_array('WORK', $types)) {
                                $telField = 'tel_fax';
                            } elseif(in_array('HOME', $types)) {
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
        
        $contact->setFromArray($data);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' data ' . print_r($contact->toArray(), true));
        
        return $contact;
    }
    
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @param  Addressbook_Model_Contact  $_model
     * @return string
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_model)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_model->toArray(), true));
        
        $card = new Sabre_VObject_Component('VCARD');
        
        // required vcard fields
        $card->add(new Sabre_VObject_Property('VERSION', '3.0'));
        $card->add(new Sabre_VObject_Property('FN', $_model->n_fileas));
        $card->add(new Sabre_VObject_Property('N', $_model->n_family . ';' . $_model->n_given));
        
        $card->add(new Sabre_VObject_Property('PRODID', '-//tine20.org//Tine 2.0//EN'));
        $card->add(new Sabre_VObject_Property('UID', $_model->getId()));

        // optional fields
        $card->add(new Sabre_VObject_Property('ORG', Sabre_VObject_Property::concatCompoundValues(array($_model->org_name, $_model->org_unit))));
        $card->add(new Sabre_VObject_Property('TITLE', $_model->title));
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_work);
        $tel->add('TYPE', 'WORK');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_home);
        $tel->add('TYPE', 'HOME');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_cell);
        $tel->add('TYPE', 'CELL');
        $tel->add('TYPE', 'WORK');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_cell_private);
        $tel->add('TYPE', 'CELL');
        $tel->add('TYPE', 'HOME');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_fax);
        $tel->add('TYPE', 'FAX');
        $tel->add('TYPE', 'WORK');
        $card->add($tel);
        
        $tel = new Sabre_VObject_Property('TEL', $_model->tel_fax_home);
        $tel->add('TYPE', 'FAX');
        $tel->add('TYPE', 'HOME');
        $card->add($tel);
        
        $card->add(new Sabre_VObject_Property('ADR;TYPE=work', Sabre_VObject_Property::concatCompoundValues(array(null, $_model->adr_one_street2, $_model->adr_one_street, $_model->adr_one_locality, $_model->adr_one_region, $_model->adr_one_postalcode, $_model->adr_one_countryname))));
        $card->add(new Sabre_VObject_Property('ADR;TYPE=home', Sabre_VObject_Property::concatCompoundValues(array(null, $_model->adr_two_street2, $_model->adr_two_street, $_model->adr_two_locality, $_model->adr_two_region, $_model->adr_two_postalcode, $_model->adr_two_countryname))));
        
        $card->add(new Sabre_VObject_Property('EMAIL;TYPE=work', $_model->email));
        $card->add(new Sabre_VObject_Property('EMAIL;TYPE=home', $_model->email_home));
        
        $card->add(new Sabre_VObject_Property('URL;TYPE=work', $_model->url));
        $card->add(new Sabre_VObject_Property('URL;TYPE=home', $_model->url_home));
        
        $card->add(new Sabre_VObject_Property('NOTE', $_model->note));
        
        if(! empty($_model->jpegphoto)) {
            try {
                $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $_model->getId());
                $jpegData = $image->getBlob('image/jpeg');
                $card->add(new Sabre_VObject_Property('PHOTO;ENCODING=b;TYPE=JPEG', base64_encode($jpegData)));
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Image for contact {$_model->getId()} not found or invalid");
            }
        
        
        }        
        if(isset($_model->tags) && count($_model->tags) > 0) {
            $card->add(new Sabre_VObject_Property('CATEGORIES', Sabre_VObject_Property::concatCompoundValues((array) $_model->tags->name, ',')));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card->serialize();
    }
    
}
