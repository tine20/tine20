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
     * list of supported folders
     * @todo retrieve users real container
     * @var array
     */
    protected $_folders = array(array(
        'folderId'      => 'contatcsroot',
        'parentId'      => 0,
        'displayName'   => 'Contacts',
        'type'          => ActiveSync_Command_FolderSync::FOLDERTYPE_CONTACT
    ));
    
    protected $_applicationName     = 'Addressbook';
    
    protected $_modelName           = 'Contact';
        
    /**
     * append contact to xml parent node
     *
     * @param DOMDocument $_xmlDocument
     * @param DOMElement $_xmlNode
     * @param string $_serverId
     */
    public function appendXML(DOMDocument $_xmlDocument, DOMElement $_xmlNode, $_serverId)
    {
        $data = $this->_contentController->get($_serverId);
        
        foreach($this->_mapping as $key => $value) {
            if(!empty($data->$value)) {
                switch($value) {
                    case 'bday':
                        # 2008-12-18T23:00:00.000Z
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Contacts', $key, $data->bday->getIso()));
                        #Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Birthday " . $_data->bday->getIso());
                        break;
                    case 'jpegphoto':
                        // do nothing currently
                        break;
                    default:
                        $_xmlNode->appendChild($_xmlDocument->createElementNS('uri:Contacts', $key, $data->$value));
                        break;
                }
            }
        }        
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
        
        $xmlData = $_data->children('uri:Contacts');

        foreach($this->_mapping as $fieldName => $value) {
            switch($value) {
                case 'jpegphoto':
                    // do not change if not set
                    if(isset($xmlData->$fieldName)) {
                        $contact->$value = base64_decode((string)$xmlData->$fieldName);
                    }
                    break;
                case 'bday':
                    // do nothing
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
     * @return Addressbook_Model_ContactFilter
     */
    protected function _toTineFilter(SimpleXMLElement $_data)
    {
        $xmlData = $_data->children('Contacts');
        
        $filter = new Addressbook_Model_ContactFilter(array(
            array(
                'field'     => 'containerType',
                'operator'  => 'equals',
                'value'     => 'all'
            )
        )); 
    
        foreach($this->_mapping as $fieldName => $value) {
            if($filter->has($value)) {
                $filter->$value = array(
                    'operator'  => 'equals',
                    'value'     => (string)$xmlData->$fieldName
                );
            }
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " filterData " . print_r($filter, true));
        
        return $filter;
    }
}