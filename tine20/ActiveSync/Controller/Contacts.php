<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @subpackage  ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
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
    
    protected $_folders = array(array(
        'folderId'      => 'contatcsroot',
        'parentId'      => 0,
        'displayName'   => 'Contacts',
        'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_CONTACT
    ));
    
    /**
     * get estimate of add,changed or deleted contacts
     *
     * @todo improve filter usage. Filter need to support OR and need to return count only
     * @param Zend_Date $_startTimeStamp
     * @param Zend_Date $_endTimeStamp
     * @return int total count of changed items
     */
    public function getItemEstimate($_startTimeStamp = NULL, $_endTimeStamp = NULL)
    {
        $count = 0;
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        
        if($_startTimeStamp === NULL && $_endTimeStamp === NULL) {
            $filter = new Addressbook_Model_ContactFilter(array()); 
            $count = Addressbook_Controller_Contact::getInstance()->searchCount($filter);
        } elseif($_endTimeStamp === NULL) {
            foreach(array('creation_time', 'last_modified_time', 'deleted_time') as $fieldName) {
                $filter = new Addressbook_Model_ContactFilter(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'after',
                        'value'     => $startTimeStamp
                    ),
                )); 
                $count += Addressbook_Controller_Contact::getInstance()->searchCount($filter);
            }
        } else {
            $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
            
            foreach(array('creation_time', 'last_modified_time', 'deleted_time') as $fieldName) {
                $filter = new Addressbook_Model_ContactFilter(array(
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'after',
                        'value'     => $startTimeStamp
                    ),
                    array(
                        'field'     => $fieldName,
                        'operator'  => 'before',
                        'value'     => $endTimeStamp
                    ),
                )); 
                $count += Addressbook_Controller_Contact::getInstance()->searchCount($filter);
            }
        }
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Count: $count Timestamps: ($startTimeStamp / $endTimeStamp)");
                    
        return $count;
    }
    
    public function getSince($_field, $_startTimeStamp, $_endTimeStamp)
    {
        switch($_field) {
            case 'added':
                $fieldName = 'creation_time';
                break;
            case 'changed':
                $fieldName = 'last_modified_time';
                break;
            case 'deleted':
                $fieldName = 'deleted_time';
                break;
            default:
                throw new Exception("$_field must be either added, changed or deleted");                
        }
        
        $startTimeStamp = ($_startTimeStamp instanceof Zend_Date) ? $_startTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_startTimeStamp;
        $endTimeStamp = ($_endTimeStamp instanceof Zend_Date) ? $_endTimeStamp->get(Tinebase_Record_Abstract::ISO8601LONG) : $_endTimeStamp;
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array(
                'field'     => $fieldName,
                'operator'  => 'after',
                'value'     => $startTimeStamp
            ),
            array(
                'field'     => $fieldName,
                'operator'  => 'before',
                'value'     => $endTimeStamp
            ),
        ));
        $result = Addressbook_Controller_Contact::getInstance()->search($filter);
        
        return $result;
    }    
    
    public function appendXML($_xmlDocument, $_xmlNode, $_data)
    {
        foreach($this->_mapping as $key => $value) {
            if(isset($_data->$value)) {
                switch($value) {
                    case 'bday':
                        # 2008-12-18T23:00:00.000Z
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Contacts', $key, $_data->bday->getIso()));
                        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Birthday " . $_data->bday->getIso());
                        break;
                    case 'jpegphoto':
                        break;
                    default:
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Contacts', $key, $_data->$value));
                        break;
                }
            }
        }        
    }
    
    public function add($_collectionId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId");
        
        $contact = $this->_toTine20Contact($_data);
        $contact->creation_time = $this->_syncTimeStamp;
        
        $contact = Addressbook_Controller_Contact::getInstance()->create($contact);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " added contact id " . $contact->getId());

        return $contact;
    }
    
    public function search($_collectionId, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId");
        
        $filter = $this->_toTine20ContactFilter($_data);
        
        $foundContacts = Addressbook_Controller_Contact::getInstance()->search($filter);

        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " found " . count($foundContacts));
            
        return $foundContacts;
    }
    
    public function change($_collectionId, $_id, SimpleXMLElement $_data)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " CollectionId: $_collectionId Id: $_id");
        
        $contactsController = Addressbook_Controller_Contact::getInstance();
        
        $oldContact = $contactsController->get($_id); 
        
        $contact = $this->_toTine20Contact($_data);
        $contact->setId($_id);
        $contact->container_id = $oldContact->container_id;
        $contact->last_modified_time = $this->_syncTimeStamp;
        
        $contact = $contactsController->update($contact);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " updated contact id " . $contact->getId());

        return $contact;
    }
    
    /**
     * delete contact
     *
     * @param unknown_type $_collectionId
     * @param unknown_type $_id
     */
    public function delete($_collectionId, $_id)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " ColectionId: $_collectionId Id: $_id");
        
        Addressbook_Controller_Contact::getInstance()->delete($_id);
        
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " deleted contact id " . $_id);
    }
    
    /**
     * convert contact from xml to Addressbook_Model_Contact
     *
     * @todo handle images
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_Contact
     */
    protected function _toTine20Contact(SimpleXMLElement $_data)
    {
        $contactData = array();
        $xmlData = $_data->children('uri:Contacts');
        
        foreach($this->_mapping as $fieldName => $value) {
            if(isset($xmlData->$fieldName)) {
                switch($value) {
                    case 'jpegphoto':
                        $contactData[$value] = base64_decode((string)$xmlData->$fieldName);
                        #$fp = fopen('/tmp/data.txt', 'w');
                        #fwrite($fp, base64_decode((string)$_data->Picture));
                        #fclose($fp);
                        break;
                    default:
                        $contactData[$value] = (string)$xmlData->$fieldName;
                        break;
                }
            }
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " contactData " . print_r($contactData, true));
        $contact = new Addressbook_Model_Contact($contactData);
        
        return $contact;
    }
    
    /**
     * convert contact from xml to Addressbook_Model_ContactFilter
     *
     * @param SimpleXMLElement $_data
     * @return Addressbook_Model_ContactFilter
     */
    protected function _toTine20ContactFilter(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('Contacts');
        
        $contactFilter = new Addressbook_Model_ContactFilter(array(
            array(
                'field'     => 'containerType',
                'operator'  => 'equals',
                'value'     => 'all'
            )
        )); 
    
        foreach($this->_mapping as $fieldName => $value) {
            if($contactFilter->has($value)) {
                $contactFilter->$value = array(
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " contactData " . print_r($contactFilter, true));
        
        return $contactFilter;
    }
}