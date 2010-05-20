<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ 
 */

/**
 * class to import addressbooks/contacts from egw14
 * 
 * NOTE: THIS CLASS IS JUST A PAST OF OLD CODE WHICH SHOULD NOT GET LOST
 *       ITS NOT YET RUNNABLE IN ANY WAYS
 * 
 * @package     Addressbook
 * @subpackage  Setup
 */
class Adressbook_Setup_Import_Egw14 extends Tinebase_Setup_Import_Egw14_Abstract
{

    /**
     * country mapping
     * 
     * @var array
     * @todo    add more countries
     */
    protected $countryMapping = array(
        "ÖSTERREICH" => "AT",
        "BELGIEN" => "BE",
        "DEUTSCHLAND" => "DE",
        "FRANKREICH" => "FR",
        "GERMANY" => "DE",
        "LUXEMBURG" => "LU",
        "NIEDERLANDE" => "NL",
        "SCHWEIZ" => "CH",
        "SLOVAKEI" => "SK",
        "SPANIEN" => "ES",
        "VEREINIGTE STAATEN VON AMERIKA" => "US",
    );
    
    /**
     * import the addressbook from egw14
     *
     * @param string $_oldTableName [OPTIONAL]
     * @param int $_useOldId [OPTIONAL]
     * 
     * @todo    use old group name for the (shared) container ?
     * @todo    add more config params (
     */
    public function importAddressbook( $_oldTableName = NULL, $_useOldId = TRUE )
    {
        // did nothing
        //@set_time_limit (120);  
        $sharedContactsGroupId = -15;
        $sharedContactsContainerName = "Metaways Kontakte";
        $setFileasFromName = TRUE; 
        
        $tableName = ( $_oldTableName != NULL ) ? $_oldTableName : $this->oldTablePrefix.'addressbook';
        $contactsTable = new Tinebase_Db_Table(array('name' => $tableName));
        
        // get contacts
        $contacts = $contactsTable->fetchAll();

        // get categories
        $categories = $this->getCategories();
        
        echo "Import Contacts from table ".$tableName." ... <br/>";
        
        foreach($contacts as $contact) {

            echo "importing " . $contact->n_given . " " . $contact->n_family . " ...";

            /******************** add container ************************/
            
            if ( $contact->contact_owner > 0 ) {
                // personal container for owner
                try {
                    $container = Tinebase_Container::getInstance()->getContainerByName('Addressbook', 'Personal Contacts', Tinebase_Model_Container::TYPE_PERSONAL);
                } catch (Tinebase_Exception_NotFound $e) {
                    $container = new Tinebase_Model_Container(array(
                        'name' => 'Personal Contacts',
                        'type' => Tinebase_Model_Container::TYPE_PERSONAL,      
                        'backend' => 'Sql',
                        'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),                  
                    ));             
                    $container = Tinebase_Container::getInstance()->addContainer($container, NULL, TRUE);  
                }

            } else if ( $contact->contact_owner == $sharedContactsGroupId ) {
                // default users group -> shared container
                $userGroup = Tinebase_Group::getInstance()->getGroupByName('Users');
                try {
                    $container = Tinebase_Container::getInstance()->getContainerByName('Addressbook', $sharedContactsContainerName, Tinebase_Model_Container::TYPE_SHARED);
                } catch (Tinebase_Exception_NotFound $e) {
                    $container = new Tinebase_Model_Container(array(
                        'name' => $sharedContactsContainerName,
                        'type' => Tinebase_Model_Container::TYPE_SHARED,      
                        'backend' => 'Sql',
                        'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Addressbook')->getId(),                  
                    ));
                    $container = Tinebase_Container::getInstance()->addContainer($container, NULL, TRUE);
                    Tinebase_Container::getInstance()->addGrants($container, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
                        Tinebase_Model_Grants::GRANT_READ,
                        Tinebase_Model_Grants::GRANT_ADD,
                        Tinebase_Model_Grants::GRANT_EDIT,
                        Tinebase_Model_Grants::GRANT_DELETE,
                    ), TRUE);
                }                
            } else {
                echo "skipped.<br/>";
                continue;
            }                   
            $containerId = $container->getId();        

