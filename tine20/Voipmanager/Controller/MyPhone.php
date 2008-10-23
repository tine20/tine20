<?php
/**
 * MyPhone controller for Voipmanager Management application
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
 * MyPhone controller class for Voipmanager Management application
 * 
 * @package     Voipmanager
 * @subpackage  Controller
 */
class Voipmanager_Controller_MyPhone extends Voipmanager_Controller_Abstract
{
    /**
     * Voipmanager backend class
     *
     * @var Voipmanager_Backend_MyPhone
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
     * @var Voipmanager_Controller_MyPhone
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Voipmanager_Controller_MyPhone
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Voipmanager_Controller_MyPhone;
        }
        
        return self::$_instance;
    }
    
    /**
     * get myPhone by id
     *
     * @param string $_id
     * @param string $_accountId
     * @return Tinebase_Record_RecordSet of subtype Voipmanager_Model_SnomPhone
     */
    public function getMyPhone($_id, $_accountId)
    {
        if(empty($_accountId)) 
        {
            throw new UnderflowException('no accountId set');
        }   
        
        $phone = $this->_backend->getMyPhone($_id, $_accountId);
        
        $filter = new Voipmanager_Model_SnomLineFilter(array(
            'snomphone_id'  => $phone->id
        ));
        $phone->lines = Voipmanager_Controller_Snom_Line::getInstance()->search($filter);

        return $phone;    
    }
    
   /**
     * update one myPhone
     *
     * @param Voipmanager_Model_SnomPhone $_phone
     * @return  Voipmanager_Model_SnomPhone
     */
    public function update(Voipmanager_Model_MyPhone $_phone, Voipmanager_Model_SnomPhoneSettings $_phoneSettings, $_accountId)
    {
       
        if(empty($_accountId)) 
        {
            throw new UnderflowException('no accountId set');
        }        
       
        $phone = $this->_backend->updateMyPhone($_phone, $_accountId);
        
        // force the right phone_id
        $_phoneSettings->setId($phone->getId());

        // set all settings which are equal to the default settings to NULL
        $template = Voipmanager_Controller_Snom_Template::getInstance()->get($phone->template_id);
        $settingDefaults = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->get($template->setting_id);

        foreach($_phoneSettings AS $key => $value) {
            if($key == 'phone_id') {
                continue;
            }
            if($_phoneSettings->$key == $settingDefaults->$key) {
                $_phoneSettings->$key = NULL;
            }    
        }
        
        if(Voipmanager_Controller_Snom_Setting::getInstance()->get($phone->getId())) {
            $phoneSettings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->update($_phoneSettings);
        } else {
            $phoneSettings = Voipmanager_Controller_Snom_PhoneSettings::getInstance()->create($_phoneSettings);            
        }
      
        return $this->getMyPhone($phone, $_accountId);
    }    
    
    /****************** don't allow get/create/search here ************************/
    
    /**
     * disabled
     *
     * @param unknown_type $_id
     */
    public function get($_id)
    {
        throw new Exception('not allowed!');
    }
    
    /**
     * disabled
     *
     * @param Tinebase_Record_Interface $_record
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        throw new Exception('not allowed!');        
    }
    
    /**
     * disabled
     *
     * @param unknown_type $_identifiers
     */
    public function delete($_identifiers) {
        throw new Exception('not allowed!');
    }
}
