<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     Addressbook
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Addressbook Service
 *
 * @package     Addressbook
 */
class Addressbook_Service extends Tinebase_Service_Abstract
{
    /**
     * @var bool
     */
    public $debugEnabled = false;
    
    /**
     * retreaves a remote contact identified by its id
     *
     * @param  int $_contactId
     * @return Addressbook_Model_Contact
     */
    public function getContact($_contactId)
    {
        $response = $this->_connection->request(array(
            'method'    => 'Addressbook.getContact',
            'contactId' => $_contactId
        ));

        if(!$response->isSuccessful()) {
            throw new Exception('getting contact failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $contact = new Addressbook_Model_Contact($responseData['contact']);
        return $contact;
    }
    
    /**
     * gets image of contact identified by its id
     *
     * @todo move to Tinebase_Service
     * @param  int $_id
     * @return binary image data
     */
    public function getImage($_id, $_width=130, $_height=130, $_ratiomode=0)
    {
        $response = $this->_connection->request(array(
            'method'        =>  'Tinebase.getImage',
            'application'   => 'Addressbook',
            'id'            =>  $_id,
            'location'      =>  '', 
            'width'         => $_width,
            'height'        =>  $_height,
            'ratiomode'     =>  $_ratiomode,
        ), 'GET');
        
        return($response->getBody());
    }
    
    /**
     * gets all contacts readable for the current user
     *
     * @return Tinebase_Record_RecordSet of Addressbook_Model_Contact
     */
    public function getAllContacts()
    {
        $response = $this->_connection->request(array(
            'method' => 'Addressbook.searchContacts',
            'filter' => Zend_Json::encode(array(
                array(
                    'field'    => 'containerType',
                    'operator' => 'equals',
                    'value'    => 'all'
                ),
                array(
                    'field'    => 'container',
                    'operator' => 'equals',
                    'value'    => NULL
                ),
                array(
                    'field'    => 'owner',
                    'operator' => 'equals',
                    'value'    => NULL
                ),
            )),
           'paging' => Zend_Json::encode(array(
               'sort' => 'n_fileas', 
               'dir' => 'ASC', 
           ))
        ));

        if(!$response->isSuccessful()) {
            throw new Exception('getting all contacts failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $contacts = new Tinebase_Record_RecordSet('Addressbook_Model_Contact', $responseData['results']);
        return $contacts;
    }
        
    
    /**
     * adds / creates a new contact in remote installation
     *
     * @param  Addressbook_Model_Contact $_contact
     * @return Addressbook_Model_Contact
     */
    public function addContact(Addressbook_Model_Contact $_contact)
    {
        if(!$_contact->isValid()) {
            throw new Exception('contact is not valid');
        }
        
        $response = $this->_connection->request(array(
            'method'   => 'Addressbook.saveContact',
            'contactData'  => Zend_Json::encode($_contact->toArray())
        ));

        if(!$response->isSuccessful()) {
            throw new Exception('adding contact failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $contact = new Addressbook_Model_Contact($responseData['updatedData']);
        return $contact;
    }
    
    /**
     * deletes a remote contact identified by its id
     *
     * @param  int $_id
     * @return void
     */
    public function deleteContact($_id)
    {
        $response = $this->_connection->request(array(
            'method'   => 'Addressbook.deleteContacts',
            '_contactIds'  => Zend_Json::encode(array($_id))
        ));

        if(!$response->isSuccessful()) {
            throw new Exception('deleting contact failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
    }
}