            /******************** set fileas ************************/

            if ( $setFileasFromName ) {
                
                $fileas = ""; 
                if ( !empty($contact->n_family) ) {
                    if ( !empty($contact->n_given) ) {
                        $fileas = $contact->n_family . ", " . $contact->n_given;
                    } else {
                        $fileas = $contact->n_family;
                    }
                } else {
                    $fileas = $contact->n_given;
                }

                if ( empty($fileas) ) {
                    $fileas = $contact->org_name;
                } elseif ( !empty($contact->n_middle) ) {
                    $fileas .= " " .$contact->n_middle;
                }
            } else {
                $fileas = ( empty($contact->n_fileas) ) ? $contact->org_name : $contact->n_fileas;
            }

            /******************** set urls (add 'http://' if missing) ************************/
            
            if ( !preg_match("/https*:\/\//i", $contact->contact_url) && !empty($contact->contact_url) ) {
                $url = "http://".$contact->contact_url;
            } else {
                $url = $contact->contact_url;
            }
            if ( !preg_match("/https*:\/\//i", $contact->contact_url_home) && !empty($contact->contact_url_home) ) {
                $urlHome = "http://".$contact->contact_url_home;
            } else {
                $urlHome = $contact->contact_url_home;
            }
            
            /******************** create contact record ************************/
            
            $tineContact = new Addressbook_Model_Contact ( array(
                
                'id'                    => ( $_useOldId ) ? $contact->contact_id : 0,
                'account_id'            => $contact->account_id,                        
                'owner'                 => $containerId,

                'n_family'              => ( empty($contact->n_family) ) ? 'imported' : $contact->n_family,
                'n_fileas'              => $fileas,
                'n_fn'                  => ( empty($contact->n_fn) ) ? 'imported' : $contact->n_fn,
            
                'adr_one_countryname'   => ( isset($this->countryMapping[$contact->adr_one_countryname]) ) ? $this->countryMapping[$contact->adr_one_countryname] : "",
                'adr_one_locality'      => $contact->adr_one_locality,
                'adr_one_postalcode'    => $contact->adr_one_postalcode,
                'adr_one_region'        => $contact->adr_one_region,
                'adr_one_street'        => $contact->adr_one_street,
                'adr_one_street2'       => $contact->adr_one_street2,
                'adr_two_countryname'   => ( isset($this->countryMapping[$contact->adr_two_countryname]) ) ? $this->countryMapping[$contact->adr_two_countryname] : "",
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
                'url'                   => $url,
                'url_home'              => $urlHome,
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
           
            //$tineContact = Addressbook_Backend_Sql::getInstance()->create($tineContact);
            $tineContact = Addressbook_Controller_Contact::getInstance()->create($tineContact);
            echo " ok.<br/>";
            
            // get categories -> tags
            if (!empty($contact->cat_id)) {
                $catIds = explode ( ',', $contact->cat_id );
                $filter = new Tinebase_Model_TagFilter(array(
                    'name'        => '%',
                    'application' => 'Addressbook',
                    //'owner'       => $owner,
                ));
                $paging = new Tinebase_Model_Pagination();
                    
                $contactTags = new Tinebase_Record_RecordSet ('Tinebase_Model_Tag');
                foreach ( $catIds as $catId ) {
                    if ( isset($categories[$catId]) ) {
                        $filter->name = $categories[$catId]->cat_name;
                        $tags = Tinebase_Tags::getInstance()->searchTags($filter, $paging)->toArray();
                        if ( empty($tags) ) {
                            $tag = new Tinebase_Model_Tag (array(
                                'type'  => Tinebase_Model_Tag::TYPE_SHARED,
                                'name'  => $categories[$catId]->cat_name,
                            ));
                            $tag = Tinebase_Tags::getInstance()->createTag($tag);
                            $contactTags->addRecord($tag);
                        } else {
                            $contactTags->addRecord(new Tinebase_Model_Tag($tags[0]));
                        }
                    }
                }        
                            
                $tineContact->tags = $contactTags;
                Tinebase_Tags::getInstance()->setTagsOfRecord($tineContact);
            }            
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
