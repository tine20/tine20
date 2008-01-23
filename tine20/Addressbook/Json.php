<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
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
 */
class Addressbook_Json extends Egwbase_Application_Json_Abstract
{
    protected $_appname = 'Addressbook';
    
    public function addAddressbook($name, $type)
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);

        $id = $backend->addAddressbook($name, $type);
        
        $result = array('addressbookId' => $id);
        
        return $result;
    }
    
    public function deleteAddressbook($addressbookId)
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);

        $backend->deleteAddressbook($addressbookId);
            
        return TRUE;
    }
    
    public function renameAddressbook($addressbookId, $name)
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);

        $backend->renameAddressbook($addressbookId, $name);
            
        return TRUE;
    }
    
    /**
     * delete a array of contacts
     *
     * @param array $_contactIDs
     * @return array
     */
    public function deleteContacts($_contactIds)
    {
        $contactIds = Zend_Json::decode($_contactIds);
        if(is_array($contactIds)) {
            $contacts = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
            foreach($contactIds as $contactId) {
                $contacts->deleteContactById($contactId);
            }

            $result = array('success'   => TRUE, 'ids' => $contactIds);
        } else {
            $result = array('success'   => FALSE);
        }

        return $result;
    }
          
    /**
     * save one contact
     *
     * if $_contactId is 0 the contact gets added, otherwise it gets updated
     *
     * @return array
     */
    public function saveContact()
    {
        // convert birthday back to yyyy-mm-dd
        if(isset($_POST['contact_bday'])) {
            $locale = Zend_Registry::get('locale');
            $dateFormat = $locale->getTranslationList('Dateformat');
            try {
                $date = new Zend_Date($_POST['contact_bday'], $dateFormat['long'], 'en');
                $_POST['contact_bday'] = $date->toString('yyyy-MM-dd');
            } catch (Exception $e) {
                unset($_POST['contact_bday']);
            }
        }

        // unset if empty
        if(empty($_POST['contact_id'])) {
            unset($_POST['contact_id']);
        }

        $contact = new Addressbook_Model_Contact();
        try {
            $contact->setFromUserData($_POST);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $contact->getValidationErrors(),
                            'errorMessage'      => 'invalid data for some fields');

            return $result;
        }

        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
         
        try {
            $backend->saveContact($contact);
            $result = array('success'           => true,
                            'welcomeMessage'    => 'Entry updated');
        } catch (Exception $e) {
            $result = array('success'           => false,
        					'errorMessage'      => $e->getMessage());
        }

        return $result;
         
    }

    /**
     * get data for overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param string $nodeid
     * @param string $_datatype
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getContactsByOwner($filter, $owner, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        if($rows = $backend->getContactsByOwner($owner, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = $backend->getCountByOwner($owner, $filter);
            }
        }

        return $result;
    }

    public function getAccounts($filter, $start, $sort, $dir, $limit)
    {
        $internalContainer = Egwbase_Container::getInstance()->getInternalContainer('addressbook');
        
        $addressbookId = $internalContainer->container_id;
        
        $result = $this->getContactsByAddressbookId($addressbookId, $filter, $start, $sort, $dir, $limit);

        return $result;
    }
    
    public function getContactsByAddressbookId($addressbookId, $filter, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        if($rows = $backend->getContactsByAddressbookId($addressbookId, $filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = $backend->getCountByAddressbookId($addressbookId, $filter);
            }
        }
        
        return $result;
    }

    public function getAddressbooksByOwner($owner)
    {
        $treeNodes = array();
        
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        if($rows = $backend->getAddressbooksByOwner($owner)) {
            foreach($rows as $addressbookData) {
                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'contacts', 'addressbook-' . $addressbookData->container_id, $addressbookData->container_name, TRUE);
                $childNode->addressbookId = $addressbookData->container_id;
                $childNode->nodeType = 'singleAddressbook';
                $treeNodes[] = $childNode;
            }
        }
        
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    

    public function getSharedAddressbooks()
    {
        $treeNodes = array();
        
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        if($rows = $backend->getSharedAddressbooks()) {
            foreach($rows as $addressbookData) {
                $childNode = new Egwbase_Ext_Treenode('Addressbook', 'contacts', 'shared-' . $addressbookData->container_id, $addressbookData->container_name, TRUE);
                $childNode->addressbookId = $addressbookData->container_id;
                $childNode->nodeType = 'singleAddressbook';
                $treeNodes[] = $childNode;
            }
        }
        
        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    
    
    /**
     * returns a list a accounts who gave current account at least read access to 1 personal addressbook 
     *
     */
    public function getOtherUsers()
    {
        $treeNodes = array();
        
        $rows = Addressbook_Controller::getInstance()->getOtherUsers();
        
        foreach($rows as $accountData) {
            $treeNode = new Egwbase_Ext_Treenode(
                'Addressbook',
                'contacts',
                'otheraddressbook_'. $accountData->accountId, 
                $accountData->accountDisplayName,
                false
            );
            $treeNode->owner  = $accountData->accountId;
            $treeNode->nodeType = 'userAddressbooks';
            $treeNodes[] = $treeNode;
        }

        echo Zend_Json::encode($treeNodes);

        // exit here, as the Zend_Server's processing is adding a result code, which breaks the result array
        exit;
    }    

    /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getAllContacts($filter, $start, $sort, $dir, $limit)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);

        if($rows = $backend->getAllContacts($filter, $sort, $dir, $limit, $start)) {
            $result['results']    = $rows->toArray();
            $result['totalcount'] = $backend->getCountOfAllContacts($filter);
        }

        return $result;
    }

    /**
     * get list of shared contacts
     *
     * @todo implement correct total count of shared contacts
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getSharedContacts($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        $rows = Addressbook_Controller::getInstance()->getSharedContacts($filter, $sort, $dir, $limit, $start);
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                //$result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfSharedContacts();
            }
        }

        return $result;
    }

    /**
     * get data for the overview
     *
     * returns the data to be displayed in a ExtJS grid
     *
     * @todo implement correc total count for lists
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @param string $options json encoded array of additional options
     * @return array
     */
    public function getOtherPeopleContacts($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $rows = $backend->getOtherPeopleContacts($filter, $sort, $dir, $limit, $start);
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            //$result['totalcount'] = $backend->getCountOfOtherPeopleContacts();
        }

        return $result;
    }
    
    public function getGrants($addressbookId)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $result['results'] = Addressbook_Controller::getInstance()->getGrants($addressbookId)->toArray();
        $result['totalcount'] = count($result['results']);
        
        foreach($result['results'] as $key => $value) {
            $result['results'][$key]["accountName"] = Egwbase_Account::getInstance()->getAccountById($value['accountId'])->toArray();
        }
        
        return $result;
    }
    
    public function setGrants($addressbookId, $grants)
    {
        $newGrants = new Egwbase_Record_RecordSet(Zend_Json::decode($grants), 'Egwbase_Record_Grants');
        
        $result = Addressbook_Controller::getInstance()->setGrants($addressbookId, $newGrants);
               
        return $result;
    }
}