<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id $
 */

class Tinebase_UserProfile
{
    /**
     * possible profile fields
     * @var array
     */
    protected $_possibleFields = array(
        'adr_one_countryname', 'adr_one_locality', 'adr_one_postalcode', 'adr_one_region', 'adr_one_street', 'adr_one_street2',
        'adr_two_countryname', 'adr_two_locality', 'adr_two_postalcode', 'adr_two_region', 'adr_two_street', 'adr_two_street2',
        'assistent',
        'bday',
        'calendar_uri',
        'email',
        'email_home',
        'jpegphoto',
        'freebusy_uri',
        'account_id',
        'note',
        'role',
        'salutation_id',
        'title',
        'url',
        'url_home',
        'n_family',
        'n_given',
        'n_middle',
        'n_prefix',
        'n_suffix',
        'org_name',
        'org_unit',
        'pubkey',
        'room',
        'tel_assistent',
        'tel_car',
        'tel_cell',
        'tel_cell_private',
        'tel_fax',
        'tel_fax_home',
        'tel_home',
        'tel_pager',
        'tel_work',
        'tz',
        'lon',
        'lat',
    );
    
    /**
     * readable fields (stored in config later)
     * @var array
     */
    protected $_readableFields = array(
        'n_prefix', 'n_given', 'n_middle', 'n_family',
        'email_home',
        'tel_home', 'tel_cell_private', 'tel_fax_home', 
        'adr_two_street', 'adr_two_postalcode', 'adr_two_locality',
        
        // generics
        'account_id',
        'created_by',
        'creation_time',
        'last_modified_by',
        'last_modified_time',
        'is_deleted',
        'deleted_time',
        'deleted_by',
    );
    
    /**
     * updateable fields (stored in config later)
     * @var array
     */
    protected $_updateableFields = array(
        'n_prefix', 'n_given', 'n_middle', 'n_family',
        'email_home',
        'tel_home', 'tel_cell_private', 'tel_fax_home', 
        'adr_two_street', 'adr_two_postalcode', 'adr_two_locality',
    );
    
    /**
     * holds the instance of the singleton
     *
     * @var Tinebase_UserProfile
     */
    private static $instance = NULL;
    
    /**
     * don't clone
     */
    private function __clone() {}
    
    /**
     * the constructor
     */
    private function __construct()
    {
    }
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_UserProfile
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_UserProfile();
        }
        return self::$instance;
    }
    
    /**
     * get list of readable fields from Addressbook_Model_Contact
     * 
     * @return array
     */
    public function getReadableFields()
    {
        return $this->_readableFields;
    }
    
    /**
     * get list of updateable fields from Addressbook_Model_Contact
     * 
     * @return array
     */
    public function getUpdateableFields()
    {
        return $this->_updateableFields;
    }
    
    /**
     * get userProfile
     *
     * @param  string $_userId
     * @return Addressbook_Model_Contact
     */
    public function get($_userId)
    {
        return Addressbook_Controller_Contact::getInstance()->getUserProfile($_userId);
    }
    
    /**
     * update userProfile
     *
     * @param  Addressbook_Model_Contact $_userProfile
     * @return Addressbook_Model_Contact
     */
    public function update($_userProfile)
    {
        return Addressbook_Controller_Contact::getInstance()->updateUserProfile($_userProfile);
    }
    
    /**
     * return a profile only cleaned up copy of the given contact
     * 
     * @param  Addressbook_Model_Contact $_contact
     * @return Addressbook_Model_Contact
     */
    public function doProfileCleanup($_contact)
    {
        $userProfile = new Addressbook_Model_Contact(array(), TRUE);
        
        foreach($this->getReadableFields() as $fieldName) {
            $userProfile->$fieldName = $_contact->$fieldName;
        }
        
        return $userProfile;
    }
    
    /**
     * checks if user has right to manage this profile
     * 
     * @param string $_userId
     * @throws Tasks_Exception_AccessDenied
     */
    public function checkRight($_userId)
    {
        // check if user is permitted to update profile -> skip normal grant checking
        if (!Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::MANAGE_OWN_PROFILE)) {
            throw new Tasks_Exception_AccessDenied('No rights to manage own profile');
        }
        
        if (Tinebase_Core::getUser()->getId() != $_userId) {
            // We might itroduce a MANAGE_OTHER_PROFILE ?
            throw new Tasks_Exception_AccessDenied('given profile does not belong to current user');
        }
    }
    
    /**
     * merges allowed fields from $userProfile into a clone of given $contact
     * 
     * @param  Addressbook_Model_Contact $contact
     * @param  Addressbook_Model_Contact $userProfile
     * @return Addressbook_Model_Contact
     */
    public function mergeProfileInfo($_contact, $_userProfile)
    {
        $contact = clone $_contact;
        
        foreach($this->getUpdateableFields() as $fieldName) {
            $contact->$fieldName = $_userProfile->$fieldName;
        }
        
        return $contact;
    }
}
    