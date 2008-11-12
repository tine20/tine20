<?php
/**
 * Category controller for Timesheet application
 * 
 * @package     Timesheet
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * Category controller class for Timesheet application
 * 
 * @package     Timesheet
 * @subpackage  Controller
 */
class Timesheet_Controller_Category extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Timesheet';
        $this->_backend = new Timesheet_Backend_Category();
        $this->_modelName = 'Timesheet_Model_Category';
        $this->_currentAccount = Tinebase_Core::getUser();   
    }    
    
    /**
     * holdes the instance of the singleton
     *
     * @var Timesheet_Controller_Category
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timesheet_Controller_Category
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Timesheet_Controller_Category();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
}
