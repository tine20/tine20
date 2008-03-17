<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold contact data
 * 
 * @package     Addressbook
 */
class Addressbook_Model_Contact extends Tinebase_Record_Abstract
{
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
        //'*'                     => 'StringTrim',
        'adr_one_countryname'   => array('StringTrim', 'StringToUpper'),
        'adr_two_countryname'   => array('StringTrim', 'StringToUpper'),
    	'email'         => array('StringTrim', 'StringToLower'),
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
    protected $_validators = array(
        //'contact_created'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        //'contact_creator'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_modified'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        //'contact_modifier'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
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
 /*       'email'     => array(
            array(
                'Regex', 
                '/^[^0-9][a-z0-9_]+([.][a-z0-9_]+)*[@][a-z0-9_]+([.][a-z0-9_]+)*[.][a-z]{2,4}$/'
            ), 
            Zend_Filter_Input::ALLOW_EMPTY => true
        ),
        'contact_email_home'     => array(
            array(
                'Regex', 
                '/^[^0-9][a-z0-9_]+([.][a-z0-9_]+)*[@][a-z0-9_]+([.][a-z0-9_]+)*[.][a-z]{2,4}$/'
            ), 
            Zend_Filter_Input::ALLOW_EMPTY => true
        ),*/
        'email'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_email_home'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'id'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
    	'contact_note'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'owner'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
    	'contact_role'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_title'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url'		=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'contact_url_home'	=> array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_family'		    => array(),
        'n_fileas'          => array(),
        'n_given'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_middle'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_prefix'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'n_suffix'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_name'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'org_unit'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_assistent'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_car'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_cell_private'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_fax_home'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_home'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_pager'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tel_work'          => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'contact_bday'
    );
    
    /**
     * sets the record related properties from user generated input.
     * 
     * Input-filtering and validation by Zend_Filter_Input can enabled and disabled
     *
     * @param array $_data the new data to set
     * @param bool $_bypassFilters enabled/disable validation of data. set to NULL to use state set by the constructor 
     * @throws Tinebase_Record_Exception when content contains invalid or missing data
     */
    public function setFromArray(array $_data)
    {
        if(empty($_data['n_fileas'])) {
            $_data['n_fileas'] = $_data['n_family'];
            if(!empty($_data['n_given'])) {
                $_data['n_fileas'] .= ', ' . $_data['n_given'];
            }
        }
        
        parent::setFromArray($_data);
    }
}