<?php
/**
 * Tine 2.0
 * 
 * MAIN controller for addressbook, does event and container handling
 *
 * @package     Admin
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 */

/**
 * main controller for Admin
 *
 * @package     Admin
 * @subpackage  Controller
 */
class Admin_Controller extends Tinebase_Controller_Abstract
{
    /**
     * holds the instance of the singleton
     *
     * @var Admin_Controller
     */
    private static $_instance = NULL;

    /**
     * constructor
     */
    private function __construct()
    {
        $this->_applicationName = 'Admin';
        $this->_defaultsSettings = array(
            Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK  => NULL,
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
     * @return Admin_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Admin_Controller;
        }
        
        return self::$_instance;
    }
    
    /**
     * resolve some config settings
     * 
     * @param array $_settings
     */
    protected function _resolveConfigSettings($_settings)
    {
        foreach ($_settings as $key => $value) {
            if ($key === Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK && $value) {
                $_settings[$key] = Tinebase_Container::getInstance()->get($value)->toArray();
            }
        }
        
        return $_settings;
    }
}
