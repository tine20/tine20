<?php
/**
 * class to hold snom location data
 * 
 * @package     Voipmanager
 * @subpackage    Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * class to hold snom location data
 * 
 * @package     Voipmanager
 * @subpackage    Model
 */
class Voipmanager_Model_Snom_Location extends Tinebase_Record_Abstract
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
    protected $_application = 'Voipmanager';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_filters = array(
        '*'                     => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'name'                  => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'firmware_interval'     => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        #'firmware_status'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'update_policy'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        #'setting_server'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'registrar'             => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'base_download_url'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'admin_mode'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'admin_mode_password'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ntp_server'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ntp_refresh'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'timezone'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'webserver_type'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'http_port'             => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'https_port'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'http_user'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'http_pass'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'tone_scheme'           => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'GER'),
        'date_us_format'        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
        'time_24_format'        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0)
    );
    
    /**
     * overwrite constructor to add more filters
     *
     * @param mixed $_data
     * @param bool $_bypassFilters
     * @param mixed $_convertDates
     * @return void
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = true)
    {
        // set default value if field is empty
        $this->_filters['admin_mode'] = new Zend_Filter_Empty('false');
        $this->_filters['webserver_Type'] = new Zend_Filter_Empty('https');
        $this->_filters['ntp_refresh'] = new Zend_Filter_Empty(0);
        $this->_filters['http_port'] = new Zend_Filter_Empty(0);
        $this->_filters['https_port'] = new Zend_Filter_Empty(0);
        
        parent::__construct($_data, $_bypassFilters, $_convertDates);
    }
}
