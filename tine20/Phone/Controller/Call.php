<?php
/**
 * Call controller for Phone application
 * 
 * @package     Phone
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Call controller class for Phone application
 * 
 * @package     Phone
 * @subpackage  Controller
 */
class Phone_Controller_Call extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Phone';
        $this->_backend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);
        $this->_modelName = 'Phone_Model_Call';
        $this->_purgeRecords = TRUE;
        $this->_doRightChecks = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
    }    
    
    /**
     * holds the instance of the singleton
     *
     * @var Phone_Controller_Call
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Phone_Controller_Call
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }
}
