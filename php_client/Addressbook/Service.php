<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     yet unknown
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Addressbook Service
 *
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
        $client = $this->getConnection();
        
        $client->setParameterPost(array(
            'method'    => 'Addressbook.getContact',
            'contactId' => $_contactId
        ));        
        $response = $client->request('POST');
        if($this->debugEnabled === true) {
            var_dump( $client->getLastRequest());
            var_dump( $response );
        }

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
        
        $client = $this->getConnection();
        
        $client->setParameterPost(array(
            'method'   => 'Addressbook.saveContact',
            'contactData'  => Zend_Json::encode($_contact->toArray())
        ));        
        $response = $client->request('POST');
        if($this->debugEnabled === true) {
            var_dump( $client->getLastRequest());
            var_dump( $response );
        }

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
}