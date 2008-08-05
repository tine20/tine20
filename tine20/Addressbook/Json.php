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
        $result = array(
            'success'   => true
        );

        $contact = Addressbook_Controller::getInstance()->getContact($contactId);
        $result['contact'] = $this->_contactToJson($contact);
        
        return $result;
    }
    
    /**
     * Search for contacts matching given arguments
     *
     * @param array $filter
     * @return array
     * 
     * @todo add timezone?
     */
    public function searchContacts($filter, $paging)
    {
        $filter = new Addressbook_Model_ContactFilter(Zend_Json::decode($filter));
        $pagination = new Tinebase_Model_Pagination(Zend_Json::decode($paging));
        
        //Zend_Registry::get('logger')->debug(print_r($decodedFilter,true));
        
        $contacts = Addressbook_Controller::getInstance()->searchContacts($filter, $pagination);
        //$contacts->setTimezone($this->_userTimezone);
        //$contacts->convertDates = true;
        
        $result = array();
        foreach ($contacts as $contact) {
            $result[] = $this->_contactToJson($contact);
        }
        
        return array(
            'results'       => $result,
            'totalcount'    => Addressbook_Controller::getInstance()->searchContactsCount($filter)
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
        
        Addressbook_Controller::getInstance()->deleteContact($contactIds);

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
        $contact->setFromJson($contactData);
        
        if (empty($contact->id)) {
            $contact = Addressbook_Controller::getInstance()->createContact($contact);
        } else {
            $contact = Addressbook_Controller::getInstance()->updateContact($contact);
        }

        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $this->_contactToJson($contact, TRUE)
        );         
        
        return $result;
         
    }
    
    /****************************************** helper functions ***********************************/

    /**
     * returns contact prepared for json transport
     *
     * @param Addressbook_Model_Contact $_contact
     * @return array contact data
     * 
     * @todo add account grants again for list view -> improve performance first
     * @todo get tags (?) / account grants for all records at once 
     */
    protected function _contactToJson($_contact, $_getAccountGrants = FALSE)
    {        
        $result = $_contact->toArray();
        $result['owner'] = Tinebase_Container::getInstance()->getContainerById($_contact->owner)->toArray();
        
        // get tags for preview ?
        //$result['tags'] = Tinebase_Tags::getInstance()->getTagsOfRecord($_contact)->toArray();
        
        // removed for list view because it took 50% of the execution time
        if ($_getAccountGrants) {
            $result['owner']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $_contact->owner)->toArray();
        }
        
        $result['jpegphoto'] = $this->_getImageLink($_contact);
        
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
            $link = 'images/empty_photo.jpg';
        }
        return $link;
    }
}