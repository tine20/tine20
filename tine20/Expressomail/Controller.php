<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for Expressomail, does event handling
 *
 * @package     Expressomail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * main controller for Expressomail
 *
 * @package     Expressomail
 * @subpackage  Controller
 */
class Expressomail_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the instance of the singleton
     *
     * @var Expressomail_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor (get current user)
     */
    private function __construct() {
        $this->_applicationName = "Expressomail";
        $this->_defaultsSettings = array(
            Expressomail_Config::IMAPSEARCHMAXRESULTS => 1000,
            Expressomail_Config::AUTOSAVEDRAFTSINTERVAL => 15,
            Expressomail_Config::REPORTPHISHINGEMAIL => '',
        );
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
     * @return Expressomail_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Expressomail_Controller;
        }
        
        return self::$_instance;
    }

    /**
     * event handler function
     * 
     * all events get routed through this function
     *
     * @param Tinebase_Event_Abstract $_eventObject the eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . ' (' . __LINE__ . ') handle event of type ' . get_class($_eventObject));
        
        switch(get_class($_eventObject)) {
            case 'Tinebase_Event_User_ChangeCredentialCache':
                Expressomail_Controller_Account::getInstance()->updateCredentialsOfAllUserAccounts($_eventObject->oldCredentialCache);
                break;
        }
    }
    
    /**
     * Returns settings for Expressomail app
     *
     * @param boolean $_resolve if some values should be resolved (here yet unused)
     * @return  Tinebase_Model_Config
     *
     */
    public function getConfigSettings($_resolve = FALSE)
    {	
    	$result = Expressomail_Config::getInstance()->get(Expressomail_Config::EXPRESSOMAIL_SETTINGS, new Tinebase_Config_Struct($this->_defaultsSettings));
    	return ($_resolve) ? $this->_resolveConfigSettings($result) : $result;
    }
    
    /**
     * save Expressomail settings
     *
     * @param $_settings: array of tuples [parameter -> value]
     * @return array of [parameter -> value]
     *
     * @todo generalize this
     */
    public function saveConfigSettings($_settings)
    {
    	if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
    			. ' Updating Expressomail Settings: ' . print_r($_settings, TRUE));
    	
    	Expressomail_Config::getInstance()->set(Expressomail_Config::EXPRESSOMAIL_SETTINGS, $_settings);
    	return $this->getConfigSettings();
    }
}
