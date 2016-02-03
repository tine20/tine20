<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contact data
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @property    string account_id                 id of associated user
 * @property    string adr_one_countryname        name of the country the contact lives in
 * @property    string adr_one_locality           locality of the contact
 * @property    string adr_one_postalcode         postalcode belonging to the locality
 * @property    string adr_one_region             region the contact lives in
 * @property    string adr_one_street             street where the contact lives
 * @property    string adr_one_street2            street2 where contact lives
 * @property    string adr_two_countryname        second home/country where the contact lives
 * @property    string adr_two_locality           second locality of the contact
 * @property    string adr_two_postalcode         ostalcode belonging to second locality
 * @property    string adr_two_region             second region the contact lives in
 * @property    string adr_two_street             second street where the contact lives
 * @property    string adr_two_street2            second street2 where the contact lives
 * @property    string assistent                  name of the assistent of the contact
 * @property    datetime bday                     date of birth of contact
 * @property    integer container_id              id of container
 * @property    string email                      the email address of the contact
 * @property    string email_home                 the private email address of the contact
 * @property    blob jpegphoto                    photo of the contact
 * @property    string n_family                   surname of the contact
 * @property    string n_fileas                   display surname, name
 * @property    string n_fn                       the full name
 * @property    string n_given                    forename of the contact
 * @property    string n_middle                   middle name of the contact
 * @property    string note                       notes of the contact
 * @property    string n_prefix
 * @property    string n_suffix
 * @property    string org_name                   name of the company the contact works at
 * @property    string org_unit
 * @property    string role                       type of role of the contact
 * @property    string tel_assistent              phone number of the assistent
 * @property    string tel_car
 * @property    string tel_cell                   mobile phone number
 * @property    string tel_cell_private           private mobile number
 * @property    string tel_fax                    number for calling the fax
 * @property    string tel_fax_home               private fax number
 * @property    string tel_home                   telephone number of contact's home
 * @property    string tel_pager                  contact's pager number
 * @property    string tel_work                   contact's office phone number
 * @property    string title                      special title of the contact
 * @property    string type                       type of contact
 * @property    string url                        url of the contact
 * @property    string url_home                   private url of the contact
 */
class Addressbook_Model_Contact extends Tinebase_Record_Abstract
{
    /**
     * const to describe contact of current account id independent
     * 
     * @var string
     */
    const CURRENTCONTACT = 'currentContact';
    
    /**
     * contact type: contact
     * 
     * @var string
     */
    const CONTACTTYPE_CONTACT = 'contact';
    
    /**
     * contact type: user
     * 
     * @var string
     */
    const CONTACTTYPE_USER = 'user';

    /**
     * small contact photo size
     *
     * @var integer
     */
    const SMALL_PHOTO_SIZE = 36000;
    
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Addressbook';
    
