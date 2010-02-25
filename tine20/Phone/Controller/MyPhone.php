<?php
/**
 * MyPhone controller for Voipmanager Management application
 *
 * @package     Phone
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * MyPhone controller class for Voipmanager Management application
 * 
 * @package     Phone
 * @subpackage  Controller
 */
class Phone_Controller_MyPhone extends Voipmanager_Controller_Snom_Phone
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_modelName   = 'Phone_Model_MyPhone';
        $this->_backend     = new Voipmanager_Backend_Snom_Phone(NULL, $this->_modelName);
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
            
    /**
     * holds the instance of the singleton
     *
     * @var Phone_Controller_MyPhone
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Controller_MyPhone
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Phone_Controller_MyPhone();
        }
        
        return self::$_instance;
    }
    
    /**
     * get myPhone by id
     *
     * @param string $_id
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_Snom_Phone
     * @throws  Voipmanager_Exception_NotFound
     */
    /*
    public function getMyPhone($_id)
    {
        $phone = $this->_backend->getMyPhone($_id, $this->_currentAccount->getId());
        
        $filter = new Voipmanager_Model_Snom_LineFilter(array(
            array('field' => 'snomphone_id', 'operator' => 'equals', 'value' => $phone->id)
        ));
        $phone->lines = Voipmanager_Controller_Snom_Line::getInstance()->search($filter);

        return $phone;    
    }
    */
    
   /**
     * update one myPhone
     *
     * @param Voipmanager_Model_Snom_Phone $_phone
     * @return  Voipmanager_Model_Snom_Phone
     * @throws  Voipmanager_Exception_InvalidArgument
     */
    /*
    public function update(Tinebase_Record_Interface $_phone)
    {
        $phone = $this->_backend->updateMyPhone($_phone, $this->_currentAccount->getId());
        
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

        if(Voipmanager_Controller_Snom_PhoneSettings::getInstance()->get($phone->getId())) {
            $phoneSettings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->update($_phoneSettings);
        } else {
            $phoneSettings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->create($_phoneSettings);            
        }
    
        return $this->getMyPhone($phone, $_accountId);
    }
    */
}
