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
     * @var array groupAccountArray
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
     * mapping of application ids
     * 
     * @var array
     */
    protected $applicationIdMapping = array (  "8" => "2", 
                                                "12" => "5", 
                                                "13" => "4" );    
    
    /**
     * mapping of application rights
     * 
     * @var array
     */
    protected $applicationRightsMapping = array ( 1 => 'run', 2 => 'admin' );
        
    /**
     * mapping of task status ids
     * 
     * @var array
     */
    protected $statusIdMapping = array ( 2 => 1, 4 => 2, 6 => 3, 8 => 4 );

    /**
     * is needed for task links (old id => uid)
     * 
     * @var array
     */
    protected $taskIds = array();

    
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
        $this->importAddressbook();
        $this->importCrm();
        $this->importApplicationRights();                
        $this->importTasks();        
        $this->importLinks();
                
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
                'application_id'    => ( isset($this->applicationIdMapping[$row->application_id]) ) ? $this->applicationIdMapping[$row->application_id] : $row->application_id,
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
     * import the leads (+ states/sources/types/products) from revision 949
     *
     */
    protected function importCrm()
    {
        
        // delete old entries (leadsource + leadstate + leadtype) and import the new stuff
        $leadTableDataArray = array ( 
            array (
                'name'      => 'metacrm_leadsource',
                'fields'    => array ( 'lead_leadsource_id' => 'id', 'lead_leadsource' => 'leadsource' ),
                'model'     => 'Crm_Model_Leadsource',
                'delete'    => TRUE,
                'where'     => array(),
            ), 
            array (
                'name' => 'metacrm_leadstate',
                'fields'    => array ( 
                    'lead_leadstate_id' => 'id', 
                    'lead_leadstate' => 'leadstate',
                    'lead_leadstate_probability' => 'probability',
                    'lead_leadstate_endslead' => 'endslead',
                ),
                'model'     => 'Crm_Model_Leadstate',
                'delete'    => TRUE,
                'where'     => array(),
                ), 
            array (
                'name' => 'metacrm_leadtype',
                'fields'    => array ( 'lead_leadtype_id' => 'id', 'lead_leadtype' => 'leadtype' ),
                'model'     => 'Crm_Model_Leadtype',
                'delete'    => TRUE,
                'where'     => array(),
            ),
            array (
                'name' => 'metacrm_lead',
                'fields'    => array ( 
                    'lead_id'               => 'id', 
                    'lead_name'             => 'lead_name',
                    'lead_leadstate_id'     => 'leadstate_id', 
                    'lead_leadtype_id'      => 'leadtype_id',            
                    'lead_leadsource_id'    => 'leadsource_id',
                    'lead_container'        => 'container',
                    'lead_modifier'         => 'modifier',
                    'lead_start'            => 'start',
                    'lead_modified'         => 'modified',
                    'lead_created'          => 'created',
                    'lead_description'      => 'description',
                    'lead_end'              => 'end',
                    'lead_turnover'         => 'turnover',
                    'lead_probability'      => 'probability',
                    'lead_end_scheduled'    => 'end_scheduled',
                    'lead_lastread'         => 'lastread',
                ),
                'model'     => 'Crm_Model_Lead',
                'delete'    => FALSE,
                'where'     => array(),
            ),
            array (
                'name' => 'metacrm_product',
                'fields'    => array ( 
                    'lead_id' => 'id', 
                    'lead_lead_id' => 'lead_id',
                    'lead_product_id' => 'product_id',
                    'lead_product_desc' => 'product_desc',
                    'lead_product_price' => 'product_price',
                ),
                'model'     => 'Crm_Model_Product',
                'delete'    => TRUE,
                'where'     => array( 'lead_product_id > 0' ),
            ),
            array (
                'name' => 'metacrm_productsource',
                'fields'    => array ( 
                    'lead_productsource_id' => 'id', 
                    'lead_productsource' => 'productsource',
                    'lead_productsource_price' => 'price',
                ),
                'model'     => 'Crm_Model_Productsource',
                'delete'    => TRUE,
                'where'     => array(),
            ),
            );
              
        // get crm backend
        $crmBackend = new Crm_Backend_Sql();
        
        foreach ( $leadTableDataArray as $leadTableData ) {
            echo "Import from table ".$this->oldTablePrefix.''.$leadTableData['name']." ... ";
            
            if ( $leadTableData['delete'] ) {
                $leadTable = new Tinebase_Db_Table(array('name' => $this->newTablePrefix.$leadTableData['name']));
                $leadTable->delete( "1" );                    
            }
            
            // get data
            $table = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.''.$leadTableData['name']));
            $rows = $table->fetchAll($leadTableData['where']);

            $dataArray = array ();
            foreach ( $rows as $row ) {
                
                // add to array
                $values = array ();
                foreach ( $leadTableData['fields'] as $oldKey => $newKey ) {
                    $values[$newKey] = $row->$oldKey;                    
                }
                
                if ( $leadTableData['name'] === 'metacrm_lead' ) {
                    
                    // @todo add modified   created lastreader modifier lastread ??
                    // -> use Zend_Date
                    
                    $lead = new Crm_Model_Lead ( $values );
                    try {
                        $crmBackend->addLead($lead);
                    } catch ( UnderflowException $e ) {
                        echo "error: " . $e->getMessage() . "<br/>";
                    }
                } else {
                    $dataArray[] = $values;
                }
            }
            
            if ( !empty($dataArray) ) {
                
                $records = new Tinebase_Record_RecordSet($leadTableData['model'], $dataArray);
                
                // save in the new tables            
                switch ( $leadTableData['name'] ) {
                    case 'metacrm_leadsource':
                        $crmBackend->saveLeadsources($records);
                        break;
                    case 'metacrm_leadstate':
                        $crmBackend->saveLeadstates($records);
                        break;
                    case 'metacrm_leadtype':                        
                        $crmBackend->saveLeadtypes($records);
                        break;
                    case 'metacrm_product':
                        $crmBackend->saveProducts($records);
                        break;
                    case 'metacrm_productsource':
                        $crmBackend->saveProductsource($records);
                        break;                        
                }
            }
            
            echo "done! got ".sizeof($rows)." rows.<br>";
        }        

    }
    
    /**
     * import the application rights
     *
     */
    protected function importApplicationRights()
    {
        
        // delete old entries and import the new stuff
        $tableDataArray = array ( 
            array (
                'name'      => 'application_rights',
                'fields'    => array ( 
                    'acl_id'            => 'id', 
                    'application_id'    => 'application_id',
                    'application_right' => 'right',
                    'account_id'        => 'account_id',
                ),
                'model'     => 'Tinebase_Model_AccessLog',
                'delete'    => TRUE,
                'where'     => array(Zend_Registry::get('dbAdapter')->quoteInto('application_id IN (?)', array_keys($this->applicationIdMapping))),
            ), 
        );
              
        // get backend/controller
        $backend = Tinebase_Acl_Rights::getInstance();
        
        foreach ( $tableDataArray as $tableData ) {
            echo "Import from table ".$this->oldTablePrefix.''.$tableData['name']." ... ";
            
            if ( $tableData['delete'] ) {
                $deleteTable = new Tinebase_Db_Table(array('name' => $this->newTablePrefix.$tableData['name']));
                $deleteTable->delete( "1" );                    
            }
            
            // get data
            $table = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.''.$tableData['name']));
            $rows = $table->fetchAll($tableData['where']);

            $dataArray = array ();
            foreach ( $rows as $row ) {
                
                // add to array
                $values = array ();
                foreach ( $tableData['fields'] as $oldKey => $newKey ) {
                    if ( $tableData['name'] === 'application_rights' ) {
                        if ( $oldKey === 'account_id' ) {
                            if ( empty($row->$oldKey) ) {
                                $values['account_type'] = 'anyone';
                                $values['account_id'] = 0;
                                continue;
                            } elseif ( in_array($row->$oldKey, $this->groupAccountArray) ) {
                                $values['account_type'] = 'group'; 
                            } else {
                                $values['account_type'] = 'account';
                            }
                        } elseif ( $oldKey === 'application_id' ) {
                            $values[$newKey] = $this->applicationIdMapping[$row->$oldKey];
                            continue;
                        } elseif ( $oldKey === 'application_right' ) {
                            $values[$newKey] = $this->applicationRightsMapping[$row->$oldKey];
                            continue;
                        }
                    } 

                    $values[$newKey] = $row->$oldKey;
                                        
                }
                
                if ( $tableData['name'] === 'application_rights' ) {
                    $right = new Tinebase_Acl_Model_Right ( $values );
                    try {
                        $backend->addRight($right);
                    } catch ( Exception $e ) {
                        echo "error: " . $e->getMessage() . "<br/>" . print_r ( $values, true ) . "<br/>";
                    }
                } elseif ( $tableData['name'] === 'access_log' ) {
                } else {
                    $dataArray[] = $values;
                }
            }
            
            echo "done! got ".sizeof($rows)." rows.<br>";
        }        

    }    
    
    /**
     * import tasks
     *
     */
    protected function importTasks()
    {
        
        // delete old entries and import the new stuff
        $tableDataArray = array ( 
            array (
                'name'      => 'tasks',
                'fields'    => array ( 
                    //'identifier'            => 'id', 
                    'container'             => 'container_id',
                    'created_by'            => 'created_by',
                    'last_modified_by'      => 'last_modified_by',
                    'last_modified_time'    => 'last_modified_time',
                    'is_deleted'            => 'is_deleted',
                    'deleted_time'          => 'deleted_time',
                    'deleted_by'            => 'deleted_by',
                    'percent'               => 'percent',
                    'completed'             => 'completed',
                    'due'                   => 'due',
                    'class'                 => 'class_id',
                    'description'           => 'description',
                    'geo'                   => 'geo',
                    'location'              => 'location',
                    'organizer'             => 'organizer',
                    'priority'              => 'priority',
                    'summaray'              => 'summary',
                    'status'                => 'status_id',
                    'url'                   => 'url',
            ),
                'model'     => 'Tasks_Model_Task',
                'delete'    => TRUE,
                'where'     => array( "is_deleted = 0"),
            ), 
        );
              
        // get backend/controller
        $backend = new Tasks_Backend_Sql();
        
        foreach ( $tableDataArray as $tableData ) {
            echo "Import from table ".$this->oldTablePrefix.''.$tableData['name']." ... ";
            
            if ( $tableData['delete'] ) {
                $deleteTable = new Tinebase_Db_Table(array('name' => $this->newTablePrefix.$tableData['name']));
                $deleteTable->delete( "1" );                    
            }
            
            // get data
            $table = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.''.$tableData['name']));
            $rows = $table->fetchAll($tableData['where']);

            $dataArray = array ();
            foreach ( $rows as $row ) {
                
                // add to array
                $values = array ();
                foreach ( $tableData['fields'] as $oldKey => $newKey ) {
                    if ( $tableData['name'] === 'tasks' ) {
                        if ( $oldKey === 'status' ) {
                            $values[$newKey] = $this->statusIdMapping[$row->$oldKey];
                            continue;
                        }
                    }

                    $values[$newKey] = $row->$oldKey;                                        
                }
                
                if ( $tableData['name'] === 'tasks' ) {
                    $task = new Tasks_Model_Task ( $values );
                    //$backend->createTask($task);
                    try {
                        $task = $backend->createTask($task);
                    } catch ( Exception $e ) {
                        echo "error: " . $e->getMessage() . "<br/>" . print_r ( $values, true ) . "<br/>";
                    }
                    // add old task id => task uid mapping
                    $this->taskIds[$row->identifier] = $task->getId();
                
                } else {
                    $dataArray[] = $values;
                }
            }
            
            echo "done! got ".sizeof($rows)." rows.<br>";
        }        

    }   

    /**
     * import links
     *
     */
    protected function importLinks()
    {
        
        // delete old entries and import the new stuff
        $tableDataArray = array ( 
            array (
                'name'      => 'links',
                'fields'    => array (),
                'model'     => '',
                'delete'    => TRUE,
                'where'     => array(),
            ), 
        );
              
        // get backend/controller
        $backend = Tinebase_Links::getInstance();
        
        foreach ( $tableDataArray as $tableData ) {
            echo "Import from table ".$this->oldTablePrefix.''.$tableData['name']." ... ";
            
            if ( $tableData['delete'] ) {
                $deleteTable = new Tinebase_Db_Table(array('name' => $this->newTablePrefix.$tableData['name']));
                $deleteTable->delete( "1" );                    
            }
            
            // get data
            $table = new Tinebase_Db_Table(array('name' => $this->oldTablePrefix.''.$tableData['name']));
            $rows = $table->fetchAll($tableData['where']);

            $counter = sizeof($rows);
            foreach ( $rows as $row ) {
                if ( $row->link_app2 === strtolower('tasks') ) {
                    if ( isset($this->taskIds[$row->link_id2]) ) {                        
                        $id2 = $this->taskIds[$row->link_id2];
                    } else {
                        // skip deleted task links
                        $counter--;
                        continue;
                    }
                } else {
                    $id2 = $row->link_id2;
                }
                
                $backend->addLink(strtolower($row->link_app1), $row->link_id1, strtolower($row->link_app2), $id2, $row->link_remark);
                               
            }
            
            echo "done! got ".$counter." rows.<br>";
        }        

    }     
}
