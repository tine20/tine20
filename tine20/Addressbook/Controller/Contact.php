<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * contact controller for Addressbook
 *
 * @package     Addressbook
 * @subpackage  Controller
 */
class Addressbook_Controller_Contact extends Tinebase_Controller_Record_Abstract
{
    /**
     * set geo data for contacts
     * 
     * @var boolean
     */
    protected $_setGeoDataForContacts = FALSE;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Addressbook';
        $this->_modelName = 'Addressbook_Model_Contact';
        $this->_backend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $this->_currentAccount = Tinebase_Core::getUser();
        $this->_purgeRecords = FALSE;
        $this->_resolveCustomFields = TRUE;
        
        $this->_setGeoDataForContacts = Tinebase_Config::getInstance()->getConfig(Tinebase_Model_Config::MAPPANEL, NULL, TRUE)->value;
        if (! $this->_setGeoDataForContacts) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Mappanel/geoext/nominatim disabled with config option.');
        }
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var Addressbook_Controller_Contact
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Addressbook_Controller_Contact
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Controller_Contact();
        }
        
        return self::$_instance;
    }
    
    /**
     * gets binary contactImage
     *
     * @param int $_contactId
     * @return blob
     */
    public function getImage($_contactId) {
        // ensure user has rights to see image
        $this->get($_contactId);
        
        $image = $this->_backend->getImage($_contactId);
        return $image;
    }
    
    /**
     * returns the default addressbook
     * 
     * @return Tinebase_Model_Container
     * 
     * @todo replace this with Tinebase_Container::getInstance()->getDefaultContainer
     */
    public function getDefaultAddressbook()
    {
        $defaultAddressbookId = Tinebase_Core::getPreference('Addressbook')->getValue(Addressbook_Preference::DEFAULTADDRESSBOOK);
        try {
            $defaultAddressbook = Tinebase_Container::getInstance()->getContainerById($defaultAddressbookId);
        } catch (Tinebase_Exception $te) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Create new default addressbook. (' . $te->getMessage() . ')');
            
            // default may be gone -> remove default adb pref
            Tinebase_Core::getPreference('Addressbook')->deleteUserPref(Addressbook_Preference::DEFAULTADDRESSBOOK);
            
            // generate a new one
            $defaultAddressbookId = Tinebase_Core::getPreference('Addressbook')->getValue(Addressbook_Preference::DEFAULTADDRESSBOOK);
            $defaultAddressbook = Tinebase_Container::getInstance()->getContainerById($defaultAddressbookId);
        }
        
        return $defaultAddressbook;
    }
    
    /**
     * fetch one contact identified by $_userId
     *
     * @param   int $_userId
     * @param   boolean $_ignoreACL don't check acl grants
     * @return  Addressbook_Model_Contact
     * @throws  Addressbook_Exception_AccessDenied if user has no read grant
     * @throws  Addressbook_Exception_NotFound if contact is hidden from addressbook
     * 
     * @todo this is almost always called with ignoreACL = TRUE because contacts can be hidden from addressbook. 
     *       is this the way we want that?
     */
    public function getContactByUserId($_userId, $_ignoreACL = FALSE)
    {
        $contact = $this->_backend->getByUserId($_userId);
        
        if ($_ignoreACL === FALSE) {
            if (empty($contact->container_id)) {
                throw new Addressbook_Exception_NotFound('Contact is hidden from addressbook (container id is empty).');
            }
            if (! $this->_currentAccount->hasGrant($contact->container_id, Tinebase_Model_Grants::GRANT_READ)) {
                throw new Addressbook_Exception_AccessDenied('Read access to contact denied.');
            }
        }
        
        if ($this->_resolveCustomFields && $contact->has('customfields')) {
            Tinebase_CustomField::getInstance()->resolveRecordCustomFields($contact);
        }
        
        return $contact;
    }

    /**
     * can be called to activate/deactivate if geodata should be set for contacts (ignoring the config setting)
     * 
     * @param boolean $_value
     * @return void
     */
    public function setGeoDataForContacts($_value)
    {
        $this->_setGeoDataForContacts = (boolean) $_value;
    }
    
    /**
     * gets profile portion of the requested user
     * 
     * @param string $_userId
     * @return Addressbook_Model_Contact 
     */
    public function getUserProfile($_userId)
    {
        Tinebase_UserProfile::getInstance()->checkRight($_userId);
        
        $contact = $this->getContactByUserId($_userId, TRUE);
        $userProfile = Tinebase_UserProfile::getInstance()->doProfileCleanup($contact);
        
        return $userProfile;
    }
    
    /**
     * update profile portion of given contact
     * 
     * @param  Addressbook_Model_Contact $_userProfile
     * @return Addressbook_Model_Contact
     * 
     * @todo think about adding $_ignoreACL to generic update() to simplify this
     */
    public function updateUserProfile($_userProfile)
    {
        Tinebase_UserProfile::getInstance()->checkRight($_userProfile->account_id);
        
        $doContainerACLChecks = $this->doContainerACLChecks(FALSE);
        
        $contact = $this->getContactByUserId($_userProfile->account_id, true);
        
        // we need to unset the jpegphoto because update() expects the image data and we only have a boolean value here
        unset($contact->jpegphoto);
        
        $userProfile = Tinebase_UserProfile::getInstance()->mergeProfileInfo($contact, $_userProfile);
        
        $contact = $this->update($userProfile);
        
        $userProfile = Tinebase_UserProfile::getInstance()->doProfileCleanup($contact);

        $this->doContainerACLChecks($doContainerACLChecks);
        
        return $userProfile;
    }
    
    /**
     * update one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Addressbook_Model_Contact
     * @throws  Tinebase_Exception_AccessDenied
     */
    public function update(Tinebase_Record_Interface $_record)
    {
        $contact = parent::update($_record);
        
        if ($contact->type == Addressbook_Model_Contact::CONTACTTYPE_USER) {
            Tinebase_User::getInstance()->updateContact($contact);
        }
        
        return $contact;
    }
    
    /**
     * delete one record
     * - don't delete if it belongs to an user account
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Addressbook_Exception_AccessDenied
     */
    protected function _deleteRecord(Tinebase_Record_Interface $_record)
    {
        if (!empty($_record->account_id)) {
            throw new Addressbook_Exception_AccessDenied('It is not allowed to delete a contact linked to an user account!');
        }
        
        parent::_deleteRecord($_record);
    }
    
    /**
     * inspect creation of one record
     * 
     * @param   Tinebase_Record_Interface $_record
     * @return  void
     */
    protected function _inspectBeforeCreate(Tinebase_Record_Interface $_record)
    {
        $this->_setGeoData($_record);
        
        if (isset($_record->type) &&  $_record->type == Addressbook_Model_Contact::CONTACTTYPE_USER) {
            throw new Addressbook_Exception_InvalidArgument('can not add contact of type user');
        }
    }
    
    /**
     * inspect update of one record
     * 
     * @param   Tinebase_Record_Interface $_record      the update record
     * @param   Tinebase_Record_Interface $_oldRecord   the current persistent record
     * @return  void
     * 
     * @todo    check if address changes before setting new geodata
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
        if (
            ($_record->adr_one_locality    != $_oldRecord->adr_one_locality) ||
            ($_record->adr_one_postalcode  != $_oldRecord->adr_one_postalcode) ||
            ($_record->adr_one_street      != $_oldRecord->adr_one_street) ||
            ($_record->adr_one_countryname != $_oldRecord->adr_one_countryname)
        ) {
            $this->_setGeoData($_record);
        }
        
        if (isset($_oldRecord->type) && $_oldRecord->type == Addressbook_Model_Contact::CONTACTTYPE_USER) {
            $_record->type = Addressbook_Model_Contact::CONTACTTYPE_USER;
        }
    }
    
    /**
     * set geodata of record
     * 
     * @param Addressbook_Model_Contact $_record
     * @param array $_ommitFields do not submit these fields to nominatim
     * @return void
     */
    protected function _setGeoData(Addressbook_Model_Contact $_record, $_ommitFields = array())
    {
        if (! $this->_setGeoDataForContacts) {
            return;
        }
        
        if(empty($_record->adr_one_locality) && empty($_record->adr_one_postalcode) && empty($_record->adr_one_street) && empty($_record->adr_one_countryname)) {
            $_record->lon = NULL;
            $_record->lat = NULL;
            
            return;
        }
        
        $nominatim = new Zend_Service_Nominatim();

        if (! empty($_record->adr_one_locality)) {
            $nominatim->setVillage($_record->adr_one_locality);
        }
        
        if (! empty($_record->adr_one_postalcode) && ! in_array('adr_one_postalcode', $_ommitFields)) {
            $nominatim->setPostcode($_record->adr_one_postalcode);
        }
        
        if (! empty($_record->adr_one_street)) {
            $nominatim->setStreet($_record->adr_one_street);
        }
        
        if (! empty($_record->adr_one_countryname)) {
            $country = Zend_Locale::getTranslation($_record->adr_one_countryname, 'Country', $_record->adr_one_countryname);
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' country ' . $country);
            $nominatim->setCountry($country);
        }
        
        try {            
            $places = $nominatim->search();
            
            if (count($places) > 0) {
                $place = $places->current();
                
                $_record->lon = $place->lon;
                $_record->lat = $place->lat;
                
                if (empty($_record->adr_one_countryname) && !empty($place->country_code)) {
                    $_record->adr_one_countryname = $place->country_code;
                }
                if ((empty($_record->adr_one_postalcode) || in_array('adr_one_postalcode', $_ommitFields)) && !empty($place->postcode)) {
                    $_record->adr_one_postalcode = $place->postcode;
                }
                if (empty($_record->adr_one_locality) && !empty($place->city)) {
                    $_record->adr_one_locality = $place->city;
                }
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                    . ' Place found: lon/lat ' . $_record->lon . ' / ' . $_record->lat);
                
            } else {
                if (! in_array('adr_one_postalcode', $_ommitFields)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' .
                        'Could not find place - try it again without postalcode.');
                    $this->_setGeoData($_record, array('adr_one_postalcode'));
                    return;
                }
                
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Could not find place.');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $_record->adr_one_street . ', ' 
                    . $_record->adr_one_postalcode . ', ' . $_record->adr_one_locality . ', ' . $_record->adr_one_countryname
                );
                
                $_record->lon = NULL;
                $_record->lat = NULL;
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            
            // the address has changed, the old values for lon/lat can not be valid anymore
            $_record->lon = NULL;
            $_record->lat = NULL;
        }
    }
    
    /**
     * get number from street (and remove it)
     * 
     * @param string $_street
     * @return string
     */
    protected function _splitNumberAndStreet(&$_street)
    {
        $pattern = '([0-9]+)';
        preg_match('/ ' . $pattern . '$/', $_street, $matches);
        
        if (empty($matches)) {
            // look at the beginning
            preg_match('/^' . $pattern . ' /', $_street, $matches);
        }
        
        if (array_key_exists(1, $matches)) {
            $result = $matches[1];
            $_street = str_replace($matches[0], '', $_street);
        } else {
            $result = '';
        }
        
        return $result;
    }
}
