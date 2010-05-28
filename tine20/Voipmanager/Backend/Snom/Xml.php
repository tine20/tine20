<?php
/**
 * Tine 2.0
 *
 * @package     Voipmanager Management
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        extend Tinebase_Backend_Sql_Abstract?
 */

/**
 * backend to handle phones
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Xml
{
    protected $_guiLanguages = array(
        'CZ'    => 'Cestina',
        'CA'    => 'Catalan',
        'DE'    => 'Deutsch',
        'DK'    => 'Dansk',
        'EN'    => 'English',
        'FI'    => 'Suomi',
        'FR'    => 'Francais',
        'IT'    => 'Italiano',
        'JP'    => 'Japanese',
        'NL'    => 'Nederlands',
        'NO'    => 'Norsk',
        'PL'    => 'Polski',
        'PR'    => 'Portugues',
        'RU'    => 'Russian',
        'SP'    => 'Espanol',
        'SW'    => 'Svenska',
        'TR'    => 'Turkce',
        'UK'    => 'English(UK)'
    );
    
    protected $_webLanguages = array(
        'CZ'    => 'Cestina',
        'DE'    => 'Deutsch',
        'DK'    => 'Dansk',
        'EN'    => 'English',
        'FI'    => 'Suomi',
        'FR'    => 'Francais',
        'IT'    => 'Italiano',
        'JP'    => 'Japanese',
        'NL'    => 'Nederlands',
        'NO'    => 'Norsk',
        'PR'    => 'Portugues',
        'RU'    => 'Russian',
        'SP'    => 'Espanol',
        'SW'    => 'Svenska',
        'TR'    => 'Turkce',
    );
    
    protected $_baseURL;
    
    /**
     * constructor
     *
     * @param Zend_Db_Adapter_Abstract|optional $_db the database adapter to use
     */
    public function __construct($_db = NULL)
    {
        if($_db instanceof Zend_Db_Adapter_Abstract) {
            $this->_db = $_db;
        } else {
            $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        }
        
        $this->_baseURL = Voipmanager_Frontend_Snom_Abstract::getBaseUrl();
    }
    
    protected function _appendLocationSettings(Voipmanager_Model_Snom_Phone $_phone, SimpleXMLElement $_xml)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " xml " . $_xml->asXML());
        $snomLocation     = new Voipmanager_Backend_Snom_Location($this->_db);        
        $locationSettings = $snomLocation->get($_phone->location_id);
        #if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " localtion_id " . print_r($locationSettings, true));
        $locationSettings = $locationSettings->toArray();
        
        unset($locationSettings['id']);
        unset($locationSettings['name']);
        unset($locationSettings['description']);
        unset($locationSettings['registrar']);
        unset($locationSettings['base_download_url']);
        
        // see http://wiki.snom.com/Interoperability/Asterisk#Basic_Asterisk_configuration
        $locationSettings['user_phone']         = 'off';
        $locationSettings['filter_registrar']   = 'off';
        $locationSettings['challenge_response'] = 'off';
        $locationSettings['call_completion']    = 'off';
        // disable redundant keys
        $locationSettings['redundant_fkeys']    = 'off';
        
        $locationSettings['setting_server']     = Voipmanager_Frontend_Snom_Abstract::getBaseUrl() . '?method=Voipmanager.settings&amp;mac=' . $_phone->macaddress;
        $locationSettings['firmware_status']    = Voipmanager_Frontend_Snom_Abstract::getBaseUrl() . '?method=Voipmanager.firmware&amp;mac=' . $_phone->macaddress;
        
        foreach($locationSettings as $key => $value) {
            $child = $_xml->addChild($key, $value);
            if($key == 'admin_mode') {
                $child->addAttribute('perm', 'RW');
            } else {
                $child->addAttribute('perm', 'RO');
            }
        }
        
        // reset old dialplan
        $child = $_xml->addChild('user_dp_str1');
        $child->addAttribute('perm', 'RW');
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " xml " . $_xml->asXML());
    }
    
    protected function _appendPhoneUrls(Voipmanager_Model_Snom_Phone $_phone, SimpleXMLElement $_xml)
    {
        $locationSettings = array();
                                
        $locationSettings['setting_server']          = $this->_baseURL . '?method=Voipmanager.settings&amp;mac=' . $_phone->macaddress;
        $locationSettings['firmware_status']         = $this->_baseURL . '?method=Voipmanager.firmware&amp;mac=' . $_phone->macaddress;
        
        // add directory button
        $locationSettings['dkey_directory']          = 'url ' . $this->_baseURL . '?method=Phone.directory&amp;mac=$mac';
        
        // not used anymore
        $locationSettings['action_redirection_on_url']  = '';
        $locationSettings['action_redirection_off_url'] = '';
        
        // callhistory logging
        $locationSettings['action_incoming_url']     = $this->_baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=incoming&amp;callId=$call-id&amp;local=$local&amp;remote=$remote';
        $locationSettings['action_outgoing_url']     = $this->_baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=outgoing&amp;callId=$call-id&amp;local=$local&amp;remote=$remote';
        $locationSettings['action_connected_url']    = $this->_baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=connected&amp;callId=$call-id&amp;local=$local&amp;remote=$remote';
        $locationSettings['action_disconnected_url'] = $this->_baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=disconnected&amp;callId=$call-id&amp;local=$local&amp;remote=$remote';
        $locationSettings['action_missed_url']       = $this->_baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=missed&amp;callId=$call-id&amp;local=$local&amp;remote=$remote';
        
        foreach($locationSettings as $key => $value) {
            $child = $_xml->addChild($key, $value);
            $child->addAttribute('perm', 'RO');
        }        
    }
    
    protected function _appendPhoneSettings(Voipmanager_Model_Snom_Phone $_phone, SimpleXMLElement $_xml)
    {
        $phoneSettings['http_client_user']['value'] = $_phone->http_client_user;
        $phoneSettings['http_client_user']['perms'] = 'RO';
        $phoneSettings['http_client_pass']['value'] = $_phone->http_client_pass;
        $phoneSettings['http_client_pass']['perms'] = 'RO';
        
        $phoneSettings['redirect_time']['value'] = 99;
        $phoneSettings['redirect_time']['perms'] = 'RO';
        
        $phoneSettings['redirect_time_on_code']['value'] = '*23';
        $phoneSettings['redirect_time_on_code']['perms'] = 'RO';
        $phoneSettings['redirect_time_off_code']['value'] = '#23';
        $phoneSettings['redirect_time_off_code']['perms'] = 'RO';
        $phoneSettings['redirect_always_on_code']['value'] = '*21';
        $phoneSettings['redirect_always_on_code']['perms'] = 'RO';
        $phoneSettings['redirect_always_off_code']['value'] = '#21';
        $phoneSettings['redirect_always_off_code']['perms'] = 'RO';
        $phoneSettings['redirect_busy_on_code']['value'] = '*22';
        $phoneSettings['redirect_busy_on_code']['perms'] = 'RO';
        $phoneSettings['redirect_busy_off_code']['value'] = '#22';
        $phoneSettings['redirect_busy_off_code']['perms'] = 'RO';
        $phoneSettings['dnd_on_code']['value'] = '*24';
        $phoneSettings['dnd_on_code']['perms'] = 'RO';
        $phoneSettings['dnd_off_code']['value'] = '#24';
        $phoneSettings['dnd_off_code']['perms'] = 'RO';
        
        $phoneSettings['phone_name']['value'] = $_phone->description;
        $phoneSettings['phone_name']['perms'] = 'RO';
        
        $phoneSettings['alert_internal_ring_sound']['value'] = 'Ringer9';
        $phoneSettings['alert_internal_ring_sound']['perms'] = 'RO';
        $phoneSettings['alert_external_ring_sound']['value'] = 'Ringer1';
        $phoneSettings['alert_external_ring_sound']['perms'] = 'RO';
        $phoneSettings['alert_group_ring_sound']['value'] = 'Ringer7';
        $phoneSettings['alert_group_ring_sound']['perms'] = 'RO';

        $phoneSettings['advertisement']['value'] = 'off';
        $phoneSettings['advertisement']['perms'] = 'RO';
        
        foreach($phoneSettings as $key => $value) {
            $child = $_xml->addChild($key, $value['value']);
            $child->addAttribute('perm', $value['perms']);
        }
    }
    
    protected function _appendUserSettings(Voipmanager_Model_Snom_Phone $_phone, SimpleXMLElement $_xml)
    {
        $phoneSettinsgBackend = new Voipmanager_Backend_Snom_PhoneSettings($this->_db);
        $phoneSettings = $phoneSettinsgBackend->get($_phone->getId());
                
        $templateBackend = new Voipmanager_Backend_Snom_Template($this->_db);
        $template = $templateBackend->get($_phone->template_id);
        
        $defaultPhoneSettingsBackend = new Voipmanager_Backend_Snom_Setting($this->_db);
        $defaultPhoneSettings = $defaultPhoneSettingsBackend->get($template->setting_id);

        $userSettings = array();
        
        foreach($phoneSettings AS $key => $value) {
            if($key == 'phone_id') {
                continue;
            }
            
            $isWriteAbleProperty = $key . '_writable';
            
            if($defaultPhoneSettings->$isWriteAbleProperty == true && $value !== NULL) {
                $userSettings[$key]['value'] = $value;
                $userSettings[$key]['perms'] = 'RW';
            } elseif($defaultPhoneSettings->$key !== NULL) {
                $userSettings[$key]['value'] = $defaultPhoneSettings->$key;
                if($defaultPhoneSettings->$isWriteAbleProperty == true) {
                    $userSettings[$key]['perms'] = 'RW';
                } else {
                    $userSettings[$key]['perms'] = 'RO';
                }
            }            
        }
        
        foreach($userSettings as $key => $value) {
            $child = $_xml->addChild($key, $value['value']);
            $child->addAttribute('perm', $value['perms']);
        }
    }
    
    protected function _appendPhoneLines(Voipmanager_Model_Snom_Phone $_phone, SimpleXMLElement $_xml)
    {
        $asteriskPeer = new Voipmanager_Backend_Asterisk_SipPeer($this->_db);
        $snomLocation = new Voipmanager_Backend_Snom_Location($this->_db);
        
        $lines = array();
        $location = $snomLocation->get($_phone->location_id);
        
        foreach($_phone->lines as $snomLine) {
            $line = array();
            $line['user_active']    = ($snomLine->lineactive == 1 ? 'on' : 'off');
            $line['user_idle_text'] = $snomLine->idletext;
            
            $asteriskLine = $asteriskPeer->get($snomLine->asteriskline_id);
            // remove <some tag> from the end of the line
            $line['user_realname'] = trim(preg_replace('/<\d+>$/', '', $asteriskLine->callerid));
            $line['user_name']     = $asteriskLine->name;
            $line['user_host']     = $location->registrar;
            $line['user_mailbox']  = $asteriskLine->mailbox;
            $line['user_pass']     = $asteriskLine->secret;
            $line['user_server_type'] = 'asterisk';
            
            $lines[$snomLine->linenumber] = $line;
        }
        
        foreach($lines as $lineId => $line) {
            foreach($line as $key => $value) {
                $child = $_xml->addChild($key, $value);
                $child->addAttribute('idx', $lineId);
                $child->addAttribute('perm', 'RO');
            }
            // reset old dialplan
            $child = $_xml->addChild('user_dp_str');
            $child->addAttribute('idx', $lineId);
            $child->addAttribute('perm', 'RO');
        }
        
    }
    
    protected function _appendDialPlan(Voipmanager_Model_Snom_Phone $_phone, SimpleXMLElement $_xml)
    {
        // Metaways specific dialplan
        
        $child = $_xml->addChild('template');
        $child->addAttribute('match', '[1-4].');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
        $child = $_xml->addChild('template');
        $child->addAttribute('match', '5..');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
        $child = $_xml->addChild('template');
        $child->addAttribute('match', '[6-8].');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
        $child = $_xml->addChild('template');
        $child->addAttribute('match', '9..');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
    }
    
    /**
     * get config of one phone
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     * @return string the config as xml string
     * @throws  Voipmanager_Exception_Validation
     */
    public function getConfig(Voipmanager_Model_Snom_Phone $_phone)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " phone " . print_r($_phone->toArray(), true));
        
        if (!$_phone->isValid()) {
            throw new Voipmanager_Exception_Validation('invalid phone');
        }
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><settings></settings>');
        
        $phoneSettings = $xml->addChild('phone-settings');
        
        $this->_appendLocationSettings($_phone, $phoneSettings);

        $this->_appendPhoneUrls($_phone, $phoneSettings);
        $this->_appendPhoneSettings($_phone, $phoneSettings);
        $this->_appendPhoneLines($_phone, $phoneSettings);
        
        $this->_appendUserSettings($_phone, $phoneSettings);
        
        // append available languages
        $snomLocation = new Voipmanager_Backend_Snom_Location($this->_db);
        $locationSettings = $snomLocation->get($_phone->location_id);
        
        $guiLanguages = $xml->addChild('gui-languages');
        foreach($this->_guiLanguages as $iso => $translated) {
            $child = $guiLanguages->addChild('language');
            $child->addAttribute('url', $locationSettings->base_download_url . '/' . $_phone->current_software . '/snomlang/gui_lang_' . $iso . '.xml');
            $child->addAttribute('name', $translated);
        }
      
        $webLanguages = $xml->addChild('web-languages');
        foreach($this->_webLanguages as $iso => $translated) {
            $child = $webLanguages->addChild('language');
            $child->addAttribute('url', $locationSettings->base_download_url . '/' . $_phone->current_software . '/snomlang/web_lang_' . $iso . '.xml');
            $child->addAttribute('name', $translated);
        }
      
        // append dialplann
        $dialPlan = $xml->addChild('dialplan');
        $this->_appendDialPlan($_phone, $dialPlan);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " xml " . $xml->asXML());
        
        return $xml->asXML();
    }
    
    /**
     * get phone firmware
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     * @return string the firmware as xml string
     * @throws  Voipmanager_Exception_Validation
     */
    public function getFirmware(Voipmanager_Model_Snom_Phone $_phone)
    {
        if (!$_phone->isValid()) {
            throw new Voipmanager_Exception_Validation('invalid phone');
        }
        
        $snomLocation = new Voipmanager_Backend_Snom_Location($this->_db);
        $locationSettings = $snomLocation->get($_phone->location_id);
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><firmware-settings></firmware-settings>');
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones', array())
            ->where($this->_db->quoteIdentifier(SQL_TABLE_PREFIX . 'snom_phones.macaddress') . ' = ?', $_phone->macaddress)
            ->join(SQL_TABLE_PREFIX . 'snom_templates', SQL_TABLE_PREFIX . 'snom_phones.template_id = ' . SQL_TABLE_PREFIX . 'snom_templates.id', array())
            ->join(SQL_TABLE_PREFIX . 'snom_software', SQL_TABLE_PREFIX . 'snom_templates.software_id = ' . SQL_TABLE_PREFIX . 'snom_software.id', array('softwareimage_' . $_phone->current_model));
            
        $firmware = $this->_db->fetchOne($select);
    
        if(!empty($firmware)) {
            $child = $xml->addChild('firmware', $locationSettings->base_download_url . '/' . $firmware);
        }
    
        return $xml->asXML();        
    }    
}