<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
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
        'adr_one_countryname'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_locality'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_postalcode'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_region'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street2'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_lon'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_lat'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_countryname'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_locality'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_postalcode'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_region'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street2'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_lon'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_lat'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'assistent'                     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'bday'                          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'calendar_uri'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'email'                         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'email_home'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'jpegphoto'                     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'freebusy_uri'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'id'                            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'account_id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'note'                          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'container_id'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'role'                          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'salutation'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'title'                         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'url'                           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'url_home'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_family'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_fileas'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_fn'                          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_given'                       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_middle'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_prefix'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_suffix'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_name'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_unit'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'pubkey'                        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'room'                          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_assistent'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_car'                       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_private'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax'                       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_home'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_pager'                     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_work'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_other'                     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_prefer'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_assistent_normalized'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_car_normalized'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_normalized'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_private_normalized'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_normalized'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_home_normalized'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home_normalized'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_pager_normalized'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_work_normalized'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_other_normalized'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_prefer_normalized'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tz'                            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'geo'                           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // modlog fields
        'created_by'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'creation_time'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_by'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_deleted'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_time'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'deleted_by'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'seq'                           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // tine 2.0 generic fields
        'tags'                          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'relations'                     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'attachments'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'customfields'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE         => array()),
        'type'                          => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTACTTYPE_CONTACT,
            array('InArray', array(self::CONTACTTYPE_USER, self::CONTACTTYPE_CONTACT)),
        ),
        'paths'                         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
     * list of telephone country codes
     *
     * source of country codes:
     * $json = json_decode(file_get_contents('https://raw.github.com/mledoze/countries/master/countries.json'));
     * foreach($json as $val) { foreach($val->callingCode as $cc) $data['+'.$cc] = true;}
     * ksort($data);
     * echo 'array(\'' . join('\',\'', array_keys($data)) . '\');';
     *
     * @var array list of telephone country codes
     */
    protected static $countryCodes = array('+1','+7','+20','+27','+30','+31','+32','+33','+34','+36','+39','+40','+41','+43','+44','+45','+46','+47','+48','+49','+51','+52','+53','+54','+55','+56','+57','+58','+60','+61','+62','+63','+64','+65','+66','+76','+77','+81','+82','+84','+86','+90','+91','+92','+93','+94','+95','+98','+211','+212','+213','+216','+218','+220','+221','+222','+223','+224','+225','+226','+227','+228','+229','+230','+231','+232','+233','+234','+235','+236','+237','+238','+239','+240','+241','+242','+243','+244','+245','+246','+248','+249','+250','+251','+252','+253','+254','+255','+256','+257','+258','+260','+261','+262','+263','+264','+265','+266','+267','+268','+269','+291','+297','+298','+299','+350','+351','+352','+353','+354','+355','+356','+357','+358','+359','+370','+371','+372','+373','+374','+375','+376','+377','+378','+379','+380','+381','+382','+383','+385','+386','+387','+389','+420','+421','+423','+500','+501','+502','+503','+504','+505','+506','+507','+508','+509','+590','+591','+592','+593','+594','+595','+596','+597','+598','+670','+672','+673','+674','+675','+676','+677','+678','+679','+680','+681','+682','+683','+685','+686','+687','+688','+689','+690','+691','+692','+850','+852','+853','+855','+856','+880','+886','+960','+961','+962','+963','+964','+965','+966','+967','+968','+970','+971','+972','+973','+974','+975','+976','+977','+992','+993','+994','+995','+996','+998','+1242','+1246','+1264','+1268','+1284','+1340','+1345','+1441','+1473','+1649','+1664','+1670','+1671','+1684','+1721','+1758','+1767','+1784','+1787','+1809','+1829','+1849','+1868','+1869','+1876','+1939','+4779','+5999','+3906698');

    /**
     * name of fields which require manage accounts to be updated
     *
     * @var array list of fields which require manage accounts to be updated
     */
    protected static $_manageAccountsFields = array(
        'email',
        'n_fileas',
        'n_fn',
        'n_given',
        'n_family',
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
     * @return array
     */
    static public function getManageAccountFields()
    {
        return self::$_manageAccountsFields;
    }

    /**
     * returns prefered email address of given contact
     * 
     * @return string
     */
    public function getPreferredEmailAddress()
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
    protected function _resolveAutoValues(array $_data)
    {
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
                break;
            default:
                // normalize telephone numbers
                if (strpos($_name, 'tel_') === 0 && strpos($_name, '_normalized') === false) {
                    parent::__set($_name . '_normalized', (empty($_value)? $_value : static::normalizeTelephoneNoCountry($_value)));
                }
                break;
        }
        
        parent::__set($_name, $_value);
    }

    /**
     * normalizes telephone numbers and removes country part
     * result will be of format 0xxxxxxxxx (only digits)
     *
     * @param  string $telNumber
     * @return string|null
     */
    public static function normalizeTelephoneNoCountry($telNumber)
    {
        $val = trim($telNumber);

        // replace leading + with 00
        if ($val[0] === '+') {
            $val = '00' . mb_substr($val, 1);
        }

        // remove any non digit characters
        $val = preg_replace('/\D+/u', '', $val);

        // if not at least 5 digits, stop where
        if (strlen($val) < 5)
            return null;

        // replace 00 with +
        if ($val[0] === '0' && $val[1] === '0') {
            $val = '+' . mb_substr($val, 2);
        }

        // normalize to remove leading country codes and make the number start with 0
        if ($val[0] === '+') {
            $val = str_replace(static::$countryCodes, '0', $val);
        } elseif($val[0] !== '0') {
            $val = '0' . $val;
        }

        // in case the country codes was not recognized...
        if ($val[0] === '+') {
            $val = '0' . mb_substr($val, 1);
        }

        return $val;
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
     * @param $maxSize
     */
    public function setSmallContactImage($newPhotoBlob, $maxSize = self::SMALL_PHOTO_SIZE)
    {
        if ($this->getId()) {
            try {
                $currentPhoto = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId())->getBlob('image/jpeg', $maxSize);
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
     * @param $maxSize
     *
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     */
    public function getSmallContactImage($maxSize = self::SMALL_PHOTO_SIZE)
    {
        $image = Tinebase_Controller::getInstance()->getImage('Addressbook', $this->getId());
        return $image->getBlob('image/jpeg', $maxSize);
    }

    /**
     * get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->n_fn;
    }
}
