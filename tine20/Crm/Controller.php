<?php
/**
 * MAIN controller for CRM application
 * 
 * the main logic of the CRM application
 *
 * @package     Crm
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * leads controller class for CRM application
 * 
 * @package     Crm
 * @subpackage  Controller
 */
class Crm_Controller extends Tinebase_Controller_Event implements Tinebase_Container_Interface
{
    /**
     * default settings
     * 
     * @var array
     */
    protected $_defaultsSettings = array(
        'leadstate_id'  => 1,
        'leadtype_id'   => 1,
        'leadsource_id' => 1,
    );
    
    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Crm_Model_Lead';
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() 
    {
        $this->_applicationName = 'Crm';
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
     * @var Crm_Controller
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Crm_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Crm_Controller;
        }
        
        return self::$_instance;
    }    
        
    /********************* event handler and personal folder ***************************/
    
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
            case 'Admin_Event_AddAccount':
                $this->createPersonalFolder($_eventObject->account);
                break;
            case 'Admin_Event_DeleteAccount':
                $this->deletePersonalFolder($_eventObject->account);
                break;
        }
    }
    
    /**
     * creates the initial folder for new accounts
     *
     * @param mixed[int|Tinebase_Model_User] $_account   the accountd object
     * @return Tinebase_Record_RecordSet                            of subtype Tinebase_Model_Container
     * 
     * @todo generalize this
     */
    public function createPersonalFolder($_accountId)
    {
        $translation = Tinebase_Translation::getTranslation('Crm');
        
        $account = Tinebase_User::getInstance()->getUserById($_accountId);
        
        $newContainer = new Tinebase_Model_Container(array(
            'name'              => sprintf($translation->_("%s's personal leads"), $account->accountFullName),
            'type'              => Tinebase_Model_Container::TYPE_PERSONAL,
            'owner_id'          => $_accountId,
            'backend'           => 'Sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Crm')->getId(),
            'model'             => static::$_defaultModel
        ));
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Creating new personal folder for account id ' . $_accountId);
        
        $personalContainer = Tinebase_Container::getInstance()->addContainer($newContainer);
        $container = new Tinebase_Record_RecordSet('Tinebase_Model_Container', array($personalContainer));
        
        return $container;
    }

    /**
     * Returns settings for crm app
     * - result is cached
     *
     * @param boolean $_resolve if some values should be resolved (here yet unused)
     * @return  Crm_Model_Config
     * 
     * @todo check 'endslead' values
     * @todo generalize this / adopt Tinebase_Controller_Abstract::getConfigSettings()
     */
    public function getConfigSettings($_resolve = FALSE)
    {
        $cache = Tinebase_Core::get('cache');
        $cacheId = convertCacheId('getCrmSettings');
        $result = $cache->load($cacheId);
        
        if (! $result) {
            
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
                . ' Fetching Crm Settings ...');
            
            $translate = Tinebase_Translation::getTranslation('Crm');
            
            $result = new Crm_Model_Config(array(
                'defaults' => parent::getConfigSettings()
            ));
            
            $others = array(
                Crm_Model_Config::LEADTYPES => array(
                    array('id' => 1, 'leadtype' => $translate->_('Customer')),
                    array('id' => 2, 'leadtype' => $translate->_('Partner')),
                    array('id' => 3, 'leadtype' => $translate->_('Reseller')),
                ), 
                Crm_Model_Config::LEADSTATES => array(
                    array('id' => 1, 'leadstate' => $translate->_('open'),                  'probability' => 0,     'endslead' => 0),
                    array('id' => 2, 'leadstate' => $translate->_('contacted'),             'probability' => 10,    'endslead' => 0),
                    array('id' => 3, 'leadstate' => $translate->_('waiting for feedback'),  'probability' => 30,    'endslead' => 0),
                    array('id' => 4, 'leadstate' => $translate->_('quote sent'),            'probability' => 50,    'endslead' => 0),
                    array('id' => 5, 'leadstate' => $translate->_('accepted'),              'probability' => 100,   'endslead' => 1),
                    array('id' => 6, 'leadstate' => $translate->_('lost'),                  'probability' => 0,     'endslead' => 1),
                ), 
                Crm_Model_Config::LEADSOURCES => array(
                    array('id' => 1, 'leadsource' => $translate->_('Market')),
                    array('id' => 2, 'leadsource' => $translate->_('Email')),
                    array('id' => 3, 'leadsource' => $translate->_('Telephone')),
                    array('id' => 4, 'leadsource' => $translate->_('Website')),
                )
            );
            foreach ($others as $setting => $defaults) {
                $result->$setting = Crm_Config::getInstance()->get($setting, new Tinebase_Config_Struct($defaults))->toArray();
            }
            
            // save result and tag it with 'settings'
            $cache->save($result, $cacheId, array('settings'));
        }
        
        return $result;
    }
    
    /**
     * save crm settings
     * 
     * @param Crm_Model_Config $_settings
     * @return Crm_Model_Config
     * 
     * @todo generalize this
     */
    public function saveConfigSettings($_settings)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ 
            . ' Updating Crm Settings: ' . print_r($_settings->toArray(), TRUE));
        
        foreach ($_settings->toArray() as $field => $value) {
            if ($field == 'id') {
                continue;
            } else if ($field == 'defaults') {
                parent::saveConfigSettings($value);
            } else {
                Crm_Config::getInstance()->set($field, $value);
            }
        }
        
        // invalidate cache
        Tinebase_Core::getCache()->remove('getCrmSettings');
        Crm_Config::getInstance()->clearCache();
        
        return $this->getConfigSettings();
    }
}
