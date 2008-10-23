<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo        add more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Voipmanager_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Voipmanager_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Fixtures
     * 
     * @var array test objects
     */
    protected $_objects = array();

    /**
     * Backend
     *
     * @var Voipmanager_Json
     */
    protected $_backend;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Voipmanager Json Tests');
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
        $this->_backend = new Voipmanager_Json();
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
    
    /** MeetMe tests **/
    
    /**
     * test creation of asterisk meetme room
     *
     */
    public function testCreateAsteriskMeetme()
    {
        /*
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_backend->save(Zend_Json::encode($test));
        $this->assertEquals($test['confno'], $returned['confno']);
        $this->assertEquals($test['adminpin'], $returned['adminpin']);
        $this->assertNotNull($returned['id']);
        
        $this->_backend->delete($returned['id']);
        */ 
    }
    
    /**
     * test update of asterisk meetme room
     *
     */
    public function testUpdateAsteriskMeetme()
    {
        /*
        $test = $this->_getAsteriskMeetme();
        
        $test = $this->_backends['Asterisk_Meetme']->create($test);
        $test->adminpin = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Asterisk_Meetme']->update($test);
        $this->assertEquals($test->confno, $returned->confno);
        $this->assertEquals($test->adminpin, $returned->adminpin);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_Meetme']->delete($returned->getId());
        */ 
    }
    
    /**
     * test search of asterisk meetme room
     *
     */
    public function testSearchAsteriskMeetme()
    {
        /*
        $test = $this->_getAsteriskMeetme();
        $test = $this->_backends['Asterisk_Meetme']->create($test);
        
        $filter = new Voipmanager_Model_AsteriskMeetmeFilter(array(
            'query' => $test->confno
        ));        
        $returned = $this->_backends['Asterisk_Meetme']->search($filter);
        $this->assertEquals(1, count($returned));
        
        $this->_backends['Asterisk_Meetme']->delete($returned->getId());
        */ 
    }
    
    protected function _getAsteriskMeetme()
    {
        return array(
            'confno'  => Tinebase_Record_Abstract::generateUID(),
            'adminpin' => Tinebase_Record_Abstract::generateUID(),
            'pin' => Tinebase_Record_Abstract::generateUID()
        );
    }    
}		
