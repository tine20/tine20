<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add forced pref test
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    Tinebase_PreferenceTest::main();
}

/**
 * Test class for Tinebase_PreferenceTest
 */
class Tinebase_PreferenceTest extends PHPUnit_Framework_TestCase
{
    /**
     * unit under test (UIT)
     * @var Tinebase_Preference
     */
    protected $_instance;

    /**
     * @var array test objects
     */
    protected $objects = array();

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_PreferenceTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_instance = Tinebase_Core::getPreference();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {      
    }
    
    /**
     * get default preference
     *
     */
    public function testGetDefaultPreference()
    {
        $prefValue = $this->_instance->getValue(Tinebase_Preference::TIMEZONE);
        
        $this->assertEquals('Europe/Berlin', $prefValue);
    }
    
    /**
     * test set timezone pref
     *
     */
    public function testSetPreference()
    {
        $newValue = 'Europe/Nicosia';
        $this->_instance->setValue(Tinebase_Preference::TIMEZONE, $newValue);

        $prefValue = $this->_instance->getValue(Tinebase_Preference::TIMEZONE);
        
        $this->assertEquals($newValue, $prefValue);
        
        // reset old default value
        $this->_instance->setValue(Tinebase_Preference::TIMEZONE, 'Europe/Berlin');
    }

    /**
     * test get default value
     *
     */
    public function testGetDefaultPreferenceValue()
    {
        $defaultValue = 'Shangri-La';
        $prefValue = $this->_instance->getValue('SomeNonexistantPref', $defaultValue);
        
        $this->assertEquals($defaultValue, $prefValue);
    }
}
