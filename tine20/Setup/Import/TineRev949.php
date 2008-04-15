<?php
/**
 * Tine 2.0
 * class for migration from tine 2.0 revision 949
 * 
 * the database setup is very similar to egw14 -> perhaps we can use these functions for egw14 import
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
     * is needed for container grants
     * 
     * @var groupAccountArray
     */
    protected $groupAccountArray = array();

    /**
     * is needed for container acl
     * acl -> uid mapping
     * 
     * @var groupArray
     */
    protected $grantAclIdToUid = array();
    
    
    /**
     * import main function
     *
     */
    public function import()
    {
        
        $this->importGroups();
        $this->importAccounts();
        $this->importGroupMembers();
        $this->importContainer();
        
        //@todo make it work
        //$this->importAddressbook();
        
        //@todo write these functions (and add more?)
        
//        $this->importCrm();
//        $this->importAcl();
//        $this->importXXX();        
        
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
            
            // add group for container import
            $this->groupAccountArray[] = $group->account_id;
            
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
        
        echo "Import Groupmembers from table sirona_acl ... ";
        foreach($groupMembers as $member) {
            Tinebase_Group_Sql::getInstance()->addGroupMember(abs($member->acl_location), $member->acl_account);
        }
        echo "done! got ".sizeof($groupMembers)." group memberships.<br>";
        
    }
    
    /**
     * import the containers (+acl) from revision 949
     *
     */
    protected function importContainer()
    {
        
        $what = "container";
        $table = new Tinebase_Db_Table(array('name' => 'sirona_'.$what));
        $mapping = array (  "8" => "2", 
                            "12" => "5", 
                            "13" => "4" );
        $where = array();        

        // delete old entries (contacts + container)      
        $newContactsTable = new Tinebase_Db_Table(array('name' => 'sironanew_addressbook'));
        $newContactsTable->delete( "1" );        
        $newTable = new Tinebase_Db_Table(array('name' => 'sironanew_'.$what));
        $newTable->delete( "1" );
        
        $rows = $table->fetchAll($where);
        
        echo "Import $what  ... ";
        foreach($rows as $row) {
            // old: container_id    container_name  container_type  container_backend   application_id
            // new: id   name    type    backend     application_id
            $tineModel = new Tinebase_Model_Container(array(
                'id'                => $row->container_id,
                'name'              => $row->container_name,
                'type'              => $row->container_type,
                'backend'           => strtolower($row->container_backend),
                'application_id'    => ( isset($mapping[$row->application_id]) ) ? $mapping[$row->application_id] : $row->application_id,
            ));
            
            // get grants
            $grantsArray = array();
            $aclTable = new Tinebase_Db_Table(array('name' => 'sirona_container_acl'));
            $grants = $aclTable->fetchAll( array(Zend_Registry::get('dbAdapter')->quoteInto('container_id = ?', $row->container_id)) );
            foreach ( $grants as $grant ) {
                             
                $type = 'user';
                $accountId = $grant->account_id;
                
                if ( empty($accountId) or $accountId === NULL ) {
                    $type = 'anyone';
                    $accountId = 0;
                }
                if ( in_array($grant->account_id, $this->groupAccountArray) ) {
                    $type = 'group';
                }
                
                $grantsArray[] = array ( 'type' => $type, 'accountId' => $accountId, 'grant' => $grant->account_grant );
                
            }
            Tinebase_Container::getInstance()->addContainer($tineModel, new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array()), TRUE);

            foreach ( $grantsArray as $grantData ) {
                Tinebase_Container::getInstance()->addGrants(
                    $row->container_id, 
                    $grantData['type'],
                    $grantData['accountId'], 
                    array($grantData['grant']),
                    TRUE
                );
            }
            
        }
        echo "done! got ".sizeof($rows)." $what(s).<br>";
        
    }
    
    /**
     * import the addressbook from revision 949
     *
     */
    protected function importAddressbook()
    {
        $contactsTable = new Tinebase_Db_Table(array('name' => 'sirona_addressbook'));
        
        // get contacts
        $contacts = $contactsTable->fetchAll();        
        
        echo "Import Contacts from table sirona_addressbook ... ";
        foreach($contacts as $contact) {
            
            $tineContact = new Addressbook_Model_Contact ( array(
                
                'id'                    => $contact->contact_id,
                'account_id'            => $contact->account_id,
                'owner'                 => $contact->contact_owner,

                'n_family'              => $contact->n_family,
                'n_fileas'              => $contact->n_fileas,
                'n_fn'                  => $contact->n_fn,
            
                'adr_one_countryname'   => $contact->adr_one_countryname,
                'adr_one_locality'      => $contact->adr_one_locality,
                'adr_one_postalcode'    => $contact->adr_one_postalcode,
                'adr_one_region'        => $contact->adr_one_region,
                'adr_one_street'        => $contact->adr_one_street,
                'adr_one_street2'       => $contact->adr_one_street2,
                'adr_two_countryname'   => $contact->adr_two_countryname,
                'adr_two_locality'      => $contact->adr_two_locality,
                'adr_two_postalcode'    => $contact->adr_two_postalcode,
                'adr_two_region'        => $contact->adr_two_region,
                'adr_two_street'        => $contact->adr_two_street,
                'adr_two_street2'       => $contact->adr_two_street2,

            /* @todo add more fields */
            
/*
                'modified'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'assistent'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'bday'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'email'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'email_home'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'note'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'role'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'title'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'url'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'url_home'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'n_given'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'n_middle'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'n_prefix'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'n_suffix'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'org_name'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'org_unit'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_assistent'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_car'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_cell'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_cell_private'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_fax'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_fax_home'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_home'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_pager'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
                'tel_work'              => array(Zend_Filter_Input::ALLOW_EMPTY => true)
            */            
            
                ) 
            );
            
            Addressbook_Backend_Sql::getInstance()->addContact($tineContact);
            
        }
        echo "done! got ".sizeof($contacts)." contacts.<br>";
        
    }    
}
