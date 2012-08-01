<?php
/**
 * Tine 2.0
 * 
 * @package     HumanResources
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * HumanResources Controller
 * 
 * @package HumanResources
 * @subpackage  Controller
 */
class HumanResources_Controller extends Tinebase_Controller_Event
{
    /**
     * holds the instance of the singleton
     *
     * @var HumanResources_Controller
     */
    private static $_instance = NULL;

    /**
     * holds the default Model of this application
     * @var string
     */
    protected static $_defaultModel = 'HumanResources_Model_Employee';
    
    /**
     * constructor (get current user)
     */
    private function __construct() {
        $this->_applicationName = 'HumanResources';
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
     * @return HumanResources_Controller
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new HumanResources_Controller;
        }
        
        return self::$_instance;
    }
}
