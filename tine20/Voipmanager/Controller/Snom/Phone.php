<?php
/**
 * Snom_Phone controller for Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Snom_Phone controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_Snom_Phone extends Voipmanager_Controller_Abstract
{
    /**
     * Voipmanager backend class
     *
     * @var Voipmanager_Backend_Snom_Phone
     */
    protected $_backend;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_backend      = new Voipmanager_Backend_Snom_Phone($this->_getDatabaseBackend());
    }
        
    /**
     * holdes the instance of the singleton
     *
     * @var Voipmanager_Controller_Snom_Phone
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller_Snom_Phone
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_Snom_Phone;
        }
        
        return self::$_instance;
    }
    
    /**
     * get snom_phone by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
     */
    public function get($_id)
    {
        $phone = $this->_backend->get($_id);
        
        $filter = new Voipmanager_Model_SnomLineFilter(array(
            'snomphone_id'  => $phone->id
        ));
        $phone->lines  = Voipmanager_Controller_Snom_Line::getInstance()->search($filter);
        $phone->rights = $this->_backend->getPhoneRights($phone->id);
        
        // add accountDisplayName
        foreach ($phone->rights as &$right) {
            $user = Tinebase_User::getInstance()->getUserById($right->account_id);
            $right->accountDisplayName = $user->accountDisplayName;
        }
        
        return $phone;    
    }
    
    /**
     * get snom_phone by macAddress
     *
     * @param string $_macAddress
     * @return Voipmanager_Model_SnomPhone
     */
    public function getByMacAddress($_macAddress)
    {
        $phone = $this->_backend->getByMacAddress($_macAddress);
        
        $filter = new Voipmanager_Model_SnomLineFilter(array(
            'snomphone_id'  => $phone->id
        ));
        $phone->lines  = Voipmanager_Controller_Snom_Line::getInstance()->search($filter);
        $phone->rights = $this->_backend->getPhoneRights($phone->id);
        
        // add accountDisplayName
        foreach ($phone->rights as &$right) {
            $user = Tinebase_User::getInstance()->getUserById($right->account_id);
            $right->accountDisplayName = $user->accountDisplayName;
        }
        
        return $phone;    
    }

    /**
     * add one phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return  Voipmanager_Model_SnomPhone
     */
    public function create(Voipmanager_Model_SnomPhone $_phone, Voipmanager_Model_SnomPhoneSettings $_phoneSettings)
    {
        // auto generate random http client username and password        
        // limit length because of Snom phone limitations
        $_phone->http_client_user = Tinebase_Record_Abstract::generateUID(30);
        $_phone->http_client_pass = Tinebase_Record_Abstract::generateUID(20);
        $_phone->http_client_info_sent = false;
        
        unset($_phone->settings_loaded_at);
        unset($_phone->firmware_checked_at);
        unset($_phone->last_modified_time);
        unset($_phone->ipaddress);
        unset($_phone->current_software);
        
        $phone = $this->_backend->create($_phone);
        
        // force the right phone_id
        $_phoneSettings->setId($phone->getId());

        // set all settings which are equal to the default settings to NULL
        $template = Voipmanager_Controller_Snom_Template::getInstance()->get($phone->template_id);
        $settingDefaults = Voipmanager_Controller_Snom_Setting::getInstance()->get($template->setting_id);

        foreach($_phoneSettings AS $key => $value) {
            if($key == 'phone_id') {
                continue;
            }
            if($_phoneSettings->$key == $settingDefaults->$key) {
                $_phoneSettings->$key = NULL;
            }    
        }
                
        $phoneSettings = $this->_snomPhoneSettingsBackend->create($_phoneSettings);
        
        foreach($_phone->lines as $line) {
            $line->snomphone_id = $phone->getId();
            $addedLine = Voipmanager_Controller_Snom_Line::getInstance()->create($line);
        }
        
        // save phone rights
        if (isset($phone->rights)) {
            $this->_backend->setPhoneRights($phone);
        }        
      
        return $this->get($phone);
    }
    
    /**
     * set redirect settings only
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     */
    public function updateRedirect(Voipmanager_Model_SnomPhone $_phone)
    {
        $this->_backend->updateRedirect($_phone);
    }
    
    /**
     * update one phone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @param Voipmanager_Model_SnomPhoneSettings|optional $_phoneSettings
     * @return  Voipmanager_Model_SnomPhone
     */
    public function update(Voipmanager_Model_SnomPhone $_phone, $_phoneSettings = NULL)
    {
        unset($_phone->settings_loaded_at);
        unset($_phone->firmware_checked_at);
        unset($_phone->last_modified_time);
        unset($_phone->ipaddress);
        unset($_phone->current_software);
        
        $phone = $this->_backend->update($_phone);
        
        if($_phoneSettings instanceof Voipmanager_Model_SnomPhoneSettings) {
        
            // force the right phone_id
            $_phoneSettings->setId($phone->getId());
    
            // set all settings which are equal to the default settings to NULL
            $template = Voipmanager_Controller_Snom_Template::getInstance()->get($phone->template_id);
            $settingDefaults = Voipmanager_Controller_Snom_Setting::getInstance()->get($template->setting_id);
    
            foreach($_phoneSettings AS $key => $value) {
                if($key == 'phone_id') {
                    continue;
                }
                if($_phoneSettings->$key == $settingDefaults->$key) {
                    $_phoneSettings->$key = NULL;
                }    
            }

            if($this->_snomPhoneSettingsBackend->get($phone->getId())) {
                $phoneSettings = Voipmanager_Controller_Snom_Setting::getInstance()->update($_phoneSettings);
            } else {
                $phoneSettings = Voipmanager_Controller_Snom_Setting::getInstance()->create($_phoneSettings);            
            }
        
        }
        
        Voipmanager_Controller_Snom_Line::getInstance()->deletePhoneLines($phone->getId());
        
        foreach($_phone->lines as $line) {
            $line->snomphone_id = $phone->getId();
            $addedLine = Voipmanager_Controller_Snom_Line::getInstance()->createe($line);
        }
        
        // save phone rights
        if (isset($_phone->rights)) {
            $this->_backend->setPhoneRights($_phone);
        }
              
        return $this->get($phone);
    }    
    
    /**
     * send http client info to a set of phones.
     * 
     * @param array array of phone identifiers
     * @return void
     */
    public function resetHttpClientInfo($_identifiers)
    {
        if(!is_array($_identifiers) || !is_object($_identifiers)) {
            $_identifiers = (array)$_identifiers;
        }
        foreach ($_identifiers as $id) {
            $phone = $this->getSnomPhone($id);
            $phone->http_client_user = Tinebase_Record_Abstract::generateUID(30);
            $phone->http_client_pass = Tinebase_Record_Abstract::generateUID(20);
            $phone->http_client_info_sent = false;
            
            $phone = $this->_backend->update($phone);
        }
    }
}
