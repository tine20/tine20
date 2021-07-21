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
    use Tinebase_Controller_SingletonTrait;

    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    protected function __construct()
    {
        $this->_applicationName = ExampleApplication_Config::APP_NAME;
        $this->_modelName = ExampleApplication_Model_ExampleRecord::class;
        $this->_backend = new Tinebase_Backend_Sql([
            Tinebase_Backend_Sql::MODEL_NAME        => ExampleApplication_Model_ExampleRecord::class,
            Tinebase_Backend_Sql::TABLE_NAME        => ExampleApplication_Model_ExampleRecord::TABLE_NAME,
            Tinebase_Backend_Sql::MODLOG_ACTIVE     => true,
        ]);

        $this->_purgeRecords = false;
        $this->_resolveCustomFields = true;
        // activate this if you want to use containers
        $this->_doContainerACLChecks = false;
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
