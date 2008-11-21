<?php
/**
 * Category controller for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        move categories to Erp?
 */

/**
 * Category controller class for Timetracker application
 * 
 * @package     Timetracker
 * @subpackage  Controller
 */
class Timetracker_Controller_Category extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Timetracker';
        $this->_backend = new Timetracker_Backend_Category();
        $this->_modelName = 'Timetracker_Model_Category';
        $this->_currentAccount = Tinebase_Core::getUser();   
    }    
    
    /**
     * holdes the instance of the singleton
     *
     * @var Timetracker_Controller_Category
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timetracker_Controller_Category
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Timetracker_Controller_Category();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
}
