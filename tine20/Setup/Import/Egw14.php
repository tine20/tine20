<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 *
 */

/**
 * class to handle setup of Tine 2.0
 * 
 * @package     Setup
 */
class Setup_Import_Egw14
{
    public function import()
    {
        $this->importGroups();
        $this->importAccounts();
        $this->importGroupMembers();
    }
    
    /**
     * import the accounts from eGroupWare 1.4
     *
     */
    protected function importAccounts()
    {
        $accountsTable = new Tinebase_Db_Table(array('name' => 'egw_accounts'));
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('account_type = ?', 'u')
        );
        
        $accounts = $accountsTable->fetchAll($where);
        
        foreach($accounts as $account) {
            $tineAccount = new Tinebase_Account_Model_FullAccount(array(
                'accountId'                 => $account->account_id,
                'accountLoginName'          => $account->account_lid,
                'accountLastLogin'          => $account->account_lastlogin > 0 ? new Zend_Date($account->account_lastlogin, Zend_Date::TIMESTAMP) : NULL,
                'accountLastLoginfrom'      => $account->account_lastloginfrom,
                'accountLastPasswordChange' => $account->account_lastpwd_change > 0 ? new Zend_Date($account->account_lastpwd_change, Zend_Date::TIMESTAMP) : NULL,
                'accountStatus'             => $account->account_status == 'A' ? 'enabled' : 'disabled',
                'accountExpires'            => $account->account_expires > 0 ? new Zend_Date($account->account_expires, Zend_Date::TIMESTAMP) : NULL,
                'accountPrimaryGroup'       => abs($account->account_primary_group),
                'accountDisplayName'        => 'Displayname',
                'accountLastName'           => 'Lastname',
                'accountFirstName'          => 'Firstname',
                'accountFullName'           => 'Fullname',
                'accountEmailAddress'       => 'Emailaddress'
            ));
            
            Tinebase_Account_Sql::getInstance()->addAccount($tineAccount);
        }
        
    }

    /**
     * import the groups from eGroupWare 1.4
     *
     */
    protected function importGroups()
    {
        $groupsTable = new Tinebase_Db_Table(array('name' => 'egw_accounts'));
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('account_type = ?', 'g')
        );
        
        $groups = $groupsTable->fetchAll($where);
        
        foreach($groups as $group) {
            $tineGroup = new Tinebase_Group_Model_Group(array(
                'id'            => $group->account_id,
                'name'          => $group->account_lid,
                'description'   => 'imported from eGroupWare 1.4'
            ));
            
            Tinebase_Group_Sql::getInstance()->addGroup($tineGroup);
        }
        
    }

    /**
     * import the group members from eGroupWare 1.4
     *
     */
    protected function importGroupMembers()
    {
        $aclTable = new Tinebase_Db_Table(array('name' => 'egw_acl'));
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('acl_appname = ?', 'phpgw_group')
        );
        
        $groupMembers = $aclTable->fetchAll($where);
        
        foreach($groupMembers as $member) {
            Tinebase_Group_Sql::getInstance()->addGroupMember(abs($member->acl_location), $member->acl_account);
        }
        
    }
}
