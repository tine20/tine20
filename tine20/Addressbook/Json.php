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
        if (isset($contactData['tags'])) {
            $contactData['tags'] = Zend_Json::decode($contactData['tags']);
        }
        
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
        $contact = $this->getContact($contact->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $contact['contact']
        ); //$contact->toArray());
        
        //$result['updatedData']['owner'] = Tinebase_Container::getInstance()->getContainerById($contact->owner)->toArray();
        return $result;
         
    }

    /**
     * get contacts by owner
     *
     * @param  string $query
     * @param  int    $owner
     * @param  int    $sort
     * @param  string $dir
     * @param  int    $limit
     * @praam  int    $start
     * @param  string $tagFilter
     * @return array
     */
    public function getContactsByOwner($query, $owner, $sort, $dir, $limit, $start, $tagFilter)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Addressbook_Model_Filter(array(
            'query' => $query,
            'tag'   => $tagFilter
        ));
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
        
        $contact->tags = $contact->tags->toArray();
        $result['contact'] = $contact->toArray();
        $result['contact']['owner'] = Tinebase_Container::getInstance()->getContainerById($contact->owner)->toArray();
        return $result;
    }

    /**
     * returns list of accounts
     *
     * @param  string $query
     * @param  int    $sort
     * @param  string $dir
     * @param  int    $limit
     * @praam  int    $start
     * @param  string $tagFilter
     * @return array
     */
    public function getAccounts($query, $sort, $dir, $limit, $start, $tagFilter)
    {
        $internalContainer = Tinebase_Container::getInstance()->getInternalContainer(Zend_Registry::get('currentAccount'), 'Addressbook');
        
        $result = $this->getContactsByAddressbookId($internalContainer->getId(), $query, $sort, $dir, $limit, $start, $tagFilter);

        return $result;
    }
    
    /**
     * get all contacts for a given addressbookId (container)
     *
     * @param  int    $addressbookId
     * @param  string $query
     * @param  int    $sort
     * @param  string $dir
     * @param  int    $limit
     * @praam  int    $start
     * @param  string $tagFilter
     * @return array
     */
    public function getContactsByAddressbookId($addressbookId, $query, $sort, $dir, $limit, $start, $tagFilter)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Addressbook_Model_Filter(array(
            'query' => $query,
            'tag'   => $tagFilter
        ));
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
     * @param  string $query
     * @param  int    $sort
     * @param  string $dir
     * @param  int    $limit
     * @praam  int    $start
     * @param  string $tagFilter
     * @return array
     */
    public function getAllContacts($query, $sort, $dir, $limit, $start, $tagFilter)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Addressbook_Model_Filter(array(
            'query' => $query,
            'tag'   => $tagFilter
        ));
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
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfAllContacts($filter);
            }
        }

        return $result;
    }

    /**
     * get list of shared contacts
     *
     * @param  string $query
     * @param  int    $sort
     * @param  string $dir
     * @param  int    $limit
     * @praam  int    $start
     * @param  string $tagFilter
     * @return array
     */
    public function getSharedContacts($query, $sort, $dir, $limit, $start, $tagFilter)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Addressbook_Model_Filter(array(
            'query' => $query,
            'tag'   => $tagFilter
        ));
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
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfSharedContacts($filter);
            }
        }

        return $result;
    }

    /**
     * get data for the overview
     *
     * @param  string $query
     * @param  int    $sort
     * @param  string $dir
     * @param  int    $limit
     * @praam  int    $start
     * @param  string $tagFilter
     * @return array
     */
    public function getOtherPeopleContacts($query, $sort, $dir, $limit, $start, $tagFilter)
    {
        $result = array(
            'results'     => array(),
            'totalcount'  => 0
        );
        
        $filter = new Addressbook_Model_Filter(array(
            'query' => $query,
            'tag'   => $tagFilter
        ));
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
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfOtherPeopleContacts($filter);
            }
        }

        return $result;
    }
    
}