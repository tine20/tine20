<?php
/**
 * Tine 2.0
 * 
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
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
    
    /**
     * old table prefix
     * 
     * @var string
     */
    protected $oldTablePrefix = "egw_";
        
    /**
     * the constructor 
     *
     */
    public function __construct()
    {
        // set import user current account
        // @todo make it work with ldap
        echo "adding import user<br/>";
        /*$account = new Tinebase_Account_Model_Account(array(
            'accountId' => 777,
            'accountDisplayName' => 'import user',
            'accountLastName' => 'import',
            'accountFullName' => 'import user',
        ));*/
        $account = Tinebase_Account::getInstance()->getFullAccountByLoginName('pschuele');
        Zend_Registry::set('currentAccount', $account);
    }
    
    /**
     * all imports 
     *
     */
    public function import()
    {
        $this->importGroups();
        $this->importAccounts();
        $this->importGroupMembers();
        $this->importAddressbook();
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
    
    /**
     * import the addressbook from egw14
     *
     * @param string $_oldTableName [OPTIONAL]
     * @param int $useOldId [OPTIONAL]
     */
    public function importAddressbook( $_oldTableName = NULL, $useOldId = TRUE )
    {
        $tableName = ( $_oldTableName != NULL ) ? $_oldTableName : $this->oldTablePrefix.'addressbook';
        $contactsTable = new Tinebase_Db_Table(array('name' => $tableName));
        
        // get contacts
        $contacts = $contactsTable->fetchAll();

        echo "Import Contacts from table ".$tableName." ... <br/>";
        
        foreach($contacts as $contact) {

            echo "importing " . $contact->n_given . " " . $contact->n_family . " ...";

            // add container
            if ( $contact->contact_owner > 0 ) {
                // personal container for owner
                try {
                    $container = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Personal Contacts', Tinebase_Container::TYPE_PERSONAL);
                } catch ( UnderflowException $e ) {
                    $container = new Tinebase_Model_Container(array(
                        'name' => 'Personal Contacts',
                        'type' => Tinebase_Container::TYPE_PERSONAL,      
                        'backend' => 'Sql',
                        'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),                  
                    ));                    
                }

                Tinebase_Container::getInstance()->addGrants($container, 'user', $contact->contact_owner, array(
                    Tinebase_Container::GRANT_ANY,
                ), TRUE);
                                
            } else if ( $contact->contact_owner == -15 ) {
                // default users group -> shared container
                $userGroup = Tinebase_Group::getInstance()->getGroupByName('Users');
                try {
                    $container = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Shared Contacts', Tinebase_Container::TYPE_SHARED);
                } catch ( UnderflowException $e ) {
                    $container = new Tinebase_Model_Container(array(
                        'name' => 'Shared Contacts',
                        'type' => Tinebase_Container::TYPE_SHARED,      
                        'backend' => 'Sql',
                        'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),                  
                    ));
                    $container = Tinebase_Container::getInstance()->addContainer($container, NULL, TRUE);
                    Tinebase_Container::getInstance()->addGrants($container, 'group', $userGroup, array(
                        Tinebase_Container::GRANT_READ,
                        Tinebase_Container::GRANT_ADD,
                        Tinebase_Container::GRANT_EDIT,
                        Tinebase_Container::GRANT_DELETE,
                    ), TRUE);
                }                
            } else {
                echo "skipped.<br/>";
                continue;
            }                   
            $containerId = $container->getId();         
            
            // create contact record
            $tineContact = new Addressbook_Model_Contact ( array(
                
                'id'                    => ( $useOldId ) ? $contact->contact_id : 0,
                'account_id'            => $contact->account_id,                        
                'owner'                 => $containerId,

                'n_family'              => ( empty($contact->n_family) ) ? 'imported' : $contact->n_family,
                'n_fileas'              => ( empty($contact->n_fileas) ) ? 'imported' : $contact->n_fileas,
                'n_fn'                  => ( empty($contact->n_fn) ) ? 'imported' : $contact->n_fn,
            
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
            
                'tags'                  => array(),
            
                // no longer used?
                /*
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
                */
                ) 
            );
           
            $tineContact = Addressbook_Backend_Sql::getInstance()->addContact($tineContact);
            echo " ok.<br/>";
            
            // get categories -> tags
            $categories = $this->getCategories();
            $catIds = explode ( ',', $contact->cat_id );
            $filter = new Tinebase_Tags_Model_Filter(array(
                'name'        => '%',
                'application' => 'Addressbook',
                //'owner'       => $owner,
            ));
            $paging = new Tinebase_Model_Pagination();
                
            $contactTags = new Tinebase_Record_RecordSet ('Tinebase_Tags_Model_Tag');
            foreach ( $catIds as $catId ) {
                $filter->name = $categories[$catId]->cat_name;
                $tags = Tinebase_Tags::getInstance()->searchTags($filter, $paging)->toArray();
                if ( empty($tags) ) {
                    $tag = new Tinebase_Tags_Model_Tag (array(
                        'type'  => Tinebase_Tags_Model_Tag::TYPE_SHARED,
                        'name'  => $categories[$catId]->cat_name,
                    ));
                    $tag = Tinebase_Tags::getInstance()->createTag($tag);
                    $contactTags->addRecord($tag);
                } else {
                    $contactTags->addRecord(new Tinebase_Tags_Model_Tag($tags[0]));
                }
            }        
                        
            $tineContact->tags = $contactTags;
            Tinebase_Tags::getInstance()->setTagsOfRecord($tineContact);
            
        }
        echo "done! got ".sizeof($contacts)." contacts.<br>";
        
    }   

    /**
     * get categories (-> tags)
     *
     * @param string $oldTableName [OPTIONAL]
     * @return  array  categories
     */
    private function getCategories($_oldTableName = NULL)
    {
        $cats = array();
        
        // get old table data
        $tableName = ( $_oldTableName != NULL ) ? $_oldTableName : $this->oldTablePrefix.'categories';
        $table = new Tinebase_Db_Table(array('name' => $tableName));
        $rows = $table->fetchAll();
        
        // fill array
        $cats = array();
        foreach ( $rows as $row ) {
            $cats[$row->cat_id] = $row;
        }
        
        return $cats;
    }
    
}
