<?php
/**
 * Snom_Phone controller for Voipmanager Management application
 *
 * @package     Voipmanager
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * holds the instance of the singleton
     *
     * @var Voipmanager_Controller_Snom_Phone
     */
    private static $_instance = NULL;
    
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
        $this->_modelName   = 'Voipmanager_Model_Snom_Phone';
        $this->_backend     = new Voipmanager_Backend_Snom_Phone();
    }
        
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
            
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller_Snom_Phone
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_Snom_Phone();
        }
        
        return self::$_instance;
    }
    
    /**
     * get snom_phone by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Snom_Phone
     */
    public function get($_id)
    {
        $phone = $this->_backend->get($_id);
        $this->_resolveRightsAndLines($phone);
        
        return $phone;
    }
    
    /**
     * get snom_phone by macAddress
     *
     * @param string $_macAddress
     * @return Voipmanager_Model_Snom_Phone
     */
    public function getByMacAddress($_macAddress)
    {
        $phone = $this->_backend->getByMacAddress($_macAddress);
        $this->_resolveRightsAndLines($phone);
        
        return $phone;
    }

    /**
     * add one phone
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     * @return  Voipmanager_Model_Snom_Phone
     * @throws Voipmanager_Exception_Validation
     * 
     * @todo do not overwrite create() -> use inspectBefore/After functions
     */
    public function create(Tinebase_Record_Interface $_phone)
    {
        if ($_phone->has('macaddress')) {
            // check first if mac address is already used
            try {
                $this->getByMacAddress($_phone->macaddress);
                throw new Voipmanager_Exception_Validation('A phone with this mac address already exists.');
            } catch (Voipmanager_Exception_NotFound $venf) {
                // everything ok
            }
        }
        
        // auto generate random http client username and password
        // limit length because of Snom phone limitations
        $_phone->http_client_user = Tinebase_Record_Abstract::generateUID(30);
        $_phone->http_client_pass = Tinebase_Record_Abstract::generateUID(20);
        $_phone->http_client_info_sent = 0;
        
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);

            $phone = $this->_backend->create($_phone);
            
            // force the right phone_id
            $_phoneSettings = $_phone->settings;
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
            
            $phoneSettings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->create($_phoneSettings);
            
            $this->_createLines($phone, $_phone->lines);
          
            // save phone rights
            if (isset($phone->rights)) {
                $this->_backend->setPhoneRights($phone);
            }
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }
        
        return $this->get($phone->getId());
    }
    
    /**
     * set redirect settings only
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     */
    public function updateRedirect(Voipmanager_Model_Snom_Phone $_phone)
    {
        $this->_backend->updateRedirect($_phone);
    }
    
    /**
     * update one phone
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     * @param Voipmanager_Model_Snom_PhoneSettings|optional $_phoneSettings
     * @return Voipmanager_Model_Snom_Phone
     * @throws Voipmanager_Exception_Validation
     * 
     * @todo do not overwrite update() -> use inspectBefore/After functions
     */
    public function update(Tinebase_Record_Interface $_phone)
    {
        // check first if mac address is already used
        if ($_phone->has('macaddress')) {
            try {
                $phoneWithMac = $this->getByMacAddress($_phone->macaddress);
                if ($phoneWithMac->getId() !== $_phone->getId()) {
                    throw new Voipmanager_Exception_Validation('A phone with this mac address already exists.');
                }
            } catch (Voipmanager_Exception_NotFound $venf) {
                // everything ok
            }
        }
        
        try {
            $db = $this->_backend->getAdapter();
            $transactionId = Tinebase_TransactionManager::getInstance()->startTransaction($db);
            
            $phone = $this->_backend->update($_phone);
            $_phoneSettings = $_phone->settings;
            
            if ($_phoneSettings instanceof Voipmanager_Model_Snom_PhoneSettings) {
                // force the right phone_id
                $_phoneSettings->setId($phone->getId());
        
                // set all settings which are equal to the default settings to NULL
                $template = Voipmanager_Controller_Snom_Template::getInstance()->get($phone->template_id);
                $settingDefaults = Voipmanager_Controller_Snom_Setting::getInstance()->get($template->setting_id);
        
                foreach ($_phoneSettings->toArray() as $key => $value) {
                    if ($key == 'phone_id') {
                        continue;
                    }
                    if ($settingDefaults->$key == $value) {
                        $_phoneSettings->$key = NULL;
                    }
                }
                
                if (Voipmanager_Controller_Snom_PhoneSettings::getInstance()->get($phone->getId())) {
                    $phoneSettings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->update($_phoneSettings);
                } else {
                    $phoneSettings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->create($_phoneSettings);
                }
            }
            
            Voipmanager_Controller_Snom_Line::getInstance()->deletePhoneLines($phone->getId());
            $this->_createLines($phone, $_phone->lines);
            
            // save phone rights
            if (isset($_phone->rights)) {
                $this->_backend->setPhoneRights($_phone);
            }
            
            Tinebase_TransactionManager::getInstance()->commitTransaction($transactionId);
            
        } catch (Exception $e) {
            $this->_handleRecordCreateOrUpdateException($e);
        }
        
        return $this->get($phone->getId());
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
            $phone = $this->get($id);
            $phone->http_client_user = Tinebase_Record_Abstract::generateUID(30);
            $phone->http_client_pass = Tinebase_Record_Abstract::generateUID(20);
            $phone->http_client_info_sent = 0;
            
            $phone = $this->_backend->update($phone);
        }
    }
    
    /**
     * resolve phone rights and lines
     * 
     * @param Voipmanager_Model_Snom_Phone|Phone_Model_MyPhone $_phone
     * @return void
     */
    protected function _resolveRightsAndLines($_phone)
    {
        $filter = new Voipmanager_Model_Snom_LineFilter(array(
            array('field' => 'snomphone_id', 'operator' => 'equals', 'value' => $_phone->id)
        ));
        $_phone->lines  = Voipmanager_Controller_Snom_Line::getInstance()->search($filter);
        
        if ($_phone instanceof Voipmanager_Model_Snom_Phone) {
            $_phone->rights = $this->_backend->getPhoneRights($_phone->id);
            // add accountDisplayName
            foreach ($_phone->rights as &$right) {
                $user = Tinebase_User::getInstance()->getUserById($right->account_id);
                $right->account_name = $user->accountDisplayName;
            }
        }
    }
    
    /**
     * create lines / sippeers
     * 
     * @param Voipmanager_Model_Snom_Phone|Phone_Model_MyPhone $_phone
     * @param array $_lines
     * @return void
     */
    protected function _createLines($_phone, $_lines)
    {
        foreach($_lines as $line) {
            $line->snomphone_id = $_phone->getId();
            if (is_array($line->asteriskline_id)) {
                $sippeer = new Voipmanager_Model_Asterisk_SipPeer($line->asteriskline_id);
                $sippeer = Voipmanager_Controller_Asterisk_SipPeer::getInstance()->update($sippeer);
                $line->asteriskline_id = $sippeer->getId();
            }
            
            $addedLine = Voipmanager_Controller_Snom_Line::getInstance()->create($line);
        }
    }
}
