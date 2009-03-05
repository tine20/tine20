<?php
/**
 * Record controller for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 *
 */

/**
 * Record controller class for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 */
class ExampleApplication_Controller_Record extends Tinebase_Application_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {        
        $this->_applicationName = 'ExampleApplication';
        $this->_backend = new ExampleApplication_Backend_Record();
        $this->_modelName = 'ExampleApplication_Model_Record';
        $this->_currentAccount = Tinebase_Core::getUser();   
        $this->_purgeRecords = FALSE;
    }    
    
    /**
     * holdes the instance of the singleton
     *
     * @var ExampleApplication_Controller_Record
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return ExampleApplication_Controller_Record
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ExampleApplication_Controller_Record();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
    
}
