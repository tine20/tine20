<?php
/**
 * Project controller for Projects application
 * 
 * @package     Projects
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Project controller class for Projects application
 * 
 * @package     Projects
 * @subpackage  Controller
 */
class Projects_Controller_Project extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Projects';
        $this->_backend = new Projects_Backend_Project();
        $this->_modelName = 'Projects_Model_Project';
        $this->_purgeRecords = TRUE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = TRUE;
    }    
    
    /**
     * holds the instance of the singleton
     *
     * @var Projects_Controller_Project
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Projects_Controller_Project
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Projects_Controller_Project();
        }
        
        return self::$_instance;
    }      
}
