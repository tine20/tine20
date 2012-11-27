<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Sales Controller (composite)
 * 
 * @package Sales
 * @subpackage  Controller
 */
class Sales_Controller extends Tinebase_Controller_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Sales';
    }

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'Sales_Model_Contract';
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {
    }
    
    /**
     * holds self
     * @var Sales_Controller
     */
    private static $_instance = NULL;
    
    /**
     * Valid config keys for this application
     * @var array
     */
    private static $_configKeys;
    
    /**
     * config defaults
     * @var array
     */
    private static $_configKeyDefaults;
    
    /**
     * singleton
     *
     * @return Sales_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Controller();
        }
        return self::$_instance;
    }
    
  
    /**
     * returns the config for this app
     * @return array
     */
    public function getConfig()
    {
        if(!Tinebase_Core::getUser()->hasRight('Sales', 'admin')) {
            throw new Tinebase_Exception_AccessDenied(_('You do not have admin rights on Sales'));
        }
        
        return array(
            'contractNumberValidation' => Sales_Config::getInstance()->get('contractNumberValidation', 'integer'),
            'contractNumberGeneration' => Sales_Config::getInstance()->get('contractNumberGeneration', 'auto')
        );
    }

    /**
     * save Sales settings
     *
     * @param array config
     * @return Sales_Model_Config
     *
     * @todo generalize this
     */
    public function setConfig($config)
    {
        if(!Tinebase_Core::getUser()->hasRight('Sales', 'admin')) {
            throw new Tinebase_Exception_AccessDenied(_('You do not have admin rights on Sales'));
        }
        
        $ret[] = array(
            Sales_Config::getInstance()->set('contractNumberGeneration', $config['contractNumberGeneration']),
            Sales_Config::getInstance()->set('contractNumberValidation', $config['contractNumberValidation'])
        );
        return $ret;
    }
}