    /**
     * if foreign Id fields should be resolved on search and get from json
     * should have this format: 
     *     array('Calendar_Model_Contact' => 'contact_id', ...)
     * or for more fields:
     *     array('Calendar_Model_Contact' => array('contact_id', 'customer_id), ...)
     * (e.g. resolves contact_id with the corresponding Model)
     * 
     * @var array
     */
    protected static $_resolveForeignIdFields = array(
        'Tinebase_Model_User' => array('created_by', 'last_modified_by'),
        'recursive'           => array('attachments' => 'Tinebase_Model_Tree_Node'),
    );
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        'adr_one_countryname'   => array('StringTrim', 'StringToUpper'),
        'adr_two_countryname'   => array('StringTrim', 'StringToUpper'),
        'email'                 => array('StringTrim', 'StringToLower'),
        'email_home'            => array('StringTrim', 'StringToLower'),
        'url'                   => array('StringTrim'),
        'url_home'              => array('StringTrim'),
    );

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'adr_one_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_region'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street2'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_lon'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_lat'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_region'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street2'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_lon'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_lat'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'assistent'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bday'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'calendar_uri'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'email'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'email_home'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'jpegphoto'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'freebusy_uri'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'note'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container_id'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'role'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'salutation'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'title'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'url'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'url_home'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_family'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_fileas'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_fn'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_given'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_middle'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_prefix'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_suffix'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_name'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_unit'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pubkey'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'room'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_assistent'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_car'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_private'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_home'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_pager'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_work'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_other'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_prefer'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tz'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'geo'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // modlog fields
        'created_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // tine 2.0 generic fields
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'customfields'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'type'                  => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTACTTYPE_CONTACT,
            array('InArray', array(self::CONTACTTYPE_USER, self::CONTACTTYPE_CONTACT)),
        ),
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'bday',
        'creation_time',
        'last_modified_time',
        'deleted_time'
    );
    
    /**
    * name of fields that should be omited from modlog
    *
    * @var array list of modlog omit fields
    */
    protected $_modlogOmitFields = array(
        'jpegphoto',
    );
    
    /**
    * overwrite constructor to add more filters
    *
    * @param mixed $_data
    * @param bool $_bypassFilters
    * @param mixed $_convertDates
    */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // set geofields to NULL if empty
        $geoFields = array('adr_one_lon', 'adr_one_lat', 'adr_two_lon', 'adr_two_lat');
        foreach ($geoFields as $geoField) {
            $this->_filters[$geoField]        = new Zend_Filter_Empty(NULL);
        }
    
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
    
    /**
     * returns prefered email address of given contact
     * 
     * @return string
     */
    public function getPreferedEmailAddress()
    {
        // prefer work mail over private mail till we have prefs for this
        return $this->email ? $this->email : $this->email_home;
    }
    
    /**
     * (non-PHPdoc)
     * @see Tinebase/Record/Tinebase_Record_Abstract#setFromArray($_data)
     */
    public function setFromArray(array $_data)
    {
        $_data = $this->_resolveAutoValues($_data);
        parent::setFromArray($_data);
    }
    
    /**
     * Resolves the auto values n_fn and n_fileas
     * @param array $_data
     * @return array $_data
     */
    protected function _resolveAutoValues(array $_data) {
        if (! (isset($_data['org_name']) || array_key_exists('org_name', $_data))) {
            $_data['org_name'] = '';
        }

        // try to guess name from n_fileas
        // TODO: n_fn
        if (empty($_data['org_name']) && empty($_data['n_family'])) {
            if (! empty($_data['n_fileas'])) {
                $names = preg_split('/\s*,\s*/', $_data['n_fileas']);
                $_data['n_family'] = $names[0];
                if (empty($_data['n_given'])&& isset($names[1])) {
                    $_data['n_given'] = $names[1];
                }
            }
        }
        
        // always update fileas and fn
        $_data['n_fileas'] = (!empty($_data['n_family'])) ? $_data['n_family']
            : ((! empty($_data['org_name'])) ? $_data['org_name']
            : ((isset($_data['n_fileas'])) ? $_data['n_fileas'] : ''));

        if (!empty($_data['n_given'])) {
            $_data['n_fileas'] .= ', ' . $_data['n_given'];
        }

        $_data['n_fn'] = (!empty($_data['n_family'])) ? $_data['n_family']
            : ((! empty($_data['org_name'])) ? $_data['org_name']
            : ((isset($_data['n_fn'])) ? $_data['n_fn'] : ''));

        if (!empty($_data['n_given'])) {
            $_data['n_fn'] = $_data['n_given'] . ' ' . $_data['n_fn'];
        }
        return $_data;
    }
    
    /**
     * Overwrites the __set Method from Tinebase_Record_Abstract
     * Also sets n_fn and n_fileas when org_name, n_given or n_family should be set
     * @see Tinebase_Record_Abstract::__set()
     */
    public function __set($_name, $_value) {
        
        switch ($_name) {
            case 'n_given':
                $resolved = $this->_resolveAutoValues(array('n_given' => $_value, 'n_family' => $this->__get('n_family'), 'org_name' => $this->__get('org_name')));
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
                break;
            case 'n_family':
                $resolved = $this->_resolveAutoValues(array('n_family' => $_value, 'n_given' => $this->__get('n_given'), 'org_name' => $this->__get('org_name')));
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
                break;
            case 'org_name':
                $resolved = $this->_resolveAutoValues(array('org_name' => $_value, 'n_given' => $this->__get('n_given'), 'n_family' => $this->__get('n_family')));
                parent::__set('n_fn', $resolved['n_fn']);
                parent::__set('n_fileas', $resolved['n_fileas']);
        }
        
        parent::__set($_name, $_value);
    }

    /**
     * fills a contact from json data
     *
     * @param array $_data record data
     * @return void
     * 
     * @todo timezone conversion for birthdays?
     * @todo move this to Addressbook_Convert_Contact_Json
     */
    protected function _setFromJson(array &$_data)
    {
        $this->_setContactImage($_data);
        
        // unset if empty
        // @todo is this still needed?
        if (empty($_data['id'])) {
            unset($_data['id']);
        }
    }
    
    /**
     * set contact image
     * 
     * @param array $_data
     */
    protected function _setContactImage(&$_data)
    {
        if (! isset($_data['jpegphoto']) || $_data['jpegphoto'] === '') {
            return;
        }
        
        $imageParams = Tinebase_ImageHelper::parseImageLink($_data['jpegphoto']);
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' image params:' . print_r($imageParams, TRUE));
        if ($imageParams['isNewImage']) {
            try {
                $_data['jpegphoto'] = Tinebase_ImageHelper::getImageData($imageParams);
            } catch(Tinebase_Exception_UnexpectedValue $teuv) {
                Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' Could not add contact image: ' . $teuv->getMessage());
                unset($_data['jpegphoto']);
            }
        } else {
            unset($_data['jpegphoto']);
        }
    }

    /**
     * set small contact image
     *
     * @param $newPhotoBlob
     */
    public function setSmallContactImage($newPhotoBlob)
    {
        if ($this->getId()) {
            try {
                $currentPhoto = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId())->getBlob('image/jpeg', self::SMALL_PHOTO_SIZE);
            } catch (Exception $e) {
                // no current photo
            }
        }

        if (isset($currentPhoto) && $currentPhoto == $newPhotoBlob) {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__
                . " Photo did not change -> preserving current photo");
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__
                . " Setting new contact photo (" . strlen($newPhotoBlob) . "KB)");
            $this->jpegphoto = $newPhotoBlob;
        }
    }

    /**
     * return small contact image for sync
     *
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getSmallContactImage()
    {
        $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId());
        return $image->getBlob('image/jpeg', self::SMALL_PHOTO_SIZE);
    }
}
