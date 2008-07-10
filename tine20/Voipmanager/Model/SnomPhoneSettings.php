<?php
/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: SnomPhone.php 3245 2008-07-09 07:12:42Z lkneschke $
 *
 */

/**
 * class to hold phone data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_SnomPhoneSettings extends Tinebase_Record_Abstract
{
    /**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'phone_id';
    
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
        'phone_id'              => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'web_language'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'language'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'display_method'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'mwi_notification'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'mwi_dialtone'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'headset_device'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'message_led_other'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'global_missed_counter' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'scroll_outgoing'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'show_local_line'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'show_call_status'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
	    'call_waiting'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_event'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_number'       => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_time'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    
    /**
     * converts a int, string or Voipmanager_Model_SnomPhoneSetting to an phoneSetting id
     *
     * @param int|string|Voipmanager_Model_SnomPhone $_phoneSettingId the phone id to convert
     * @return int
     */
    static public function convertSnomPhoneSettingsIdToInt($_phoneSettingsId)
    {
        if ($_phoneSettingsId instanceof Voipmanager_Model_SnomPhoneSettings) {
            if (empty($_phoneSettingsId->setting_id)) {
                throw new Exception('no phoneSettings id set');
            }
            $id = (string) $_phoneSettingsId->setting_id;
        } else {
            $id = (string) $_phoneSettingsId;
        }
        
        if ($id == '') {
            throw new Exception('phoneSettings id can not be 0');
        }

        return $id;
    }

}