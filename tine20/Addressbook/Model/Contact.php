<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold contact data
 * 
 * @property	account_id	id of associated user
 * @property	email		the email address of the contact
 * @property	n_family
 * @property	n_fileas 	display name
 * @property	n_fn		the full name
 * @property	n_given		
 * @property	type		type of contact
 * @package     Addressbook
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
        'n_fileas'              => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
        'n_fn'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'),
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
    // tine 2.0 generic fields
        'tags'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'notes'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'relations'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'customfields'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => array()),
        'type'                  => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTACTTYPE_CONTACT,
            'InArray' => array(self::CONTACTTYPE_USER, self::CONTACTTYPE_CONTACT)
        )
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
        if (! array_key_exists('org_name', $_data)) {
            $_data['org_name'] = '';
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
     * additional validation
     *
     * @param $_throwExceptionOnInvalidData
     * @return bool
     * @throws Tinebase_Exception_Record_Validation
     */
    
    
    function isValid($_throwExceptionOnInvalidData = false) {
        
        if ((!$this->__get('org_name')) && (!$this->__get('n_family'))) {

            array_push($this->_validationErrors, array('id' => 'org_name', 'msg' => 'either "org_name" or "n_family" must be given!'));
            array_push($this->_validationErrors, array('id' => 'n_family', 'msg' => 'either "org_name" or "n_family" must be given!'));
            
            $valid = false;

        } else {
            $valid = true;
        }
        
        $parentException = false;
        $parentValid = false;
        
        try {
            $parentValid = parent::isValid($_throwExceptionOnInvalidData);
        } catch (Tinebase_Exception_Record_Validation $e) {
            $parentException = $e;
        }
        
        if ($_throwExceptionOnInvalidData && (!$valid || !$parentValid)) {
            
            if(!$valid) {
                $message = 'either "org_name" or "n_family" must be given!';    
            }
            
            if($parentException) $message .= ', ' . $parentException->getMessage();
            $e = new Tinebase_Exception_Record_Validation($message);
            if(!$valid) Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ":\n" . print_r($this->_validationErrors,true). $e);
            throw $e;
        }
        
        return $parentValid && $valid;        
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
}
