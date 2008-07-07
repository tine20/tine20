<?php
/**
 * class to hold snom setting data
 * 
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Thomas Wadewitz <t.wadewitz@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:  $
 *
 */

/**
 * class to hold snom setting data
 * 
 * @package     Voipmanager Management
 */
class Voipmanager_Model_SnomSetting extends Tinebase_Record_Abstract
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
        'id'						=> array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => NULL),
        'name'                      => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'description'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'web_language'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'language'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'display_method'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_notification'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'mwi_dialtone'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'headset_device'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'message_led_other'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'global_missed_counter'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),   
        'scroll_outgoing'           => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'show_local_line'           => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'show_call_status'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'dnd_on_code'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'dnd_off_code'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'ringer_headset_device'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'ring_sound'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'alert_internal_ring_text'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'alert_internal_ring_sound' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'alert_external_ring_text'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'alert_external_ring_sound' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'alert_group_ring_text'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'alert_group_ring_sound'    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'friends_ring_sound'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
        'family_ring_sound'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'colleagues_ring_sound'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),   
        'vip_ring_sound'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'custom_melody_url'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'auto_connect_indication'   => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'auto_connect_type'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'privacy_out'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'privacy_in'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'presence_timeout'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'enable_keyboard_lock'      => array(Zend_Filter_Input::ALLOW_EMPTY => true),  
        'keyboard_lock'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'keyboard_lock_pw'          => array(Zend_Filter_Input::ALLOW_EMPTY => true),   
        'keyboard_lock_emergency'   => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'emergency_proxy'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'call_waiting'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'web_language_writable'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'language_writable'         => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'display_method_writable'   => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'call_waiting_writable'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'mwi_notification_writable' => array(Zend_Filter_Input::ALLOW_EMPTY => true),   
        'mwi_dialtone_writable'     => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'headset_device_writable'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'message_led_other_writable' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'global_missed_counter_writable' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'scroll_outgoing_writable'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_local_line_writable'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'show_call_status_writable' => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_event_writable'   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_number_writable'  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'redirect_time_writable'    => array(Zend_Filter_Input::ALLOW_EMPTY => true)
    );

    /**
     * converts a int, string or Voipmanager_Model_Setting to an setting id
     *
     * @param int|string|Voipmanager_Model_Setting $_settingId the setting id to convert
     * @return int
     */
    static public function convertSnomSettingIdToInt($_settingId)
    {
        if ($_settingId instanceof Voipmanager_Model_SnomSetting) {
            if (empty($_settingId->id)) {
                throw new Exception('no setting id set');
            }
            $id = (string) $_settingId->id;
        } else {
            $id = (string) $_settingId;
        }
        
        if ($id == '') {
            throw new Exception('setting id can not be 0');
        }
        
        return $id;
    }

}