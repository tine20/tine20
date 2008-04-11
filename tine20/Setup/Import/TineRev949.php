<?php
/**
 * Tine 2.0
 * class for migration from tine 2.0 revision 949
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 *
 */

/**
 * class to handle data migration
 * 
 * @package     Setup
 */
class Setup_Import_TineRev949
{
    
    /**
     * import main function
     *
     */
    public function import()
    {
        
        $this->importGroups();
        $this->importAccounts();
        $this->importGroupMembers();
        
        //@todo write these functions (and add more?)
        
//        $this->importAddressbook();
//        $this->importCrm();
//        $this->importAcl();
//        $this->importXXX();
        
        //@todo add container?
        
        //@todo delete old tables?
    }
    
    /**
     * import the accounts from revision 949
     *
     */
    protected function importAccounts()
    {
        $accountsTable = new Tinebase_Db_Table(array('name' => 'sirona_accounts'));
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('account_type = ?', 'u')
        );
        
        $accounts = $accountsTable->fetchAll($where);
        
        echo "Import Accounts from table sirona_accounts ... ";
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
            // and set password
            Tinebase_Auth::getInstance()->setPassword($account->account_lid, $account->account_pwd, $account->account_pwd, FALSE);
            
        }
        echo "done! got ".sizeof($accounts)." accounts.<br>";
        
    }

    /**
     * import the groups from revision 949
     *
     */
    protected function importGroups()
    {
        $groupsTable = new Tinebase_Db_Table(array('name' => 'sirona_accounts'));
        $groupMapping = array ( "Default" => "Users", "Admins" => "Administrators" );
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('account_type = ?', 'g')
        );
        
        $groups = $groupsTable->fetchAll($where);
        
        echo "Import Groups from table sirona_accounts ... ";
        foreach($groups as $group) {
            $tineGroup = new Tinebase_Group_Model_Group(array(
                'id'            => $group->account_id,
                'name'          => ( isset($groupMapping[$group->account_lid]) ) ? $groupMapping[$group->account_lid] : $group->account_lid,
                'description'   => 'imported'
            ));
            
            Tinebase_Group_Sql::getInstance()->addGroup($tineGroup);
        }
        echo "done! got ".sizeof($groups)." groups.<br>";
        
    }

    /**
     * import the group members from revision 949
     *
     */
    protected function importGroupMembers()
    {
        $aclTable = new Tinebase_Db_Table(array('name' => 'sirona_acl'));
        
        $where = array(
            Zend_Registry::get('dbAdapter')->quoteInto('acl_appname = ?', 'phpgw_group')
        );
        
        $groupMembers = $aclTable->fetchAll($where);
        
        echo "Import Groups from table sirona_accounts ... ";
        foreach($groupMembers as $member) {
            Tinebase_Group_Sql::getInstance()->addGroupMember(abs($member->acl_location), $member->acl_account);
        }
        echo "done! got ".sizeof($groupMembers)." group memberships.<br>";
        
    }
}
