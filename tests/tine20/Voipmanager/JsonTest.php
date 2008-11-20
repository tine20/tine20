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
     * Backend
     *
     * @var Voipmanager_Frontend_Json
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
        $this->_backend = new Voipmanager_Frontend_Json();
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
    
    /** Asterisk Context tests **/
    
    /**
     * test creation of asterisk context
     *
     */
    public function testCreateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_backend->saveAsteriskContext(Zend_Json::encode($test));
        $this->assertEquals($test['name'], $returned['updatedData']['name']);
        $this->assertEquals($test['description'], $returned['updatedData']['description']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getAsteriskContext($contextId) as well
        $returnedGet = $this->_backend->getAsteriskContext($returned['updatedData']['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_backend->deleteAsteriskContexts(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test update of asterisk context
     *
     */
    public function testUpdateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_backend->saveAsteriskContext(Zend_Json::encode($test));
        $returned['updatedData']['name'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskContext(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['name'], $updated['updatedData']['name']);
        $this->assertEquals($returned['updatedData']['description'], $updated['updatedData']['description']);
        $this->assertNotNull($updated['updatedData']['id']);
                
        $this->_backend->deleteAsteriskContexts(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test search of asterisk context
     *
     */
    public function testSearchAsteriskContext()
    {
        $test = $this->_getAsteriskContext();        
        $returned = $this->_backend->saveAsteriskContext(Zend_Json::encode($test));
        $searchResult = $this->_backend->getAsteriskContexts('name', 'ASC', $test['name']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteAsteriskContexts(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * get asterisk context data
     *
     * @return array
     */
    protected function _getAsteriskContext()
    {
        return array(
            'name' => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Asterisk SipPeer tests **/
    
    /**
     * test creation of asterisk SipPeer
     *
     */
    public function testCreateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_backend->saveAsteriskSipPeer(Zend_Json::encode($test));
        
        $this->assertEquals($test['name'], $returned['updatedData']['name']);
        $this->assertEquals($test['context'], $returned['updatedData']['context']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getAsteriskSipPeer($SipPeerId) as well
        $returnedGet = $this->_backend->getAsteriskSipPeer($returned['updatedData']['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['context'], $returnedGet['context']);
        $this->_backend->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test update of asterisk SipPeer
     *
     */
    public function testUpdateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_backend->saveAsteriskSipPeer(Zend_Json::encode($test));
        $returned['updatedData']['name'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskSipPeer(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['name'], $updated['updatedData']['name']);
        $this->assertEquals($returned['updatedData']['context'], $updated['updatedData']['context']);
        $this->assertNotNull($updated['updatedData']['id']);
                
        $this->_backend->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test search of asterisk SipPeer
     *
     */
    public function testSearchAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();        
        $returned = $this->_backend->saveAsteriskSipPeer(Zend_Json::encode($test));
        $searchResult = $this->_backend->getAsteriskSipPeers('name', 'ASC', $test['name'], $test['context']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * get asterisk SipPeer data
     *
     * @return array
     */
    protected function _getAsteriskSipPeer()
    {
        return array(
            'name' => Tinebase_Record_Abstract::generateUID(),
            'context' => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Asterisk MeetMe tests **/
    
    /**
     * test creation of asterisk meetme room
     *
     */
    public function testCreateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_backend->saveAsteriskMeetme(Zend_Json::encode($test));
        
        $this->assertEquals($test['confno'], $returned['updatedData']['confno']);
        $this->assertEquals($test['adminpin'], $returned['updatedData']['adminpin']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getAsteriskMeetme($meetmeId) as well
        $returnedGet = $this->_backend->getAsteriskMeetme($returned['updatedData']['id']);
        //print_r($returnedGet);
        $this->assertEquals($test['confno'], $returnedGet['confno']);
        $this->assertEquals($test['adminpin'], $returnedGet['adminpin']);
        
        $this->_backend->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test update of asterisk meetme room
     *
     */
    public function testUpdateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_backend->saveAsteriskMeetme(Zend_Json::encode($test));
        $returned['updatedData']['adminpin'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskMeetme(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['confno'], $updated['updatedData']['confno']);
        $this->assertEquals($returned['updatedData']['adminpin'], $updated['updatedData']['adminpin']);
        $this->assertNotNull($updated['updatedData']['id']);
                
        $this->_backend->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test search of asterisk meetme room
     *
     */
    public function testSearchAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();        
        $returned = $this->_backend->saveAsteriskMeetme(Zend_Json::encode($test));
                
        $searchResult = $this->_backend->getAsteriskMeetmes('confno', 'ASC', $test['confno']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * get asterisk meetme data
     *
     * @return array
     */
    protected function _getAsteriskMeetme()
    {
        return array(
            'confno'  => Tinebase_Record_Abstract::generateUID(),
            'adminpin' => Tinebase_Record_Abstract::generateUID(),
            'pin' => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Asterisk Voicemail tests **/
    
    /**
     * test creation of asterisk meetme room
     *
     */
    public function testCreateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $returned = $this->_backend->saveAsteriskVoicemail(Zend_Json::encode($test));
        // print_r($returned);
        $this->assertEquals($test['context'], $returned['updatedData']['context']);
        $this->assertEquals($test['fullname'], $returned['updatedData']['fullname']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getAsteriskVoicemail as well
        $returnedGet = $this->_backend->getAsteriskVoicemail($returned['updatedData']['id']);
        // print_r($returnedGet)
        $this->assertEquals($test['context'], $returnedGet['context']);
        $this->assertEquals($test['fullname'], $returnedGet['fullname']);
        
        $this->_backend->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test update of asterisk voice mail
     *
     */
    public function testUpdateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $returned = $this->_backend->saveAsteriskVoicemail(Zend_Json::encode($test));
        $returned['updatedData']['fullname'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskVoicemail(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['context'], $updated['updatedData']['context']);
        $this->assertEquals($returned['updatedData']['fullname'], $updated['updatedData']['fullname']);
        $this->assertNotNull($updated['updatedData']['id']);
                
        $this->_backend->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test search of asterisk voicemail
     *
     */
    public function testSearchAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();        
        $returned = $this->_backend->saveAsteriskVoicemail(Zend_Json::encode($test));
                
        $searchResult = $this->_backend->getAsteriskVoicemails('context', 'ASC', $test['fullname'], $test['context']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * get asterisk voicemail data
     *
     * @return array
     */
    protected function _getAsteriskVoicemail()
    {
        return array(
            'context'  => Tinebase_Record_Abstract::generateUID(),
            'fullname' => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Snom Location tests **/
    
    /**
     * test creation of snom location
     *
     */
    public function testCreateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $returned = $this->_backend->saveSnomLocation(Zend_Json::encode($test));
        // print_r($returned);
        $this->assertEquals($test['name'], $returned['updatedData']['name']);
        $this->assertEquals($test['description'], $returned['updatedData']['description']);
        $this->assertEquals($test['registrar'], $returned['updatedData']['registrar']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getSnomLocation as well
        $returnedGet = $this->_backend->getSnomLocation($returned['updatedData']['id']);
        // print_r($returnedGet)
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        $this->assertEquals($test['registrar'], $returnedGet['registrar']);
        
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test update of snom location
     *
     */
    public function testUpdateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $returned = $this->_backend->saveSnomLocation(Zend_Json::encode($test));
        $returned['updatedData']['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomLocation(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['name'], $updated['updatedData']['name']);
        $this->assertEquals($returned['updatedData']['description'], $updated['updatedData']['description']);
        $this->assertEquals($returned['updatedData']['registrar'], $updated['updatedData']['registrar']);
        $this->assertNotNull($updated['updatedData']['id']);
                
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test search of snom location
     *
     */
    public function testSearchSnomLocation()
    {
        $test = $this->_getSnomLocation();        
        $returned = $this->_backend->saveSnomLocation(Zend_Json::encode($test));
                
        $searchResult = $this->_backend->getSnomLocations('description', 'ASC', $test['description']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * get snom location data
     *
     * @return array
     */
    protected function _getSnomLocation()
    {
        return array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID(),
            'registrar' => Tinebase_Record_Abstract::generateUID()
        );
    }
}		
