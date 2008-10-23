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
 */

/**
 * backend to handle phones
 *
 * @package  Voipmanager
 */
class Voipmanager_Backend_Snom_Xml extends Voipmanager_Frontend_Snom_Abstract
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
    }
    
    /**
     * get config of one phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return string the config as xml string
     */
    public function getConfig(Voipmanager_Model_SnomPhone $_phone)
    {
        if (!$_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        
        $baseURL = $this->_getBaseUrl();
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><settings></settings>');

        $xmlPhoneSettings = $xml->addChild('phone-settings');
        
        $locationSettings = $this->_getLocationSettings($_phone);
        foreach($locationSettings as $key => $value) {
            $child = $xmlPhoneSettings->addChild($key, $value);
            if($key == 'admin_mode') {
                $child->addAttribute('perm', 'RW');
            } else {
                $child->addAttribute('perm', 'RO');
            }
        }
        
        // reset old dialplan
        $child = $xmlPhoneSettings->addChild('user_dp_str1');
        $child->addAttribute('perm', 'RW');
        // add directory button
        $child = $xmlPhoneSettings->addChild('dkey_directory', 'url ' . $baseURL . '?method=Phone.directory&amp;mac=$mac');
        $child->addAttribute('perm', 'RO');
        // add redirect on/off action url
        $child = $xmlPhoneSettings->addChild('action_redirection_on_url', $baseURL . '?method=Voipmanager.redirect&amp;mac=$mac&amp;event=$redirect_event&amp;number=$redirect_number&amp;time=$redirect_time');
        $child->addAttribute('perm', 'RO');
        $child = $xmlPhoneSettings->addChild('action_redirection_off_url', $baseURL . '?method=Voipmanager.redirect&amp;mac=$mac&amp;event=$redirect_event&amp;number=$redirect_number&amp;time=$redirect_time');
        $child->addAttribute('perm', 'RO');
        // callhistory logging
        $child = $xmlPhoneSettings->addChild('action_incoming_url', $baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=incoming&amp;callId=$call-id&amp;local=$local&amp;remote=$remote');
        $child->addAttribute('perm', 'RO');
        $child = $xmlPhoneSettings->addChild('action_outgoing_url', $baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=outgoing&amp;callId=$call-id&amp;local=$local&amp;remote=$remote');
        $child->addAttribute('perm', 'RO');
        $child = $xmlPhoneSettings->addChild('action_connected_url', $baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=connected&amp;callId=$call-id&amp;local=$local&amp;remote=$remote');
        $child->addAttribute('perm', 'RO');
        $child = $xmlPhoneSettings->addChild('action_disconnected_url', $baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=disconnected&amp;callId=$call-id&amp;local=$local&amp;remote=$remote');
        $child->addAttribute('perm', 'RO');
        $child = $xmlPhoneSettings->addChild('action_missed_url', $baseURL . '?method=Phone.callHistory&amp;mac=$mac&amp;event=missed&amp;callId=$call-id&amp;local=$local&amp;remote=$remote');
        $child->addAttribute('perm', 'RO');
        // disable redundant keys
        $child = $xmlPhoneSettings->addChild('redundant_fkeys', 'off');
        $child->addAttribute('perm', 'RO');
                
        $phoneSettings = $this->_getPhoneSettings($_phone);
        foreach($phoneSettings as $key => $value) {
          $child = $xmlPhoneSettings->addChild($key, $value['value']);
          $child->addAttribute('perm', $value['perms']);
        }
              
        $userSettings = $this->_getUserSettings($_phone);
        foreach($userSettings as $key => $value) {
          $child = $xmlPhoneSettings->addChild($key, $value['value']);
          $child->addAttribute('perm', $value['perms']);
        }
              
        $lines = $this->_getLines($_phone);
        foreach($lines as $lineId => $line) {
            foreach($line as $key => $value) {
                $child = $xmlPhoneSettings->addChild($key, $value);
                $child->addAttribute('idx', $lineId);
                $child->addAttribute('perm', 'RO');
            }
            // reset old dialplan
            $child = $xmlPhoneSettings->addChild('user_dp_str');
            $child->addAttribute('idx', $lineId);
            $child->addAttribute('perm', 'RO');
        }
        
        $guiLanguages = $xml->addChild('gui-languages');
    
        foreach($this->_guiLanguages as $iso => $translated) {
            $child = $guiLanguages->addChild('language');
            $child->addAttribute('url', $locationSettings['base_download_url'] . '/' . $_phone->current_software . '/snomlang/gui_lang_' . $iso . '.xml');
            $child->addAttribute('name', $translated);
        }
      
        $webLanguages = $xml->addChild('web-languages');
    
        foreach($this->_webLanguages as $iso => $translated) {
            $child = $webLanguages->addChild('language');
            $child->addAttribute('url', $locationSettings['base_download_url'] . '/' . $_phone->current_software . '/snomlang/web_lang_' . $iso . '.xml');
            $child->addAttribute('name', $translated);
        }
      
        // Metaways specific dialplan
        $dialPlan = $xml->addChild('dialplan');
    
        $child = $dialPlan->addChild('template');
        $child->addAttribute('match', '[1-4].');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
        $child = $dialPlan->addChild('template');
        $child->addAttribute('match', '5..');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
        $child = $dialPlan->addChild('template');
        $child->addAttribute('match', '[6-8].');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
        $child = $dialPlan->addChild('template');
        $child->addAttribute('match', '9..');
        $child->addAttribute('timeout', 0);
        $child->addAttribute('scheme', 'sip');
        $child->addAttribute('user', 'phone');
        
        return $xml->asXML();
    }
    
    /**
     * get phone firmware
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return string the firmware as xml string
     */
    public function getFirmware(Voipmanager_Model_SnomPhone $_phone)
    {
        if (!$_phone->isValid()) {
            throw new Exception('invalid phone');
        }
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><firmware-settings></firmware-settings>');
        
        $locationSettings = $this->_getLocationSettings($_phone);
        
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones', array())
            ->where(SQL_TABLE_PREFIX . 'snom_phones.macaddress = ?', $_phone->macaddress)
            ->join(SQL_TABLE_PREFIX . 'snom_templates', SQL_TABLE_PREFIX . 'snom_phones.template_id = ' . SQL_TABLE_PREFIX . 'snom_templates.id', array())
            ->join(SQL_TABLE_PREFIX . 'snom_software', SQL_TABLE_PREFIX . 'snom_templates.software_id = ' . SQL_TABLE_PREFIX . 'snom_software.id', array('softwareimage_' . $_phone->current_model));
            
        $firmware = $this->_db->fetchOne($select);
    
        if(!empty($firmware)) {
            $child = $xml->addChild('firmware', $locationSettings['base_download_url'] . '/' . $firmware);
        }
    
        return $xml->asXML();        
    }
    
    /**
     * get general phonesettings like http client username/password and
     * call forward settings
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return array
     */
    protected function _getPhoneSettings(Voipmanager_Model_SnomPhone $_phone)
    {
        $phoneSettings['http_client_user']['value'] = $_phone->http_client_user;
        $phoneSettings['http_client_user']['perms'] = 'RO';
        $phoneSettings['http_client_pass']['value'] = $_phone->http_client_pass;
        $phoneSettings['http_client_pass']['perms'] = 'RO';
        
        /**
         * disabled until snom releases new software image which fixes a bug
         */
        #$phoneSettings['redirect_event']['value'] = $_phone->redirect_event;
        #$phoneSettings['redirect_event']['perms'] = 'RW';
        #$phoneSettings['redirect_number']['value'] = $_phone->redirect_number;
        #$phoneSettings['redirect_number']['perms'] = 'RW';
        #$phoneSettings['redirect_time']['value'] = $_phone->redirect_time;
        #$phoneSettings['redirect_time']['perms'] = 'RW';
        
        return $phoneSettings;
    }
    
    /**
     * get location settings
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return array
     */
    protected function _getLocationSettings(Voipmanager_Model_SnomPhone $_phone)
    {
        $snomLocation = new Voipmanager_Backend_Snom_Location($this->_db);        
        $location = $snomLocation->get($_phone->location_id);
        $locationsSettings = $location->toArray();
                
        unset($locationsSettings['id']);
        unset($locationsSettings['name']);
        unset($locationsSettings['description']);
        unset($locationsSettings['registrar']);
        unset($locationsSettings['base_download_url']);
        
        // see http://wiki.snom.com/Interoperability/Asterisk#Basic_Asterisk_configuration
        $locationsSettings['user_phone'] = 'off';
        $locationsSettings['filter_registrar'] = 'off';
        $locationsSettings['challenge_response'] = 'off';
        $locationsSettings['call_completion'] = 'off';

        $locationsSettings['setting_server'] = $this->_getBaseUrl() . '?method=Voipmanager.settings&amp;mac=' . $_phone->macaddress;
        $locationsSettings['firmware_status'] = $this->_getBaseUrl() . '?method=Voipmanager.firmware&amp;mac=' . $_phone->macaddress;
        
        return $locationsSettings;
    }
    
    /**
     * get user editable settings
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return array
     */
    protected function _getUserSettings(Voipmanager_Model_SnomPhone $_phone)
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
        
        return $userSettings;        
    }
    
    /**
     * return array of phone lines
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return array the lines
     */
    protected function _getLines(Voipmanager_Model_SnomPhone $_phone)
    {
        $asteriskPeer = new Voipmanager_Backend_Asterisk_SipPeer($this->_db);
        $snomLocation = new Voipmanager_Backend_Snom_Location($this->_db);
        
        $lines = array();
        $location = $snomLocation->get($_phone->location_id);
        
        foreach($_phone->lines as $snomLine) {
            $line = array();
            $line['user_active'] = ($snomLine->lineactive == 1 ? 'on' : 'off');
            $line['user_idle_text'] = $snomLine->idletext;
            
            $asteriskLine = $asteriskPeer->get($snomLine->asteriskline_id);
            // remove <xxx> from the end of the line
            $line['user_realname'] = trim(preg_replace('/<\d+>$/', '', $asteriskLine->callerid));
            $line['user_name']     = $asteriskLine->name;
            $line['user_host']     = $location->registrar;
            $line['user_mailbox']  = $asteriskLine->mailbox;
            $line['user_pass']     = $asteriskLine->secret;
            
            $lines[$snomLine->linenumber] = $line;
        }
        
        return $lines;
    }
    
}