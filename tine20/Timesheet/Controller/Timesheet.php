<?php
/**
 * contract controller for Timesheet application
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
 * contract controller class for Timesheet application
 * 
 * @package     Timesheet
 * @subpackage  Controller
 */
class Timesheet_Controller_Timesheet extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'Timesheet';
        $this->_backend = new Timesheet_Backend_Timesheet();
        $this->_modelName = 'Timesheet_Model_Timesheet';
        $this->_currentAccount = Tinebase_Core::getUser();   
        
        // disable container ACL checks as we don't init the 'Shared Timesheets' grants in the setup
        $this->_doContainerACLChecks = FALSE; 
    }    
    
    /**
     * holdes the instance of the singleton
     *
     * @var Timesheet_Controller_Timesheet
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Timesheet_Controller_Timesheet
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Timesheet_Controller_Timesheet();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
}
