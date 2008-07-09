<?php
/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_SnomPhone extends Tinebase_Record_Abstract
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
        'macaddress'            => 'StringTrim'
    );
    
    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id' 			        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'macaddress'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'location_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'template_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'settings_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ipaddress'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'current_software'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'current_model'         => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'settings_loaded_at'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'firmware_checked_at'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'lines'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'redirect_event'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'redirect_number'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'redirect_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'http_client_info_sent' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'http_client_user'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'http_client_pass'      => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'last_modified_time',
        'settings_loaded_at',
        'firmware_checked_at'
    );
    
    /**
     * converts a int, string or Voipmanager_Model_SnomPhone to an phone id
     *
     * @param int|string|Voipmanager_Model_SnomPhone $_phoneId the phone id to convert
     * @return int
     */
    static public function convertSnomPhoneIdToInt($_phoneId)
    {
        if ($_phoneId instanceof Voipmanager_Model_SnomPhone) {
            if (empty($_phoneId->id)) {
                throw new Exception('no phone id set');
            }
            $id = (string) $_phoneId->id;
        } else {
            $id = (string) $_phoneId;
        }
        
        if ($id == '') {
            throw new Exception('phone id can not be 0');
        }

        return $id;
    }

}