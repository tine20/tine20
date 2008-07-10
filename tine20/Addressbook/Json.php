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

    /**
     * returns contact prepared for json transport
     *
     * @param Addressbook_Model_Contact $_contact
     * @return array contact data
     */
    protected function _contactToJson($_contact)
    {
        $_contact->tags = $_contact->tags->toArray();
        $result = $_contact->toArray();
        $result['owner'] = Tinebase_Container::getInstance()->getContainerById($_contact->owner)->toArray();
        $result['owner']['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(Zend_Registry::get('currentAccount'), $_contact->owner)->toArray();
        $result['jpegphoto'] = $this->getImageLink($_contact);
        
        return $result;
    }
    
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
        $contact = $this->getContact($contact->getId());
        $result = array('success'           => true,
                        'welcomeMessage'    => 'Entry updated',
                        'updatedData'       => $contact['contact']
        );         
        
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
     * @param  int    $start
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
        
        if ($rows = Addressbook_Controller::getInstance()->getContactsByOwner($owner, $filter, $pagination)) {
            $result['results']    = $rows->toArray();
            if ($start == 0 && count($result['results']) < $limit) {
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
        $result['contact'] = $this->_contactToJson($contact);
        
        return $result;
    }

    /**
     * returns list of accounts
     *
     * @param  string $query
     * @param  int    $sort
     * @param  string $dir
     * @param  int    $limit
     * @param  int    $start
     * @param  string $tagFilter
     * @return array
     */
    public function getUsers($query, $sort, $dir, $limit, $start, $tagFilter)
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
     * @param  int    $start
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
        
        if ($rows = Addressbook_Controller::getInstance()->getContactsByAddressbookId($addressbookId, $filter, $pagination)) {
            $result['results']    = $rows->toArray();
            if ($start == 0 && count($result['results']) < $limit) {
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
     * @param  int    $start
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
        
        if ($rows !== false) {
            $result['results']    = $rows->toArray();
            if ($start == 0 && count($result['results']) < $limit) {
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
     * @param  int    $start
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
        
        if ($rows !== false) {
            $result['results']    = $rows->toArray();
            if ($start == 0 && count($result['results']) < $limit) {
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
     * @param  int    $start
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
        
        if ($rows !== false) {
            $result['results']    = $rows->toArray();
            if ($start == 0 && count($result['results']) < $limit) {
                $result['totalcount'] = count($result['results']);
            } else {
                $result['totalcount'] = Addressbook_Controller::getInstance()->getCountOfOtherPeopleContacts($filter);
            }
        }

        return $result;
    }
    
    /**
     * returns a image link
     * 
     * @param  Addressbook_Model_Contact|array
     * @return string
     */
    protected function getImageLink($contact)
    {
        if (!empty($contact->jpegphoto)) {
            $link =  'index.php?method=Tinebase.getImage&application=Addressbook&location=&id=' . $contact['id'] . '&width=90&height=90&$ratiomode=0';
        } else {
            $link = 'images/empty_photo.jpg';
        }
        return $link;
    }
}