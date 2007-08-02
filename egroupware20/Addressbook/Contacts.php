<?php
/**
 * factory class for the addressbook
 * 
 * a instance of the addressbook backendclass should be created using this class
 * 
 * $contacts = Addressbook_Contacts::factory($nameOfTheBackendClass);
 * 
 * @package Addressbook
 *
 */
class Addressbook_Contacts
{
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected static $filters = array(
        '*'                     => 'StringTrim',
        'contact_email'         => array('StringTrim', 'StringToLower'),
        'contact_email_home'    => array('StringTrim', 'StringToLower'),
        'contact_url'           => array('StringTrim', 'StringToLower'),
        'contact_url_home'      => array('StringTrim', 'StringToLower'),
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected static $validators = array(
        'adr_one_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_region'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_one_street2'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_countryname'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_locality'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_postalcode'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_region'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'adr_two_street2'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_assistent'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_bday'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_email'		=> array('EmailAddress', Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_email_home'	=> array('EmailAddress', Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_note'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_role'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_room'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_title'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url_home'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_family'		=> array(),
        'n_given'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_middle'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_prefix'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_suffix'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_name'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_unit'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_assistent'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_car'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_private'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_home'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_pager'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_work'              => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Sql';
    
    /**
     * constant for LDAP contacts backend class
     *
     */
    const LDAP = 'Ldap';
    
    /**
     * factory function to return a selected contacts backend class
     *
     * @param string $type
     * @return object
     */
    static public function factory($type)
    {
        switch($type) {
            case self::SQL:
            case self::LDAP:
                $className = Addressbook_Contacts_.$type;
                $instance = new $className();
                break;
                
            default:
                throw new Exception('unknown type');
        }
        
        return $instance;
    }
    
    /**
     * returns list of input filter for contacts
     *
     * @return array
     */
    static public function getFilter()
    {
        return self::$filters;
    }

    /**
     * returns list of input validator for contacts
     *
     * @return array
     */
    static public function getValidator()
    {
        return self::$validators;
    }
}    
