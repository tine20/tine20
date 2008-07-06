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
    
    public function __construct()
    {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }
    
    public function getConfig(Voipmanager_Model_SnomPhone $_phone)
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><settings></settings>');

        $phonesettings = $xml->addChild('phone-settings');
        
        $locationSettings = $this->_getLocationSettings($_phone);
        foreach($locationSettings as $key => $value) {
            $child = $phonesettings->addChild($key, $value);
            if($key == 'admin_mode') {
                $child->addAttribute('perm', 'RW');
            } else {
                $child->addAttribute('perm', 'RO');
            }
        }
        
        // set the microphone volume temporarly and other things
        // get removed if we have the settings dialogue
        $child = $phonesettings->addChild('vol_handset_mic', 7);
        $child->addAttribute('perm', 'RW');
        $child = $phonesettings->addChild('web_language', 'Deutsch');
        $child->addAttribute('perm', 'RW');
        $child = $phonesettings->addChild('language', 'Deutsch');
        $child->addAttribute('perm', 'RW');
        $child = $phonesettings->addChild('tone_scheme', 'GER');
        $child->addAttribute('perm', 'RW');
        $child = $phonesettings->addChild('display_method', 'Name+Number');
        $child->addAttribute('perm', 'RW');
        $child = $phonesettings->addChild('date_us_format', 'off');
        $child->addAttribute('perm', 'RW');
        $child = $phonesettings->addChild('time_24_format', 'on');
        $child->addAttribute('perm', 'RW');
/*        
        $userSettings = $this->_getUserSettings();
        foreach($userSettings as $key => $value) {
          $child = $phonesettings->addChild($key, $value);
          $child->addAttribute('perm', 'RW');
        }
  */      
        $lines = $this->_getLines($_phone);
        foreach($lines as $lineId => $line) {
            foreach($line as $key => $value) {
                $child = $phonesettings->addChild($key, $value);
                $child->addAttribute('idx', $lineId);
                $child->addAttribute('perm', 'RW');
            }
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
        $child->addAttribute('match', '[1-8].');
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
    
    protected function _getLocationSettings(Voipmanager_Model_SnomPhone $_phone)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones', array())
            ->where(SQL_TABLE_PREFIX . 'snom_phones.macaddress = ?', $_phone->macaddress)
            ->join(SQL_TABLE_PREFIX . 'snom_location', SQL_TABLE_PREFIX . 'snom_phones.location_id = ' . SQL_TABLE_PREFIX . 'snom_location.id');
        
        $locationsSettings = $this->_db->fetchRow($select);
        
        unset($locationsSettings['id']);
        unset($locationsSettings['name']);
        unset($locationsSettings['description']);
        
        $locationsSettings['firmware_status'] .= '?mac=' . $_phone->macaddress;
        
        return $locationsSettings;
    }
    
    protected function _getLines(Voipmanager_Model_SnomPhone $_phone)
    {
        $select = $this->_db->select()
            ->from(SQL_TABLE_PREFIX . 'snom_phones', array())
            ->where(SQL_TABLE_PREFIX . 'snom_phones.macaddress = ?', $_phone->macaddress)
            ->join(SQL_TABLE_PREFIX . 'snom_lines', SQL_TABLE_PREFIX . 'snom_phones.id = ' . SQL_TABLE_PREFIX . 'snom_lines.snomphone_id')
            ->join(SQL_TABLE_PREFIX . 'asterisk_sip_peers', SQL_TABLE_PREFIX . 'asterisk_sip_peers.id = ' . SQL_TABLE_PREFIX . 'snom_lines.asteriskline_id', array('name', 'secret', 'mailbox', 'callerid'));

        $rows = $this->_db->fetchAssoc($select);
        
        $lines = array();
        
        foreach($rows as $row) {
            $line = array();
            $line['user_active'] = ($row['lineactive'] == 1 ? 'on' : 'off');
            // remove <xxx> from the end of the line
            $line['user_realname'] = trim(preg_replace('/<\d+>$/', '', $row['callerid']));
            $line['user_idle_text'] = $row['idletext'];
            $line['user_name'] = $row['name'];
            $line['user_host'] = 'hh.metaways.de';
            $line['user_mailbox'] = $row['mailbox'];
            $line['user_pass'] = $row['secret'];
            
            $lines[$row['linenumber']] = $line;
        }
        #echo "<pre>";
        #var_dump($lines);
        
        return $lines;
    }
    
}