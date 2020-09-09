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
    private function __construct()
    {
        $this->_applicationName = 'ExampleApplication';
        $this->_modelName = ExampleApplication_Model_ExampleRecord::class;
        $this->_backend = new Tinebase_Backend_Sql(array(
            'modelName' => $this->_modelName,
            'tableName' => 'example_application_record',
            'modlogActive' => true
        ));

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
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
            self::$_instance = new self();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/

    /**
     * implement logic for each controller in this function
     *
     * @param Tinebase_Event_Abstract $_eventObject
     */
    protected function _handleEvent(Tinebase_Event_Abstract $_eventObject)
    {
        switch(get_class($_eventObject))
        {
            case 'Tinebase_Event_Record_Update':
                /** @var Tinebase_Event_Record_Update $_eventObject*/
                echo 'catched record update for observing id: ' . $_eventObject->persistentObserver->observer_identifier;
                break;
        }
    }
}
