<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */

/**
 * controller for Addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Controller implements Egwbase_Events_Interface
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Adressbook_Controller
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Adressbook_Controller
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Addressbook_Controller;
        }
        
        return self::$instance;
    }
    
    public function getGrants($_addressbookId)
    {
        $addressbookId = (int)$_addressbookId;
        if($addressbookId != $_addressbookId) {
            throw new InvalidArgumentException('$_addressbookId must be integer');
        }
        
        $result = Egwbase_Container_Container::getInstance()->getAllGrants($addressbookId);
                
        return $result;
    }
    
    public function setGrants($_addressbookId, Egwbase_Record_RecordSet $_grants)
    {
        $addressbookId = (int)$_addressbookId;
        if($addressbookId != $_addressbookId) {
            throw new InvalidArgumentException('$_addressbookId must be integer');
        }
        
        $result = Egwbase_Container_Container::getInstance()->setAllGrants($addressbookId, $_grants);
                
        return $result;
    }
    
    public function getOtherUsers() 
    {
        $result = Egwbase_Container_Container::getInstance()->getOtherUsers('addressbook');
        
        return $result;
    }
        
    /**
     * get list of shared contacts
     *
     * @param string $filter
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return Zend_Db_Table_Rowset
     */
    public function getSharedContacts($_filter, $_sort, $_dir, $_limit = NULL, $_start = NULL) 
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $rows = $backend->getSharedContacts($_filter, $_sort, $_dir, $_limit, $_start);
        
        return $rows;
    }
    
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact
     */
    public function getContact($_contactId)
    {
        $backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        
        $result = $backend->getContactById($_contactId);
        
        return $result;
    }
    
    public function handleEvents(Egwbase_Events_Abstract $_eventObject)
    {
        error_log("handle event: " . get_class($_eventObject));
        switch(get_class($_eventObject)) {
            case 'Admin_Event_AddAccount':
                //$this->createUserFolder($_eventObject->account->accountId);
                break;
            case 'Admin_Event_DeleteAccount':
                //$this->deleteUserFolder($_eventObject->account->accountId);
                break;
        }
    }
    
    public function deleteUserFolder($_accountId)
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
/*        $addressbookId = Egwbase_Container::getInstance()->addContainer('addressbook', 'Personal Contacts', Egwbase_Container::TYPE_PERSONAL, Addressbook_Backend_Factory::SQL);
    
        $allGrants = array(
            Egwbase_Container::GRANT_ADD,
            Egwbase_Container::GRANT_ADMIN,
            Egwbase_Container::GRANT_DELETE,
            Egwbase_Container::GRANT_EDIT,
            Egwbase_Container::GRANT_READ
        );
        Egwbase_Container::getInstance()->addGrants($addressbookId, $accountId, $allGrants);
*/  
    }
    
    public function createUserFolder($_accountId)
    {
        $accountId = (int)$_accountId;
        if($accountId != $_accountId) {
            throw new InvalidArgumentException('$_accountId must be integer');
        }
        $addressbookId = Egwbase_Container::getInstance()->addContainer('addressbook', 'Personal Contacts', Egwbase_Container::TYPE_PERSONAL, Addressbook_Backend_Factory::SQL);
    
        $allGrants = array(
            Egwbase_Container::GRANT_ADD,
            Egwbase_Container::GRANT_ADMIN,
            Egwbase_Container::GRANT_DELETE,
            Egwbase_Container::GRANT_EDIT,
            Egwbase_Container::GRANT_READ
        );
        Egwbase_Container::getInstance()->addGrants($addressbookId, $accountId, $allGrants);
    
    }
}
