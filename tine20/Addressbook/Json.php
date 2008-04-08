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
 */
class Addressbook_Json extends Tinebase_Application_Json_Abstract
{
    protected $_appname = 'Addressbook';
    
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
        $contactData = Zend_Json::decode($contactData);
        
        // unset if empty
        if(empty($contactData['id'])) {
            unset($contactData['id']);
        }

        $contact = new Addressbook_Model_Contact();
        try {
            $contact->setFromArray($contactData);
        } catch (Exception $e) {
            // invalid data in some fields sent from client
            $result = array('success'           => false,
                            'errors'            => $contact->getValidationErrors(),
                            'errorMessage'      => 'invalid data for some fields');

            return $result;
        }

        if(empty($contact->id)) {
            $contact = Addressbook_Controller::getInstance()->addContact($contact);
        } else {
            $contact = Addressbook_Controller::getInstance()->updateContact($contact);
        }
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $contact->toArray());
        
        $result['updatedData']['owner'] = Tinebase_Container::getInstance()->getContainerById($contact->owner)->toArray();
        return $result;
         
    }

    /**
     * get contacts by owner
     *
     * @param string $filter
     * @param int $owner
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getContactsByOwner($filter, $owner, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );

        $pagination = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        if($rows = Addressbook_Controller::getInstance()->getContactsByOwner($owner, $filter, $pagination)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountByOwner($owner, $filter);
            }
        }

        return $result;
    }

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
        
        $result['contact'] = $contact->toArray();

        return $result;
    }

    /**
     * returns list of accounts
     *
     * @param string $filter
     * @param int $start
     * @param string $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getAccounts($filter, $sort, $dir, $limit, $start)
    {
        $internalContainer = Tinebase_Container::getInstance()->getInternalContainer(Zend_Registry::get('currentAccount'), 'Addressbook');
        
        $result = $this->getContactsByAddressbookId($internalContainer->getId(), $filter, $sort, $dir, $limit, $start);

        return $result;
    }
    
    public function getContactsByAddressbookId($addressbookId, $filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        if($rows = Addressbook_Controller::getInstance()->getContactsByAddressbookId($addressbookId, $filter, $pagination)) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountByAddressbookId($addressbookId, $filter);
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
    public function getAllContacts($filter, $sort, $dir, $limit, $start)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
                
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $rows = Addressbook_Controller::getInstance()->getAllContacts($filter, $pagination);
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfAllContacts();
            }
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

        $pagination = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $rows = Addressbook_Controller::getInstance()->getSharedContacts($filter, $pagination);
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfSharedContacts();
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
                
        $pagination = new Tinebase_Model_Pagination(array(
            'start' => $start,
            'limit' => $limit,
            'sort'  => $sort,
            'dir'   => $dir
        ));
        
        $rows = Addressbook_Controller::getInstance()->getOtherPeopleContacts($filter, $pagination);
        
        if($rows !== false) {
            $result['results']    = $rows->toArray();
            if($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfOtherPeopleContacts();
            }
        }

        return $result;
    }
    
}