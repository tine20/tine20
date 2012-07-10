<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * 
 * @todo adopt this class to new controller logig
 */


/**
 * Abstact tests for record controllers
 */
class Tinebase_AbstractControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * application name of the controller to test
     *
     * @var string
     */
    protected $_appName;
    
    /**
     * Name of the model(s) this controller handels
     *
     * @var array <application>_Model_<modelName> => <modelName>
     */
    protected $_modelNames = array();
    
    /**
     * Minimal data needed to gain a valid record of $this->_modelName
     *
     * @var array modelName => array data
     */
    protected $_minimalDatas = array();
    
    /**
     * @var <Application>_Controller
     */
    protected $_controller;
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_controller = Tinebase_Core::getApplicationInstance($this->_appName);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        
    }
    
    /**
     * testGetInstance().
     */
    public function testGetInstance()
    {
        $controllerName = $this->_appName . '_Controller';
        $this->assertTrue($this->_controller instanceof $controllerName);
    }
    
    public function testCreateRecords()
    {
        foreach($this->_modelNames as $fullModelName => $modelName) {
            $createFunctionName = 'Create' . $modelName;
            $record = new $fullModelName($this->$_minimalDatas[$modelName]);
            $persitentRecord = $this->_controller->$createFunctionName($record);
        }
    }
    
    public function testGetRecord()
    {
        
    }
    
}

