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
        $this->_duplicateCheckFields = Addressbook_Config::getInstance()->get(Addressbook_Config::CONTACT_DUP_FIELDS, array(
                array('n_given', 'n_family', 'org_name'),
                array('email'),
            ));
        
        // fields used for private and company address
        $this->_addressFields = array('locality', 'postalcode', 'street', 'countryname');
        
        $this->_setGeoDataForContacts = Tinebase_Config::getInstance()->getConfig(Tinebase_Config::MAPPANEL, NULL, TRUE)->value;
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
        $defaultAddressbookId = Tinebase_Core::getPreference($this->_applicationName)->getValue(Addressbook_Preference::DEFAULTADDRESSBOOK);
        try {
            $defaultAddressbook = Tinebase_Container::getInstance()->getContainerById($defaultAddressbookId);
        } catch (Tinebase_Exception $te) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Create new default addressbook. (' . $te->getMessage() . ')');
            
            // default may be gone -> remove default adb pref
            Tinebase_Core::getPreference($this->_applicationName)->deleteUserPref(Addressbook_Preference::DEFAULTADDRESSBOOK);
            
            // generate a new one
            $defaultAddressbookId = Tinebase_Core::getPreference($this->_applicationName)->getValue(Addressbook_Preference::DEFAULTADDRESSBOOK);
            $defaultAddressbook = Tinebase_Container::getInstance()->getContainerById($defaultAddressbookId);
        }
        
        return $defaultAddressbook;
    }
    
    /**
    * you can define default filters here
    *
    * @param Tinebase_Model_Filter_FilterGroup $_filter
    */
    protected function _addDefaultFilter(Tinebase_Model_Filter_FilterGroup $_filter = NULL)
    {
        if (! $_filter->isFilterSet('showDisabled')) {
            $disabledFilter = $_filter->createFilter('showDisabled', 'equals', FALSE);
            $disabledFilter->setIsImplicit(TRUE);
            $_filter->addFilter($disabledFilter);
        }
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
        
        $contact = $this->update($userProfile, FALSE);
        
        $userProfile = Tinebase_UserProfile::getInstance()->doProfileCleanup($contact);

        $this->doContainerACLChecks($doContainerACLChecks);
        
        return $userProfile;
    }
    
    /**
     * inspect update of one record (after update)
     * 
     * @param   Tinebase_Record_Interface $_updatedRecord   the just updated record
     * @param   Tinebase_Record_Interface $_record          the update record
     * @return  void
     */
    protected function _inspectAfterUpdate($_updatedRecord, $_record)
    {
        if ($_updatedRecord->type == Addressbook_Model_Contact::CONTACTTYPE_USER) {
            Tinebase_User::getInstance()->updateContact($_updatedRecord);
        }        
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
     */
    protected function _inspectBeforeUpdate($_record, $_oldRecord)
    {
    	// do update of geo data only if one of address field changed
    	$addressDataChanged = FALSE;
    	foreach ($this->_addressFields as $field) {
       		if (
       			($_record->{'adr_one_' . $field} != $_oldRecord->{'adr_one_' . $field}) ||
       			($_record->{'adr_two_' . $field} != $_oldRecord->{'adr_two_' . $field})
       		) {
				$addressDataChanged = TRUE;
				break;
			}
    	}
    	
    	if ($addressDataChanged) {
    		$this->_setGeoData($_record);
    	}
    	        
        if (isset($_oldRecord->type) && $_oldRecord->type == Addressbook_Model_Contact::CONTACTTYPE_USER) {
            $_record->type = Addressbook_Model_Contact::CONTACTTYPE_USER;
        }
    }
    
    /**
     * set geodata for given address of record
     * 
     * @param string 					$_address (addressbook prefix - adr_one_ or adr_two_)
     * @param Addressbook_Model_Contact $_record
     * @param array $_ommitFields do not submit these fields to nominatim
     * @return void
     */
    protected function _setGeoDataForAddress($_address, Addressbook_Model_Contact $_record, $_ommitFields = array())
    {
    	if (
    		empty($_record->{$_address . 'locality'}) && 
    		empty($_record->{$_address . 'postalcode'}) && 
    		empty($_record->{$_address . 'street'}) && 
    		empty($_record->{$_address . 'countryname'})
    	) {
            $_record->{$_address . 'lon'} = NULL;
            $_record->{$_address . 'lat'} = NULL;
            
            return;
        }
        
        $nominatim = new Zend_Service_Nominatim();

        if (! empty($_record->{$_address . 'locality'})) {
            $nominatim->setVillage($_record->{$_address . 'locality'});
        }
        
        if (! empty($_record->{$_address . 'postalcode'}) && ! in_array($_address . 'postalcode', $_ommitFields)) {
            $nominatim->setPostcode($_record->{$_address . 'postalcode'});
        }
        
        if (! empty($_record->{$_address . 'street'})) {
            $nominatim->setStreet($_record->{$_address . 'street'});
        }
        
        if (! empty($_record->{$_address . 'countryname'})) {
            $country = Zend_Locale::getTranslation($_record->{$_address . 'countryname'}, 'Country', $_record->{$_address . 'countryname'});
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ($_address == 'adr_one_' ? ' Company address' : ' Private address') . ' country ' . $country);
            $nominatim->setCountry($country);
        }
        
        try {            
            $places = $nominatim->search();
            
            if (count($places) > 0) {
                $place = $places->current();
                
                $_record->{$_address . 'lon'} = $place->lon;
                $_record->{$_address . 'lat'} = $place->lat;
                
                if (empty($_record->{$_address . 'countryname'}) && ! empty($place->country_code)) {
                    $_record->{$_address . 'countryname'} = $place->country_code;
                }
                if (empty($_record->{$_address . 'postalcode'}) && ! empty($place->postcode)) {
                    $_record->{$_address . 'postalcode'} = $place->postcode;
                }
                if (empty($_record->{$_address . 'locality'}) && ! empty($place->city)) {
                    $_record->{$_address . 'locality'} = $place->city;
                }
                
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
					($_address == 'adr_one_' ? ' Company' : ' Private') . ' Place found: lon/lat ' . $_record->{$_address . 'lon'} . ' / ' . $_record->{$_address . 'lat'});
                
            } else {
                if (! in_array($_address . 'postalcode', $_ommitFields)) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
						($_address == 'adr_one_' ? ' Company address' : ' Private address') . ' could not find place - try it again without postalcode.');
						
                    $this->_setGeoDataForAddress($_address, $_record, array($_address . 'postalcode'));
                    return;
                }
                
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ($_address == 'adr_one_' ? 'Company address' : 'Private address') . ' Could not find place.');
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
					' ' . $_record->{$_address . 'street'} . ', ' . $_record->{$_address . 'postalcode'} . ', ' . $_record->{$_address . 'locality'} . ', ' . $_record->{$_address . 'countryname'});
                
                $_record->{$_address . 'lon'} = NULL;
                $_record->{$_address . 'lat'} = NULL;
            }
        } catch (Exception $e) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' ' . $e->getMessage());
            
            // the address has changed, the old values for lon/lat can not be valid anymore
            $_record->{$_address . 'lon'} = NULL;
            $_record->{$_address . 'lat'} = NULL;
        }
    }
    
    /**
     * set geodata of record
     * 
     * @param Addressbook_Model_Contact $_record
     * @return void
     */
    protected function _setGeoData(Addressbook_Model_Contact $_record)
    {
        if (! $this->_setGeoDataForContacts) {
            return;
        }
        
        $this->_setGeoDataForAddress('adr_one_', $_record);
        $this->_setGeoDataForAddress('adr_two_', $_record);
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
    
    /**
     * get contact information from string by parsing it using predefined rules
     * 
     * @param string $_address
     * @return array with Addressbook_Model_Contact + array of unrecognized tokens
     */
    public function parseAddressData($_address)
    {
        $converter = new Addressbook_Convert_Contact_String(); 
        
        $result = array(
            'contact'             => $converter->toTine20Model($_address),
            'unrecognizedTokens'  => $converter->getUnrecognizedTokens(),
        );
                    
        return $result;
    }
}
