<?php
/**
 * controller for Addressbook
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
class Addressbook_Controller
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
        
        $result = Egwbase_Container::getInstance()->getAllGrants($addressbookId);
                
        return $result;
    }
    
    public function setGrants($_addressbookId, Egwbase_Record_RecordSet $_grants)
    {
        $addressbookId = (int)$_addressbookId;
        if($addressbookId != $_addressbookId) {
            throw new InvalidArgumentException('$_addressbookId must be integer');
        }
        
        $result = Egwbase_Container::getInstance()->setAllGrants($addressbookId, $_grants);
                
        return $result;
    }
    
    public function getOtherUsers() 
    {
        $rows = Egwbase_Container::getInstance()->getOtherUsers('addressbook');

        $accountData = array();

        $result = new Egwbase_Record_RecordSet(array(), 'Egwbase_Record_PublicAccountProperties');
        
        foreach($rows as $account) {
            $result->addRecord($account->getPublicProperties());
        }

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
}
