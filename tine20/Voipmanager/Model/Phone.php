<?php
/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Product.php 2531 2008-05-18 07:52:12Z nelius_weiss $
 *
 */

/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_Phone extends Tinebase_Record_Abstract
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
        'id' 			        => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'macaddress'            => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'description'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'location_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'template_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => false, 'presence' => 'required'),
        'ipaddress'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'last_modified_time'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'current_software'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'current_model'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'settings_loaded_at'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'firmware_checked_at'   => array(Zend_Filter_Input::ALLOW_EMPTY => true)
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
     * converts a int, string or Voipmanager_Model_Phone to an phone id
     *
     * @param int|string|Voipmanager_Model_Phone $_phoneId the phone id to convert
     * @return int
     */
    static public function convertPhoneIdToInt($_phoneId)
    {
        if ($_phoneId instanceof Voipmanager_Model_Phone) {
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