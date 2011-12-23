<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  Controller
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2011 Metaways Infosystems GmbH (http://www.metaways.de)
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
        'AssistnamePhoneNumber' => 'tel_assistent',
        'Birthday'              => 'bday',
        #'Body'                  => 'body',
        #'BodySize'              => 'bodysize',
        #'BodyTruncated'         => 'bodytruncated',
        #'Business2PhoneNumber'  => 'business2phonenumber',
        'BusinessCity'          => 'adr_one_locality',
        'BusinessCountry'       => 'adr_one_countryname',
        'BusinessPostalCode'    => 'adr_one_postalcode',
        'BusinessState'         => 'adr_one_region',
        'BusinessStreet'        => 'adr_one_street',
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
        'HomeCity'              => 'adr_two_locality',
        'HomeCountry'           => 'adr_two_countryname',
        'HomePostalCode'        => 'adr_two_postalcode',
        'HomeState'             => 'adr_two_region',
        'HomeStreet'            => 'adr_two_street',
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
        #'Title'                 => '', //salutation_id
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
    protected $_defaultFolderType   = ActiveSync_Command_FolderSync::FOLDERTYPE_CONTACT;
    
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
    protected $_folderType          = ActiveSync_Command_FolderSync::FOLDERTYPE_CONTACT_USER_CREATED;

    /**
     * name of property which defines the filterid for different content classes
     * 
     * @var string
     */
    protected $_filterProperty = 'contactsfilter_id';        
    
    /**
     * field to sort search results by
     * 
     * @var string
     */
    protected $_sortField = 'n_fileas';
    
    /**
     * append contact data to xml element
     *
     * @param DOMElement  $_xmlNode   the parrent xml node
     * @param string      $_folderId  the local folder id
     * @param string      $_serverId  the local entry id
     * @param boolean     $_withBody  retrieve body of entry
     */
    public function appendXML(DOMElement $_xmlNode, $_folderId, $_serverId, array $_options, $_neverTruncate = false)
    {
        $data = $_serverId instanceof Tinebase_Record_Abstract ? $_serverId : $this->_contentController->get($_serverId);
        
        foreach($this->_mapping as $key => $value) {
        	$nodeContent = null;
            if(!empty($data->$value)) {
                switch($value) {
                    case 'bday':
                        
                        if ($this->_device->devicetype == ActiveSync_Backend_Device::TYPE_PALM) {
                            $userTimezone = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
                            $data->bday->setTimezone($userTimezone);
                            $data->bday->addHour(12);
                            $data->bday = new Tinebase_DateTime($data->bday->format(Tinebase_Record_Abstract::ISO8601LONG), 'UTC');
                        }
                        
                        $nodeContent = $data->bday->format("Y-m-d\TH:i:s") . '.000Z';
                        break;
                        
                    case 'jpegphoto':
                        if(! empty($data->$value)) {
                            try {
                                $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $data->getId());
                                $jpegData = $image->getBlob('image/jpeg', 36000);
                                $nodeContent = base64_encode($jpegData);
                            } catch (Exception $e) {
                                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Image for contact {$data->getId()} not found or invalid");
                            }

                            
                        }
                        break;
                        
                    case 'adr_one_countryname':
                    case 'adr_two_countryname':
                    	$nodeContent = Tinebase_Translation::getCountryNameByRegionCode($data->$value);
                    	break;
                        
                    default:
                        $nodeContent = $data->$value;
                        break;
                }
                
                // skip empty elements
                if($nodeContent === null || $nodeContent == '') {
                    Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " Value for $key is empty. Skip element.");
                    continue;
                }
                
                // create a new DOMElement ...
                $node = new DOMElement($key, null, 'uri:Contacts');

                // ... append it to parent node aka append it to the document ...
                $_xmlNode->appendChild($node);
                
                // ... and now add the content (DomText takes care of special chars)
                $node->appendChild(new DOMText($nodeContent));
                
                
            }
        }
          
        if(isset($data->tags) && count($data->tags) > 0) {
            $categories = $_xmlNode->appendChild(new DOMElement('Categories', null, 'uri:Contacts'));
            foreach($data->tags as $tag) {
                $categories->appendChild(new DOMElement('Category', $tag, 'uri:Contacts'));
            }
        }
    }
    
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    public function toTineModel(SimpleXMLElement $_data, $_entry = null)
    {
        if($_entry instanceof Addressbook_Model_Contact) {
            $contact = $_entry;
        } else {
            $contact = new Addressbook_Model_Contact(null, true);
        }
        unset($contact->jpegphoto);
        
        $xmlData = $_data->children('uri:Contacts');

        foreach($this->_mapping as $fieldName => $value) {
            switch($value) {
                case 'jpegphoto':
                    // do not change if not set
                    if(isset($xmlData->$fieldName)) {
                        if(!empty($xmlData->$fieldName)) {
                            $devicePhoto = base64_decode((string)$xmlData->$fieldName);
                            
                            try {
                                $currentPhoto = Tinebase_Controller::getInstance()->getImage('Addressbook', $contact->getId())->getBlob('image/jpeg', 36000);
                            } catch (Exception $e) {}
                            
                            if (isset($currentPhoto) && $currentPhoto == $devicePhoto) {
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " photo did not change on device -> preserving server photo");
                            } else {
                                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " takeing new contact photo from device (" . strlen($devicePhoto) . "KB)");
                                $contact->jpegphoto = $devicePhoto;
                            }
                        } else {
                            $contact->jpegphoto = '';
                            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ . " deleting contact photo on device request");
                        }
                    }
                    break;
                    
                case 'bday':
                    if(isset($xmlData->$fieldName)) {
                        $isoDate = (string)$xmlData->$fieldName;
                        $contact->bday = new Tinebase_DateTime($isoDate);
                        
                        if (
                            ($this->_device->devicetype == ActiveSync_Backend_Device::TYPE_PALM) ||
                            ($this->_device->devicetype == ActiveSync_Backend_Device::TYPE_IPHONE && $this->_device->getMajorVersion() < 800) ||
                            preg_match("/^\d{4}-\d{2}-\d{2}$/", $isoDate)
                        ) {
                            // iOS < 4 & palm send birthdays to the entered date, but the time the birthday got entered on the device
                            // acutally iOS < 4 somtimes sends the bday at noon but the timezone is not clear
                            // -> we don't trust the time part and set the birthdays timezone to the timezone the user has set in tine
                            $userTimezone = Tinebase_Core::get(Tinebase_Core::USERTIMEZONE);
                            $contact->bday = new Tinebase_DateTime($contact->bday->setTime(0,0,0)->format(Tinebase_Record_Abstract::ISO8601LONG), $userTimezone);
                            $contact->bday->setTimezone('UTC');
                        }
                        
                    } else {
                        $contact->bday = null;
                    }
                    break;
                    
                case 'adr_one_countryname':
                case 'adr_two_countryname':
                    $contact->$value = Tinebase_Translation::getRegionCodeByCountryName((string)$xmlData->$fieldName);
                    break;
                    
                case 'adr_one_street':
                    if(strtolower($this->_device->devicetype) == 'palm') {
                        // palm pre sends the whole address in the <Contacts:BusinessStreet> tag
                        unset($contact->adr_one_street);
                    } else {
                        // default handling for all other devices
                        if(isset($xmlData->$fieldName)) {
                            $contact->$value = (string)$xmlData->$fieldName;
                        } else {
                            $contact->$value = null;
                        }
                    }
                    break;
                    
                case 'email':
                case 'email_home':
                    // android send email address as
                    // Lars Kneschke <l.kneschke@metaways.de>
                    if (preg_match('/(.*)<(.+@[^@]+)>/', (string)$xmlData->$fieldName, $matches)) {
                        $contact->$value = trim($matches[2]);
                    } else {
                        $contact->$value = (string)$xmlData->$fieldName;
                    }
                    break;
                    
                default:
                    if(isset($xmlData->$fieldName)) {
                        $contact->$value = (string)$xmlData->$fieldName;
                    } else {
                        $contact->$value = null;
                    }
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
    
    /**
     * convert contact from xml to Addressbook_Model_ContactFilter
     *
     * @param SimpleXMLElement $_data
     * @return array
     */
    protected function _toTineFilterArray(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('uri:Contacts');
        
        $filterArray = array();
        
        foreach($this->_mapping as $fieldName => $value) {
            if(isset($xmlData->$fieldName)) {
                $filterArray[] = array(
                    'field'     => $value,
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
        return $filterArray;
    }
}
