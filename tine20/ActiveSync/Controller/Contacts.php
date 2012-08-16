<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  Controller
 */
 
class ActiveSync_Controller_Contacts extends ActiveSync_Controller_Abstract 
{
    protected $_mapping = array(
        #'Anniversary'           => 'anniversary',
        #'AssistantName'         => 'assistantname',
        'AssistantPhoneNumber' => 'tel_assistent',
        'Birthday'              => 'bday',
        'Body'                  => 'note',
        #'BodySize'              => 'bodysize',
        #'BodyTruncated'         => 'bodytruncated',
        #'Business2PhoneNumber'  => 'business2phonenumber',
        'BusinessAddressCity'          => 'adr_one_locality',
        'BusinessAddressCountry'       => 'adr_one_countryname',
        'BusinessAddressPostalCode'    => 'adr_one_postalcode',
        'BusinessAddressState'         => 'adr_one_region',
        'BusinessAddressStreet'        => 'adr_one_street',
        'BusinessFaxNumber'     => 'tel_fax',
        'BusinessPhoneNumber'   => 'tel_work',
        #'CarPhoneNumber'        => 'carphonenumber',
        #'Categories'            => 'categories',
        #'Category'              => 'category',
        #'Children'              => 'children',
        #'Child'                 => 'child',
        'CompanyName'           => 'org_name',
        'Department'            => 'org_unit',
        'Email1Address'         => 'email',
        'Email2Address'         => 'email_home',
        #'Email3Address'         => 'email3address',
        'FileAs'                => 'n_fileas',
        'FirstName'             => 'n_given',
        'Home2PhoneNumber'      => 'tel_cell_private',
        'HomeAddressCity'              => 'adr_two_locality',
        'HomeAddressCountry'           => 'adr_two_countryname',
        'HomeAddressPostalCode'        => 'adr_two_postalcode',
        'HomeAddressState'             => 'adr_two_region',
        'HomeAddressStreet'            => 'adr_two_street',
        'HomeFaxNumber'         => 'tel_fax_home',
        'HomePhoneNumber'       => 'tel_home',
        'JobTitle'              => 'title', 
        'LastName'              => 'n_family',
        'MiddleName'            => 'n_middle',
        'MobilePhoneNumber'     => 'tel_cell',
        'OfficeLocation'        => 'room',
        #'OtherCity'             => 'adr_one_locality',
        #'OtherCountry'          => 'adr_one_countryname',
        #'OtherPostalCode'       => 'adr_one_postalcode',
        #'OtherState'            => 'adr_one_region',
        #'OtherStreet'           => 'adr_one_street',
        'PagerNumber'           => 'tel_pager',
        #'RadioPhoneNumber'      => 'radiophonenumber',
        #'Spouse'                => 'spouse',
        'Suffix'                => 'n_prefix',
        #'Title'                 => '', //salutation
        'WebPage'               => 'url',
        #'YomiCompanyName'       => 'yomicompanyname',
        #'YomiFirstName'         => 'yomifirstname',
        #'YomiLastName'          => 'yomilastname',
        #'Rtf'                   => 'rtf',
        'Picture'               => 'jpegphoto'
    );
        
    /**
     * name of Tine 2.0 backend application
     * 
     * @var string
     */
    protected $_applicationName     = 'Addressbook';
    
    /**
     * name of Tine 2.0 model to use
     * 
     * @var string
     */
    protected $_modelName           = 'Contact';
    
    /**
     * type of the default folder
     *
     * @var int
     */
    protected $_defaultFolderType   = Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT;
    
    /**
     * default container for new entries
     * 
     * @var string
     */
    protected $_defaultFolder       = ActiveSync_Preference::DEFAULTADDRESSBOOK;
    
    /**
     * type of user created folders
     *
     * @var int
     */
    protected $_folderType          = Syncroton_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED;

    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'contactsfilterId';
    
    /**
     * field to sort search results by
     * 
     * @var string
     */
    protected $_sortField = 'n_fileas';

    /**
     * (non-PHPdoc)
     * @see ActiveSync_Controller_Abstract::toSyncrotonModel()
     */
    public function toSyncrotonModel($entry, array $options = array())
    {
        $syncrotonContact = new Syncroton_Model_Contact();
        
        foreach ($this->_mapping as $syncrotonProperty => $tine20Property) {
            if (empty($entry->$tine20Property) && $entry->$tine20Property != '0') {
                continue;
            }
            
            switch($tine20Property) {
                case 'adr_one_countryname':
                case 'adr_two_countryname':
                    $syncrotonContact->$syncrotonProperty = Tinebase_Translation::getCountryNameByRegionCode($entry->$tine20Property);
                    
                    break;
                
                case 'note':
                    $syncrotonContact->$syncrotonProperty = new Syncroton_Model_EmailBody(array(
                        'Type' => Syncroton_Model_EmailBody::TYPE_PLAINTEXT,
                        'Data' => $entry->$tine20Property
                    ));
                    
                    break;
                    
                case 'jpegphoto':
                    try {
                        $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $entry->getId());
                        $syncrotonContact->$syncrotonProperty = $image->getBlob('image/jpeg', 36000);
                    } catch (Exception $e) {
                        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Image for contact {$entry->getId()} not found or invalid");
                    }
                    
                    break;
                    
                // @todo validate tags are working
                case 'tags':
                    $syncrotonContact->$syncrotonProperty = (array) $entry->$tine20Property;
                    
                    break;
                    
                default:
                    $syncrotonContact->$syncrotonProperty = $entry->$tine20Property;
                    
                    break;
            }
        }
        
