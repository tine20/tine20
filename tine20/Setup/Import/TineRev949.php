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
     * old table prefix
     * 
     * @var string
     */
    protected $oldTablePrefix = "sirona_";
    
    /**
     * new table prefix
     * 
     * @var string
     */
    protected $newTablePrefix = "sironanew_";
    
    /**
     * import main function
     *
     */
    public function import()
    {
        
        $this->importGroups();
        $this->importAccounts();
        $this->importGroupMembers();
        
        //@todo activate again
        //$this->importContainer();
        //$this->importAddressbook();
        
        //@todo make it work
        $this->importCrmLeads();
        
        //@todo write these functions (and add more?)
        
//        $this->importCrmProducts();
        
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
        $accountsTable = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.'accounts'));
        
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
        $groupsTable = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.'accounts'));
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
        $aclTable = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.'acl'));
        
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
        $table = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.''.$what));
        $mapping = array (  "8" => "2", 
                            "12" => "5", 
                            "13" => "4" );
        $where = array();        

        // delete old entries (contacts + container)      
        $newContactsTable = new Tinebase_Db_Table(array('name' => $this->newTablePrefix.'addressbook'));
        $newContactsTable->delete( "1" );        
        $newTable = new Tinebase_Db_Table(array('name' => $this->newTablePrefix.$what));
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
            $aclTable = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.'container_acl'));
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
        $contactsTable = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.'addressbook'));
        
        // get contacts
        $contacts = $contactsTable->fetchAll();        
        
        echo "Import Contacts from table sirona_addressbook ... ";
        foreach($contacts as $contact) {
            // old: contact_id  contact_tid     contact_owner   contact_private     cat_id  n_family    
            //  n_given     n_middle    n_prefix    n_suffix    n_fn    n_fileas    contact_bday    
            //  org_name    org_unit    contact_title   contact_role    contact_assistent   contact_room    
            //  adr_one_street  adr_one_street2     adr_one_locality    adr_one_region  adr_one_postalcode  
            //  adr_one_countryname     contact_label   adr_two_street  adr_two_street2     adr_two_locality    
            //  adr_two_region  adr_two_postalcode  adr_two_countryname     tel_work    tel_cell    tel_fax     
            //  tel_assistent   tel_car     tel_pager   tel_home    tel_fax_home    tel_cell_private    
            //  tel_other   tel_prefer  contact_email   contact_email_home  contact_url     contact_url_home    
            //  contact_freebusy_uri    contact_calendar_uri    contact_note    contact_tz  contact_geo     
            //  contact_pubkey  contact_created     contact_creator     contact_modified    contact_modifier    
            //  contact_jpegphoto   account_id
            
            // new: id  account_id adr_one_countryname     adr_one_locality    adr_one_postalcode  
            //  adr_one_region  adr_one_street  adr_one_street2     adr_two_countryname     
            //  adr_two_locality    adr_two_postalcode  adr_two_region  adr_two_street  adr_two_street2     
            //  cat_id  assistent   bday    calendar_uri    email   email_home  freebusy_uri    
            //  geo     jpegphoto   label   note    owner   private     pubkey  role    room    tid     
            //  title   tz  url     url_home    n_family    n_fileas    n_fn    n_given     n_middle    
            //  n_prefix    n_suffix    org_name    org_unit    tel_assistent   tel_car     tel_cell    
            //  tel_cell_private    tel_fax     tel_fax_home    tel_home    tel_other   tel_pager   
            //  tel_prefer  tel_work    created_by  creation_time   last_modified_by    last_modified_time  
            // is_deleted  deleted_by  deleted_time
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

                'last_modified_time'    => new Zend_Date ( $contact->contact_modified,Zend_Date::TIMESTAMP ),
                'assistent'             => $contact->contact_assistent,
                'bday'                  => $contact->contact_bday,
                'email'                 => $contact->contact_email,
                'email_home'            => $contact->contact_email_home,
                'note'                  => $contact->contact_note,
                'role'                  => $contact->contact_role,
                'title'                 => $contact->contact_title,
                'url'                   => $contact->contact_url,
                'url_home'              => $contact->contact_url_home,
                'n_given'               => $contact->n_given,
                'n_middle'              => $contact->n_middle,
                'n_prefix'              => $contact->n_prefix,
                'n_suffix'              => $contact->n_suffix,
                'org_name'              => $contact->org_name,
                'org_unit'              => $contact->org_unit,
                'tel_assistent'         => $contact->tel_assistent,
                'tel_car'               => $contact->tel_car,
                'tel_cell'              => $contact->tel_cell,
                'tel_cell_private'      => $contact->tel_cell_private,
                'tel_fax'               => $contact->tel_fax,
                'tel_fax_home'          => $contact->tel_fax_home,
                'tel_home'              => $contact->tel_home,
                'tel_pager'             => $contact->tel_pager,
                'tel_work'              => $contact->tel_work,     
            
                // no longer used?
                // @todo    add these fields to the model?
                'cat_id'                => $contact->cat_id,
                'geo'                   => $contact->contact_geo,
                'label'                 => $contact->contact_label,
                'private'               => $contact->contact_private,
                'pubkey'                => $contact->contact_pubkey,
                'room'                  => $contact->contact_room,
                'tid'                   => $contact->contact_tid,
                'tz'                    => $contact->contact_tz,
                'tel_prefer'            => $contact->tel_prefer,
                'created_by'            => $contact->contact_creator,
                'creation_time'         => new Zend_Date ( $contact->contact_created,Zend_Date::TIMESTAMP ),
                'last_modified_by'      => $contact->contact_modifier,
            
                //'calendar_uri'          => $contact->calendar_uri,
                //'freebusy_uri'          => $contact->freebusy_uri,
            
                //jpegphoto ?
                //deleted fields ?
                ) 
            );
            
            Addressbook_Backend_Sql::getInstance()->addContact($tineContact);
            
        }
        echo "done! got ".sizeof($contacts)." contacts.<br>";
        
    }    
    
    /**
     * import the leads (+ states/sources/types) from revision 949
     *
     */
    protected function importCrmLeads()
    {
        
        // delete old entries (leadsource + leadstate + leadtype) and import the new stuff
        $leadTableDataArray = array ( 
            array (
                'name'      => 'metacrm_leadsource',
                'fields'    => array ( 'lead_leadsource_id' => 'id', 'lead_leadsource' => 'leadsource' ),
                'model'     => 'Crm_Model_Leadsource'
            ), 
            array (
                'name' => 'metacrm_leadstate',
                'fields'    => array ( 
                    'lead_leadstate_id' => 'id', 
                    'lead_leadstate' => 'leadstate',
                    'lead_leadstate_probability' => 'probability',
                    'lead_leadstate_endslead' => 'endslead',
                ),
                'model'     => 'Crm_Model_Leadstate'
            ), 
            array (
                'name' => 'metacrm_leadtype',
                'fields'    => array ( 'lead_leadtype_id' => 'id', 'lead_leadtype' => 'leadtype' ),
                'model'     => 'Crm_Model_Leadtype'
            )
        );
              
        // get crm controller
        $crmController = Crm_Controller::getInstance();
        
        foreach ( $leadTableDataArray as $leadTableData ) {
            echo "Import from table ".$this->oldTablePrefix.''.$leadTableData['name']." ... ";
            
            $leadTable = new Tinebase_Db_Table(array('name' => $this->newTablePrefix.$leadTableData['name']));
            $leadTable->delete( "1" );                    
            
            // get data
            $table = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.''.$leadTableData['name']));
            $rows = $table->fetchAll($where);

            $dataArray = array ();
            foreach ( $rows as $row ) {
                
                // add to array
                $values = array ();
                foreach ( $leadTableData['fields'] as $oldKey => $newKey ) {
                    $values[$newKey] = $row->$oldKey;                    
                }
                
                $dataArray[] = $values;
            }
            
            // save in the new tables
            $records = new Tinebase_Record_RecordSet($leadTableData['model'], $dataArray);
            
            switch ( $leadTableData['name'] ) {
                case 'metacrm_leadsource':
                    $crmController->saveLeadsources($records);
                    break;
                case 'metacrm_leadstate':
                    $crmController->saveLeadstates($records);
                    break;
                case 'metacrm_leadtype':
                    $crmController->saveLeadtypes($records);
                    break;
            }
            
            echo "done! got ".sizeof($rows)." rows.<br>";
        }
        
        //@todo import leads
        
        /*$what = "metacrm_lead";
        $table = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.''.$what));
        $where = array();        
        
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
            $aclTable = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.'container_acl'));
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
        */
    }
}
