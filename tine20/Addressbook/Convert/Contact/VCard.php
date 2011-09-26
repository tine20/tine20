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
 * class to convert a contact to vcard and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard
{
    const CLIENT_AUTODETECT = 'auto';
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
        self::CLIENT_SOGO    => array()
    );
    
    /**
     * @param unknown_type $_client
     */
    public function __construct($_client = self::CLIENT_AUTODETECT)
    {
        if (!isset($this->_supportedFields[$_client])) {
            throw new Tinebase_Exception_UnexpectedValue('incalid client provided');
        }
        
        $this->_client = $_client;
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
        
        $data = array();
        
        foreach($vcard->children() as $property) {
            
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
    public function fromTine20Model(Addressbook_Model_Contact $_model)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_model->toArray(), true));
        
        $card = new Sabre_VObject_Component('VCARD');
        
        // required vcard fields
        $card->add(new Sabre_VObject_Property('VERSION', '3.0'));
        $card->add(new Sabre_VObject_Property('FN', $_model->n_fileas));
        $card->add(new Sabre_VObject_Property('N', $_model->n_family . ';' . $_model->n_given));
        
        $card->add(new Sabre_VObject_Property('PRODID', '-//tine20.org//Tine 2.0//EN'));
        $card->add(new Sabre_VObject_Property('UID', $_model->getId()));

        $card->add(new Sabre_VObject_Property('ORG', Sabre_VObject_Property::concatCompoundValues(array($_model->org_name, $_model->org_unit))));
        $card->add(new Sabre_VObject_Property('TITLE', $_model->title));
        
        $card->add(new Sabre_VObject_Property('TEL;TYPE=work', $_model->tel_work));
        $card->add(new Sabre_VObject_Property('TEL;TYPE=cell', $_model->tel_cell));
        $card->add(new Sabre_VObject_Property('TEL;TYPE=fax',  $_model->tel_fax));
        $card->add(new Sabre_VObject_Property('TEL;TYPE=home', $_model->tel_home));
        
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
