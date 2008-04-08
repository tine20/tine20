<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Service.php 1313 2008-03-22 08:08:49Z lkneschke $
 */

class Addressbook_Service extends TineClient_Service_Abstract
{
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
        
        $contact = new Addressbook_Model_Contact($responseData['contactData']);
        
        return $contact;
    }
    
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
			echo "<hr>";
            var_dump( $client->getLastRequest());
            var_dump( $response );
        	echo "<hr>";
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
    
    public function updateContact(Addressbook_Model_Contact $_contact)
    {
        
    }
}