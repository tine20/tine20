<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * userprofile class
 * 
 * @package     ActiveSync
 */
class Tinebase_UserProfile
{
    /**
     * Config key for allowed userProfile fields
     * @staticvar string
     */
    const USERPROFILEFIELDS = 'userProfileFields';
    
    /**
     * possible profile fields
     * @var array
     */
    protected $_possibleFields = array(
        'n_prefix',
        'n_given',
        'n_middle',
        'n_family',
        'n_suffix',
        //'bday',
        'org_name',
        'org_unit',
        'role',
        'title',
        'room',
        'email',
        'email_home',    
        'tel_cell',
        'tel_cell_private',
        'tel_fax',
        'tel_fax_home',
        'tel_home',
        'tel_work',
        'url',
        'adr_one_countryname', 'adr_one_locality', 'adr_one_postalcode', 'adr_one_region', 'adr_one_street', 'adr_one_street2',
        'adr_two_countryname', 'adr_two_locality', 'adr_two_postalcode', 'adr_two_region', 'adr_two_street', 'adr_two_street2',
        'note',
    );
    
    /**
     * fields readable per default
     * 
     * @var array
     */
    protected $_defaultReadableFields = array(
        'n_prefix', 'n_given', 'n_middle', 'n_family',
        'email_home',
        'tel_home', 'tel_cell_private', 'tel_fax_home', 
        'adr_two_street', 'adr_two_postalcode', 'adr_two_locality',
    );
    
    /**
     * fields updateable per default
     * 
     * @var array
     */
    protected $_defaultUpdateableFields = array(
        'n_prefix', 'n_given', 'n_middle', 'n_family',
        'email_home',
        'tel_home', 'tel_cell_private', 'tel_fax_home', 
        'adr_two_street', 'adr_two_postalcode', 'adr_two_locality',
    );
    
    /**
     * generic fields the framework needs to function
     * 
     * @var array
     */
    protected $_genericFields = array(
        'account_id',
        'created_by',
        'creation_time',
        'last_modified_by',
        'last_modified_time',
        'is_deleted',
        'deleted_time',
        'deleted_by'
    );
    
    /**
     * fields readable
     * 
     * @var array
     */
    protected $_readableFields = array();
    
    /**
     * fields updateable
     * 
     * @var array
     */
    protected $_updateableFields = array();
    
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
        $this->_initConfig();
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
     * get list of possible fields from Addressbook_Model_Contact
     * 
     * @return array
     */
    public function getPossibleFields()
    {
        return $this->_possibleFields;
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
            throw new Tinebase_Exception_AccessDenied('No rights to manage own profile');
        }
        
        if (Tinebase_Core::getUser()->getId() != $_userId) {
            // We might itroduce a MANAGE_OTHER_PROFILE ?
            throw new Tinebase_Exception_AccessDenied('given profile does not belong to current user');
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
    
    /**
     * set readable fields
     * 
     * @param array $_readableFields
     */
    public function setReadableFields($_readableFields)
    {
        Tinebase_Core::getLogger()->debug('setting userProfile readable fields to ' . print_r($_readableFields, TRUE));
        if (!Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('No rights to set userProfile config');
        }
        
        $this->_readableFields = $_readableFields;
        $this->_setConfig();
    }
    
    /**
     * set updateable fields
     * 
     * @param array $_readableFields
     */
    public function setUpdateableFields($_updateableFields)
    {
        Tinebase_Core::getLogger()->debug('setting userProfile updateable fields to ' . print_r($_updateableFields, TRUE));
        if (!Tinebase_Core::getUser()->hasRight('Tinebase', Tinebase_Acl_Rights::ADMIN)) {
            throw new Tinebase_Exception_AccessDenied('No rights to set userProfile config');
        }
        
        $this->_updateableFields = $_updateableFields;
        $this->_setConfig();
    }
    
    /**
     * saves userProfile config
     */
    public function _setConfig()
    {
        $config = array(
            'readableFields'   => $this->_readableFields,
            'updateableFields' => $this->_updateableFields
        );
        
        Tinebase_Config::getInstance()->set(self::USERPROFILEFIELDS, $config);
    }
    
    /**
     * init the config
     * 
     */
    protected function _initConfig()
    {
        $config = Tinebase_Config::getInstance()->get(self::USERPROFILEFIELDS, new Tinebase_Config_Struct(array(
            'readableFields'   => $this->_defaultReadableFields,
            'updateableFields' => $this->_defaultUpdateableFields
        )))->toArray();
        
        // ensure generic fields are in readable fields
        $this->_readableFields = array_merge(
            $config['readableFields'], 
            $this->_genericFields
        );
        
        $this->_updateableFields = $config['updateableFields'];
    }
}
    
