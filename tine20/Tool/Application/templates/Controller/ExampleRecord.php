<?php
/**
 * ExampleRecord controller for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * ExampleRecord controller class for ExampleApplication application
 * 
 * @package     ExampleApplication
 * @subpackage  Controller
 */
class ExampleApplication_Controller_ExampleRecord extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'ExampleApplication';
        $this->_backend = new ExampleApplication_Backend_ExampleRecord();
        $this->_modelName = 'ExampleApplication_Model_ExampleRecord';
        $this->_purgeRecords = FALSE;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = FALSE;
        $this->_resolveCustomFields = TRUE;
    }
    
    /**
     * holds the instance of the singleton
     *
     * @var ExampleApplication_Controller_ExampleRecord
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return ExampleApplication_Controller_ExampleRecord
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new ExampleApplication_Controller_ExampleRecord();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/    
    
}
