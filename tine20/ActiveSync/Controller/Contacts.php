<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL), 
 *              Version 1, the distribution of the Tine 2.0 ActiveSync module in or to the 
 *              United States of America is excluded from the scope of this license.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * class documentation
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 */
 
class ActiveSync_Controller_Contacts extends ActiveSync_Controller_Abstract 
{
    protected $_mapping = array(
        #'Anniversary'           => 'anniversary',
        #'AssistantName'         => 'assistantname',
        #'AssistnamePhoneNumber' => 'assistnamephonenumber',
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
        #'Home2PhoneNumber'      => 'home2phonenumber',
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
        #'PagerNumber'           => 'pagernumber',
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
    public function appendXML(DOMElement $_xmlNode, $_folderId, $_serverId, $_withBody = false)
    {
        $data = $this->_contentController->get($_serverId);
        
        foreach($this->_mapping as $key => $value) {
        	$nodeContent = null;
            if(!empty($data->$value)) {
                switch($value) {
                    case 'bday':
                        if(strtolower($this->_device->devicetype) == 'iphone') {
                            $data->bday->addHour(12);
                        }
                        
                        $nodeContent = $data->bday->toString('yyyy-MM-ddTHH:mm:ss') . '.000Z';
                        break;
                        
                    case 'jpegphoto':
                        if(! empty($data->$value)) {
                            try {
                                $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $data->getId());
                                $image->resize(120, 160, Tinebase_Model_Image::RATIOMODE_PRESERVANDCROP);
                                $jpegData = $image->getBlob('image/jpeg');
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
    
    protected function _getSyncableFolders()
    {
        $folders = array();
    	
        $containers = Tinebase_Container::getInstance()->getPersonalContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Core::getUser(), Tinebase_Model_Grants::GRANT_SYNC);
        foreach ($containers as $container) {
            $folders[$container->id] = array(
                'folderId'      => $container->id,
                'parentId'      => 0,
                'displayName'   => $container->name,
                'type'          => (count($folders) == 0) ? $this->_defaultFolderType : $this->_folderType
           );
        }
	            
        $containers = Tinebase_Container::getInstance()->getSharedContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Grants::GRANT_SYNC);
        foreach ($containers as $container) {
            $folders[$container->id] = array(
                'folderId'      => $container->id,
                'parentId'      => 0,
                'displayName'   => $container->name,
                'type'          => $this->_folderType
            );
        }
	    
        try {
            $accountsFolder = Tinebase_Container::getInstance()->getInternalContainer(Tinebase_Core::getUser(), $this->_applicationName, Tinebase_Model_Grants::GRANT_SYNC);
            $folders[$accountsFolder->id] = array(
                'folderId'      => $accountsFolder->id,
                'parentId'      => 0,
                'displayName'   => $accountsFolder->name,
                'type'          => $this->_folderType
            );
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . " leaving out internal container as user has no GRANT_SYNC for it");
        }
        
        // we ignore the folders of others users for now
	            
        return $folders;
    }
    
    /**
     * return list of supported folders for this backend
     *
     * @return array
     */
    public function getSupportedFolders()
    {
        // only the IPhone supports multiple folders for contacts currently
        if(in_array(strtolower($this->_device->devicetype), array('iphone', 'thundertine'))) {
        
            // get the folders the user has access to
            $allowedFolders = $this->_getSyncableFolders();
            
            $wantedFolders = null;
            // maybe the user has defined a filter to limit the search results
            if(!empty($this->_device->contactsfilter_id)) {
                $persistentFilter = Tinebase_PersistentFilter::getFilterById($this->_device->contactsfilter_id);
                
                foreach($persistentFilter as $filter) {
                    if($filter instanceof Tinebase_Model_Filter_Container) {
                        $wantedFolders = array_flip($filter->getContainerIds());
                    }
                }
            }
            
            $folders = $wantedFolders === null ? $allowedFolders : array_intersect_key($allowedFolders, $wantedFolders);
        } else {
            
            $folders[$this->_specialFolderName] = array(
                'folderId'      => $this->_specialFolderName,
                'parentId'      => 0,
                'displayName'   => $this->_applicationName,
                'type'          => $this->_defaultFolderType
            );
            
        }
        
        return $folders;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @todo handle images
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    protected function _toTineModel(SimpleXMLElement $_data, $_entry = null)
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
                            $contact->jpegphoto = base64_decode((string)$xmlData->$fieldName);
                        } else {
                            $contact->jpegphoto = '';
                        }
                    }
                    break;
                    
                case 'bday':
                    if(isset($xmlData->$fieldName)) {
                        $timeStamp = $this->_convertISOToTs((string)$xmlData->$fieldName);
                        $contact->bday = new Zend_Date($timeStamp, NULL);
                        
                        switch(strtolower($this->_device->devicetype)) {
                            // the iphone sends the birthday based noon
                            // Tine 2.0 stores the birthday at midnight
                            case 'iphone':
                                $contact->bday->subHour(12);
                                break;
                                
                            // the palm sets the birthday to the time the birthday got entered on the device
                            // thats something we can't work with 
                            case 'palm':
                                unset($contact->bday);
                                break;
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " contactData " . print_r($contact->toArray(), true));
        
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
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filterArray, true));
        
        return $filterArray;
    }
    
    /**
     * converts an iso formated date into a timestamp
     *
     * @param  string Zend_Date::ISO8601 representation of a datetime filed
     * @return int    UNIX Timestamp
     */
    protected function _convertISOToTs($_ISO)
    {
        $matches = array();
        
        preg_match("/^(\d{4})-(\d{2})-(\d{2})[T ]{1}(\d{2}):(\d{2}):(\d{2})/", $_ISO, $matches);

        if (count($matches) !== 7) {
            throw new Tinebase_Exception_UnexpectedValue("invalid date format $_ISO");
        }
        
        list($match, $year, $month, $day, $hour, $minute, $second) = $matches;
        return  mktime($hour, $minute, $second, $month, $day, $year);
    }
}