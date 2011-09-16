<?php
/**
 * class to handle a single vcard
 *
 * @package     Addressbook
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to handle a single vcard
 *
 * This class handles the creation, update and deletion of vcards
 *
 * @package     Addressbook
 * @subpackage  Frontend
 */
class Addressbook_Frontend_CardDAV_Card extends Sabre_DAV_File implements Sabre_CardDAV_ICard, Sabre_DAVACL_IACL
{
    /**
     * @var Addressbook_Model_Contact
     */
    protected $_contact;
    
    /**
     * holds the vcard returned to the client
     * 
     * @var string
     */
    protected $_vcard;
    
    /**
     * Constructor 
     * 
     * @param  string|Addressbook_Model_Contact  $_contact  the id of a contact or the contact itself 
     */
    public function __construct($_contact = null) 
    {
        $this->_contact = $_contact;
    }
    
    /**
     * this function creates a Addressbook_Model_Contact and stores it in the database
     * 
     * @todo the header handling does not belong here. It should be moved to the DAV_Server class when supported
     * 
     * @param  Tinebase_Model_Container  $container
     * @param  stream|string           $vcardData
     */
    public static function create(Tinebase_Model_Container $container, $vcardData)
    {
        $contact = self::convertToAddressbookModelContact($vcardData);
        $contact->container_id = $container->getId();
        
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        
        $card = new self($contact);
        
        // this belongs to DAV_Server, but is currently not supported
        header('ETag: ' . $card->getETag());
        header('Location: /' . $card->getName());
        
        return $card;
    }
    
    /**
     * Deletes the card
     *
     * @return void
     */
    public function delete() 
    {
        Addressbook_Controller_Contact::getInstance()->delete($this->_contact);
    }
    
    /**
     * Returns the VCard-formatted object 
     * 
     * @return stream
     */
    public function get() 
    {
        $s = fopen('php://temp','r+');
        fwrite($s, $this->_getVCard());
        rewind($s);
        
        return $s;
    }
    
    /**
     * Returns the uri for this object 
     * 
     * @return string 
     */
    public function getName() 
    {
        return $this->_getContact()->getId() . '.vcf';
    }
    
    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner 
     * 
     * @todo add real owner
     * @return string|null
     */
    public function getOwner() 
    {
        return null;
        return $this->addressBookInfo['principaluri'];
    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     * 
     * @todo add real group
     * @return string|null 
     */
    public function getGroup() 
    {
        return null;
    }
    
    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are 
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to 
     *      be updated. 
     * 
     * @todo add the real logic
     * @return array 
     */
    public function getACL() {

        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
            array(
                'privilege' => '{DAV:}write',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ),
        );

    }
    
    /**
     * Returns the mime content-type
     *
     * @return string
     */
    public function getContentType() {
    
        return 'text/x-vcard';
    
    }
    
    /**
     * Returns an ETag for this object
     *
     * @return string
     */
    public function getETag() 
    {
        return '"' . md5($this->_getContact()->getId() . $this->getLastModified()) . '"';
    }
    
    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return time
     */
    public function getLastModified() 
    {
        return ($this->_getContact()->last_modified_time instanceof Tinebase_DateTime) ? $this->_getContact()->last_modified_time->toString() : $this->_getContact()->creation_time->toString();
    }
    
    /**
     * Returns the size of the vcard in bytes
     *
     * @return int
     */
    public function getSize() 
    {
        return strlen($this->_getVCard());
    }
    
    /**
     * Updates the VCard-formatted object
     *
     * @param string $cardData
     * @return void
     */
    public function put($cardData) 
    {
        $contact = self::convertToAddressbookModelContact($cardData, $this->_getContact());
        
        $this->_contact = Addressbook_Controller_Contact::getInstance()->update($contact);
        
        // @todo this belong to DAV_Server, but it currently not supported
        header('ETag: ' . $this->getETag());
    }
    
    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's. 
     * 
     * @param array $acl 
     * @return void
     */
    public function setACL(array $acl) 
    {
        throw new Sabre_DAV_Exception_MethodNotAllowed('Changing ACL is not yet supported');
    }
    