        return $syncrotonContact;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    public function toTineModel(Syncroton_Model_IEntry $data, $entry = null)
    {
        if ($entry instanceof Addressbook_Model_Contact) {
            $contact = $entry;
        } else {
            $contact = new Addressbook_Model_Contact(null, true);
        }
        unset($contact->jpegphoto);
        
        foreach($this->_mapping as $fieldName => $value) {
            if (!isset($data->$fieldName)) {
                $contact->$value = null;
                
                continue;
            }
            
            switch ($value) {
                case 'jpegphoto':
                    if(!empty($data->$fieldName)) {
                        $devicePhoto = $data->$fieldName;
                        
                        try {
                            $currentPhoto = Tinebase_Controller::getInstance()->getImage('Addressbook', $contact->getId())->getBlob('image/jpeg', 36000);
                        } catch (Exception $e) {}
                        
                        if (isset($currentPhoto) && $currentPhoto == $devicePhoto) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " photo did not change on device -> preserving server photo");
                        } else {
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " using new contact photo from device (" . strlen($devicePhoto) . "KB)");
                            $contact->jpegphoto = $devicePhoto;
                        }
                    } else if ($entry && ! empty($entry->jpegphoto)) {
                        $contact->jpegphoto = '';
                        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ 
                            . ' Deleting contact photo on device request (contact id: ' . $contact->getId() . ')');
                    }
                    
                    break;
                    
                case 'bday':
                    #$isoDate = (string)$data->$fieldName;
                    $contact->$value = new Tinebase_DateTime($data->$fieldName);
                    
                    if (
                        // ($this->_device->devicetype == Syncroton_Model_Device::TYPE_WEBOS) || // only valid versions < 2.1
                        ($this->_device->devicetype == Syncroton_Model_Device::TYPE_IPHONE && $this->_device->getMajorVersion() < 800)/* ||
                        preg_match("/^\d{4}-\d{2}-\d{2}$/", $isoDate)*/
                    ) {
                        // iOS < 4 & webow < 2.1 send birthdays to the entered date, but the time the birthday got entered on the device
                        // acutally iOS < 4 somtimes sends the bday at noon but the timezone is not clear
                        // -> we don't trust the time part and set the birthdays timezone to the timezone the user has set in tine
                        $userTimezone = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
                        $contact->$value = new Tinebase_DateTime($contact->bday->setTime(0,0,0)->format(Tinebase_Record_Abstract::ISO8601LONG), $userTimezone);
                        $contact->$value->setTimezone('UTC');
                    }
                        
                    break;
                    
                case 'adr_one_countryname':
                case 'adr_two_countryname':
                    $contact->$value = Tinebase_Translation::getRegionCodeByCountryName($data->$fieldName);
                    break;
                    
                case 'adr_one_street':
                    if (strtolower($this->_device->devicetype) == 'palm') {
                        // palm pre sends the whole address in the <Contacts:BusinessStreet> tag
                        unset($contact->adr_one_street);
                    } else {
                        $contact->$value = $data->$fieldName;
                    }
                    break;
                    
                case 'email':
                case 'email_home':
                    // android send email address as
                    // Lars Kneschke <l.kneschke@metaways.de>
                    if (preg_match('/(.*)<(.+@[^@]+)>/', $data->$fieldName, $matches)) {
                        $contact->$value = trim($matches[2]);
                    } else {
                        $contact->$value = $data->$fieldName;
                    }
                    break;
                
                case 'note':
                    // @todo check $data->$fieldName->Type and convert to/from HTML if needed
                    if ($data->$fieldName instanceof Syncroton_Model_EmailBody) {
                        $contact->$value = $data->$fieldName->Data;
                    } else {
                        $contact->$value = null;
                    }
                    
                    break;
                    
                case 'url':
                    // remove facebook urls
                    if (! preg_match('/^fb:\/\//', $data->$fieldName)) {
                        $contact->$value = $data->$fieldName;
                    }
                    break;
                    
                default:
                    $contact->$value = $data->$fieldName;
                    
                    break;
            }
        }
        
        // force update of n_fileas and n_fn
        $contact->setFromArray(array(
            'n_given'   => $contact->n_given,
            'n_family'  => $contact->n_family,
            'org_name'  => $contact->org_name
        ));
        
        // contact should be valid now
        $contact->isValid();
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) 
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " contactData " . print_r($contact->toArray(), true));
        
        return $contact;
    }
}
