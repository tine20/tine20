<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * backend class for Zend_Json_Server
 *
 * This class handles all Json requests for the addressbook application
 *
 * @package     Addressbook
 * @todo        handle timezone management
 */
class Addressbook_Json extends Tinebase_Application_Json_Abstract
{
    protected $_appname = 'Addressbook';
    
    /****************************************** get contacts *************************************/

    /**
     * get one contact identified by contactId
     *
     * @param int $contactId
     * @return array
     */
    public function getContact($contactId)
    {
        $result = array();
               
        $contact = Addressbook_Controller_Contact::getInstance()->getContact($contactId);
        $result = $this->_contactToJson($contact);
        
        return $result;
    }
    
    /**
     * Search for contacts matching given arguments
     *
     * @param string $filter json encoded
     * @param string $paging json encoded
     * @return array
     * 
     * @todo add timezone?
     */
    public function searchContacts($filter, $paging)
    {
        $filter = new Addressbook_Model_ContactFilter(Zend_Json::decode($filter));
        $pagination = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        //Zend_Registry::get('logger')->debug(print_r($decodedFilter,true));
        
        $contacts = Addressbook_Controller_Contact::getInstance()->searchContacts($filter, $pagination);
        //$contacts->setTimezone($this->_userTimezone);
        //$contacts->convertDates = true;
        
        return array(
            'results'       => $this->_multipleContactsToJson($contacts),
            'totalcount'    => Addressbook_Controller_Contact::getInstance()->searchContactsCount($filter)
        );
    }    

    /****************************************** save / delete contacts ****************************/
    
    /**
     * delete multiple contacts
     *
     * @param array $_contactIDs list of contactId's to delete
     * @return array
     */
    public function deleteContacts($_contactIds)
    {
        $result = array(
            'success'   => TRUE
        );
        
        $contactIds = Zend_Json::decode($_contactIds);
        
        Addressbook_Controller_Contact::getInstance()->deleteContact($contactIds);

        return $result;
    }
          
    /**
     * save one contact
     *
     * if $contactData['id'] is empty the contact gets added, otherwise it gets updated
     *
     * @param string $contactData a JSON encoded array of contact properties
     * @return array
     */
    public function saveContact($contactData)
    {
        $contact = new Addressbook_Model_Contact();
        $contact->setFromJsonInUsersTimezone($contactData);
        
        if (empty($contact->id)) {
            $contact = Addressbook_Controller_Contact::getInstance()->createContact($contact);
        } else {
            $contact = Addressbook_Controller_Contact::getInstance()->updateContact($contact);
        }

        $result =  $this->getContact($contact->getId());
        return $result;
         
    }

    /****************************************** get salutations ****************************/
    
    /**
     * get salutations
     *
     * @return array
     */
   public function getSalutations()
    {
         $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        if($rows = Addressbook_Controller_Salutation::getInstance()->getSalutations()) {
            $rows->translate();
            $result['results']      = $rows->toArray();
            $result['totalcount']   = count($result['results']);
        }

        return $result;    
    }  
    
    /****************************************** helper functions ***********************************/

    /**
     * returns contact prepared for json transport
     *
     * @param Addressbook_Model_Contact $_contact
     * @return array contact data
     */
    protected function _contactToJson($_contact)
    {   
        
        $_contact->setTimezone(Zend_Registry::get('userTimeZone'));
        $result = $_contact->toArray();
        
        $result['container_id'] = Tinebase_Container::getInstance()->getContainerById($_contact->container_id)->toArray();
        $result['container_id']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $_contact->container_id)->toArray();
        
        $result['jpegphoto'] = $this->_getImageLink($_contact);
        
        return $result;
    }

    /**
     * returns multiple contacts prepared for json transport
     *
     * @param Tinebase_Record_RecordSet $_contacts Addressbook_Model_Contact
     * @return array contacts data
     */
    protected function _multipleContactsToJson(Tinebase_Record_RecordSet $_contacts)
    {        
        // get acls for contacts
        Tinebase_Container::getInstance()->getGrantsOfRecords($_contacts, Zend_Registry::get('currentAccount'));
        
        $_contacts->setTimezone(Zend_Registry::get('userTimeZone'));
        $result = $_contacts->toArray();
        
        foreach ($result as &$contact) {
            $contact['jpegphoto'] = $this->_getImageLink($contact);
        }
        
        //Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($result, true));
        
        return $result;
    }
    
    /**
     * returns a image link
     * 
     * @param  Addressbook_Model_Contact|array
     * @return string
     */
    protected function _getImageLink($contact)
    {
        if (!empty($contact->jpegphoto)) {
            $link =  'index.php?method=Tinebase.getImage&application=Addressbook&location=&id=' . $contact['id'] . '&width=90&height=90&ratiomode=0';
        } else {
            $link = 'images/empty_photo.png';
        }
        return $link;
    }

    /**
     * Returns registry data of addressbook.
     * @see Tinebase_Application_Json_Abstract
     * 
     * @return mixed array 'variable name' => 'data'
     */
    public function getRegistryData()
    {   
        $registryData = array(
            'Salutations' => $this->getSalutations(),
        );        
        return $registryData;    
    }
}