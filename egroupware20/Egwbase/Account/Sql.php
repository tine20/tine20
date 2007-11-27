<?php
/**
 * sql implementation of the eGW SQL accounts interface
 * 
 * @package     Egwbase
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

class Egwbase_Account_Sql implements Egwbase_Account_Interface
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
     * @var Egwbase_Account_Sql
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Account_Sql
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Account_Sql;
        }
        
        return self::$instance;
    }

    /**
     * return the group ids a account is member of
     *
     * @param int $accountId the accountid of a account
     * @todo the group info do not belong into the ACL table, there should be a separate group table
     * @return array list of group ids
     */
    public function getAccountGroupMemberships($accountId)
    {
        $aclTable = new Egwbase_Acl_Sql();
        $memberShips = array();
        
        $where = array(
            "acl_appname = 'phpgw_group'",
            $aclTable->getAdapter()->quoteInto('acl_account = ?', $accountId)
        );
        
        $rowSet = $aclTable->fetchAll($where);
        
        foreach($rowSet as $row) {
            //$memberShips[$row->acl_location] = 'Group '.$row->acl_location;
            $memberShips[] = $row->acl_location;
        }
        
        return $memberShips;
    }
    
    /**
     * return the list of group members
     *
     * @param int $groupId
     * @todo the group info do not belong into the ACL table, there should be a separate group table
     * @return array list of group members
     */
    public function getGroupMembers($groupId)
    {
        $aclTable = new Egwbase_Acl_Sql();
        $members = array();
        
        $where = array(
            "acl_appname = 'phpgw_group'",
            $aclTable->getAdapter()->quoteInto('acl_location = ?', $groupId)
        );
        
        $rowSet = $aclTable->fetchAll($where);
        
        foreach($rowSet as $row) {
            $members[] = $row->acl_account;
        }
        
        return $members;
    }
    
    public function getAccounts($_filter, $_sort, $_dir, $_start = NULL, $_limit = NULL)
    {
        //$right = (int)$_right;
        //if($right != $_right) {
        //    throw new InvalidArgumentException('$_right must be integer');
        //}
        //$accountId   = Zend_Registry::get('currentAccount')->account_id;
        //$application = Egwbase_Application::getInstance()->getApplicationByName($_application);
               
        $db = Zend_Registry::get('dbAdapter');
        
        $select = $db->select()
            ->from('egw_accounts')
            ->join(
                'egw_addressbook',
                'egw_accounts.account_id = egw_addressbook.account_id'
            )
            ->limit($_start, $_limit)
            ->order($_sort . ' ' . $_dir);

        //error_log("getContainer:: " . $select->__toString());

        $stmt = $db->query($select);

        $result = $stmt->fetchAll(Zend_Db::FETCH_ASSOC);
        //$result = new Egwbase_Record_RecordSet($stmt->fetchAll(Zend_Db::FETCH_ASSOC), 'Egwbase_Record_Container');
        
        return $result;
    }
}