    /**
     * converts vcard to Addressbook_Model_Contact
     * 
     * @param  Sabre_VObject_Component|stream|string  $_vcard    the vcard to parse
     * @param  Addressbook_Model_Contact              $_contact  supply $_contact to update
     * @return Addressbook_Model_Contact
     */
    public static function convertToAddressbookModelContact($_vcard, Addressbook_Model_Contact $_contact = null)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($_vcard, true));
        
        if ($_vcard instanceof Sabre_VObject_Component) {
            $vcard = $_vcard;
        } else {
            if (is_resource($_vcard)) {
                $_vcard = stream_get_contents($_vcard);
            }
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' cardData ' . print_r($_vcard, true));
            $vcard = Sabre_VObject_Reader::read($_vcard);
        }
        
        if ($_contact instanceof Addressbook_Model_Contact) {
            $contact = $_contact;
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
     * convert Addressbook_Model_Contact to Sabre_VObject_Component
     * 
     * @param  Addressbook_Model_Contact  $_contact
     * @return Sabre_VObject_Component
     */
    public static function convertToVCard(Addressbook_Model_Contact $_contact)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_contact->toArray(), true));
        
        $card = new Sabre_VObject_Component('VCARD');
        
        // required vcard fields
        $card->add(new Sabre_VObject_Property('VERSION', '3.0'));
        $card->add(new Sabre_VObject_Property('FN', $_contact->n_fileas));
        $card->add(new Sabre_VObject_Property('N', $_contact->n_family . ';' . $_contact->n_given));
        
        $card->add(new Sabre_VObject_Property('PRODID', '-//tine20.org//Tine 2.0//EN'));
        $card->add(new Sabre_VObject_Property('UID', $_contact->getId()));

        $card->add(new Sabre_VObject_Property('ORG', Sabre_VObject_Property::concatCompoundValues(array($_contact->org_name, $_contact->org_unit))));
        $card->add(new Sabre_VObject_Property('TITLE', $_contact->title));
        
        $card->add(new Sabre_VObject_Property('TEL;TYPE=work', $_contact->tel_work));
        $card->add(new Sabre_VObject_Property('TEL;TYPE=cell', $_contact->tel_cell));
        $card->add(new Sabre_VObject_Property('TEL;TYPE=fax',  $_contact->tel_fax));
        $card->add(new Sabre_VObject_Property('TEL;TYPE=home', $_contact->tel_home));
        
        $card->add(new Sabre_VObject_Property('ADR;TYPE=work', Sabre_VObject_Property::concatCompoundValues(array(null, $_contact->adr_one_street2, $_contact->adr_one_street, $_contact->adr_one_locality, $_contact->adr_one_region, $_contact->adr_one_postalcode, $_contact->adr_one_countryname))));
        $card->add(new Sabre_VObject_Property('ADR;TYPE=home', Sabre_VObject_Property::concatCompoundValues(array(null, $_contact->adr_two_street2, $_contact->adr_two_street, $_contact->adr_two_locality, $_contact->adr_two_region, $_contact->adr_two_postalcode, $_contact->adr_two_countryname))));
        
        $card->add(new Sabre_VObject_Property('EMAIL;TYPE=work', $_contact->email));
        $card->add(new Sabre_VObject_Property('EMAIL;TYPE=home', $_contact->email_home));
        
        $card->add(new Sabre_VObject_Property('URL;TYPE=work', $_contact->url));
        $card->add(new Sabre_VObject_Property('URL;TYPE=home', $_contact->url_home));
        
        $card->add(new Sabre_VObject_Property('NOTE', $_contact->note));
        
        if(! empty($_contact->jpegphoto)) {
            try {
                $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $_contact->getId());
                $jpegData = $image->getBlob('image/jpeg');
                $card->add(new Sabre_VObject_Property('PHOTO;ENCODING=b;TYPE=JPEG', base64_encode($jpegData)));
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Image for contact {$_contact->getId()} not found or invalid");
            }
        
        
        }        
        if(isset($_contact->tags) && count($_contact->tags) > 0) {
            $card->add(new Sabre_VObject_Property('CATEGORIES', Sabre_VObject_Property::concatCompoundValues((array) $_contact->tags->name, ',')));
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card->serialize();
    }
    
    /**
     * return Addressbook_Model_Contact and convert contact id to model if needed
     * 
     * @return Addressbook_Model_Contact
     */
    protected function _getContact()
    {
        if (! $this->_contact instanceof Addressbook_Model_Contact) {
            $this->_contact = str_replace('.vcf', '', $this->_contact);
            $this->_contact = Addressbook_Controller_Contact::getInstance()->get($this->_contact);
        }
        
        return $this->_contact;
    }
    
    /**
     * return vcard and convert Addressbook_Model_Contact to vcard if needed
     * 
     * @return string
     */
    protected function _getVCard()
    {
        if ($this->_vcard == null) {
            $this->_vcard = self::convertToVCard($this->_getContact());
        }
        
        return $this->_vcard;
    }
}
