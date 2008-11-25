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
    
    /** Snom Phone tests **/
    
    /**
     * test creation of snom phone
     *
     */
    public function testCreateSnomPhone()
    {
        $testPhone = $this->_getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returned = $this->_backend->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
        $phoneTemplate = $this->_backend->getSnomTemplate($testPhone['template_id']);
        
        $this->assertEquals($testPhone['description'], $returned['updatedData']['description']);
        $this->assertEquals(strtoupper($testPhone['macaddress']), $returned['updatedData']['macaddress']);
        $this->assertEquals($testPhone['location_id'], $returned['updatedData']['location_id']);
        $this->assertEquals($testPhone['template_id'], $returned['updatedData']['template_id']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getSnomPhone as well
        $returnedGet = $this->_backend->getSnomPhone($returned['updatedData']['id']);
        
        $this->assertEquals($testPhone['description'], $returnedGet['description']);
        $this->assertEquals(strtoupper($testPhone['macaddress']), $returnedGet['macaddress']);
        $this->assertEquals($testPhone['location_id'], $returnedGet['location_id']);
        $this->assertEquals($testPhone['template_id'], $returnedGet['template_id']);
        
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($returned['updatedData']['id'])));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['updatedData']['location_id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['updatedData']['template_id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($phoneTemplate['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($phoneTemplate['setting_id'])));
    }
    
    /**
     * test update of snom phone
     *
     */
    public function testUpdateSnomPhone()
    {
        $testPhone = $this->_getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returned = $this->_backend->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        $returned['updatedData']['description'] = Tinebase_Record_Abstract::generateUID();
        
        $phoneTemplate = $this->_backend->getSnomTemplate($testPhone['template_id']);
        
        $updated = $this->_backend->saveSnomPhone(Zend_Json::encode($returned['updatedData']), Zend_Json::encode($returned['updatedData']['lines']), Zend_Json::encode($returned['updatedData']['rights']));
        $this->assertEquals($returned['updatedData']['description'], $updated['updatedData']['description']);
        $this->assertEquals($returned['updatedData']['macaddress'], $updated['updatedData']['macaddress']);
        $this->assertEquals($returned['updatedData']['location_id'], $updated['updatedData']['location_id']);
        $this->assertEquals($returned['updatedData']['template_id'], $updated['updatedData']['template_id']);
        $this->assertNotNull($updated['updatedData']['id']);
                
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($returned['updatedData']['id'])));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['updatedData']['location_id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['updatedData']['template_id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($phoneTemplate['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($phoneTemplate['setting_id'])));
    }
    
    /**
     * test search of snom phone
     *
     */
    public function testSearchSnomPhone()
    {
        $testPhone = $this->_getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returned = $this->_backend->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
        $phoneTemplate = $this->_backend->getSnomTemplate($testPhone['template_id']);
        
        $searchResult = $this->_backend->getSnomPhones('description', 'ASC', $testPhone['description']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($returned['updatedData']['id'])));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['updatedData']['location_id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['updatedData']['template_id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($phoneTemplate['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($phoneTemplate['setting_id'])));
    }
    
    /**
     * get snom phone data
     *
     * @return array
     */
    protected function _getSnomPhone()
    {
        $testLocation = $this->_getSnomLocation();
        $returnedLocation = $this->_backend->saveSnomLocation(Zend_Json::encode($testLocation));
        
        $testTemplate = $this->_getSnomTemplate();
        $returnedTemplate = $this->_backend->saveSnomTemplate(Zend_Json::encode($testTemplate));
        
        return array(
            'description'  => Tinebase_Record_Abstract::generateUID(),
            'macaddress' => substr(Tinebase_Record_Abstract::generateUID(), 0, 12),
            'location_id' => $returnedLocation['updatedData']['id'],
            'template_id' => $returnedTemplate['updatedData']['id'],
            'current_model' => 'snom300',
            'redirect_event' => 'none'
        );
    }
    
    /** Snom Phone Settings tests **/
    
/**
     * test creation of snom phone settings
     *
     *//*
    public function testCreateSnomPhoneSettings()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
        // print_r($returned);
        $this->assertEquals($test['name'], $returned['updatedData']['name']);
        $this->assertEquals($test['description'], $returned['updatedData']['description']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getSnomSettings as well
        $returnedGet = $this->_backend->getSnomSetting($returned['updatedData']['id']);
        // print_r($returnedGet)
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    */
    /**
     * test update of snom phone settings
     *
     *//*
    public function testUpdateSnomPhoneSettings()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
        $returned['updatedData']['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomSetting(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['name'], $updated['updatedData']['name']);
        $this->assertEquals($returned['updatedData']['description'], $updated['updatedData']['description']);
        $this->assertNotNull($updated['updatedData']['id']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    */
    /**
     * test search of snom phone settings
     *
     *//*
    public function testSearchSnomPhoneSettings()
    {
        $test = $this->_getSnomSetting();        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
                
        $searchResult = $this->_backend->getSnomSettings('description', 'ASC', $test['description']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    */
    /**
     * get snom phone settings data
     *
     * @return array
     *//*
    protected function _getSnomPhoneSettings()
    {
        return array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        );
    }
    */
    /**
     * test creation of snom settings
     *
     */
    public function testCreateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
        
        $this->assertEquals($test['name'], $returned['updatedData']['name']);
        $this->assertEquals($test['description'], $returned['updatedData']['description']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getSnomSettings as well
        $returnedGet = $this->_backend->getSnomSetting($returned['updatedData']['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test update of snom settings
     *
     */
    public function testUpdateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
        $returned['updatedData']['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomSetting(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['name'], $updated['updatedData']['name']);
        $this->assertEquals($returned['updatedData']['description'], $updated['updatedData']['description']);
        $this->assertNotNull($updated['updatedData']['id']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test search of snom settings
     *
     */
    public function testSearchSnomSetting()
    {
        $test = $this->_getSnomSetting();        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
                
        $searchResult = $this->_backend->getSnomSettings('description', 'ASC', $test['description']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * get snom settings data
     *
     * @return array
     */
    protected function _getSnomSetting()
    {
        return array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Snom Software tests **/
    
    /**
     * test creation of snom software
     *
     */
    public function testCreateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $returned = $this->_backend->saveSnomSoftware(Zend_Json::encode($test));
        $this->assertEquals($test['name'], $returned['updatedData']['name']);
        $this->assertEquals($test['description'], $returned['updatedData']['description']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getSnomSoftware as well
        $returnedGet = $this->_backend->getSnomSoftware($returned['updatedData']['id']);
        // print_r($returnedGet)
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test update of snom software
     *
     */
    public function testUpdateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $returned = $this->_backend->saveSnomSoftware(Zend_Json::encode($test));
        $returned['updatedData']['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomSoftware(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['name'], $updated['updatedData']['name']);
        $this->assertEquals($returned['updatedData']['description'], $updated['updatedData']['description']);
        $this->assertNotNull($updated['updatedData']['id']);
                
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * test search of snom software
     *
     */
    public function testSearchSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        $returned = $this->_backend->saveSnomSoftware(Zend_Json::encode($test));
        
        $searchResult = $this->_backend->searchSnomSoftware('description', 'ASC', $test['description']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['updatedData']['id'])));
    }
    
    /**
     * get snom software data
     *
     * @return array
     */
    protected function _getSnomSoftware()
    {
        return array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Snom Template tests **/
    
    /**
     * test creation of snom template
     *
     */
    public function testCreateSnomTemplate()
    {
        $test = $this->_getSnomTemplate();
        $returned = $this->_backend->saveSnomTemplate(Zend_Json::encode($test));
        
        $this->assertEquals($test['name'], $returned['updatedData']['name']);
        $this->assertEquals($test['setting_id'], $returned['updatedData']['setting_id']);
        $this->assertEquals($test['software_id'], $returned['updatedData']['software_id']);
        $this->assertNotNull($returned['updatedData']['id']);
        
        // test getSnomTemplate as well
        $returnedGet = $this->_backend->getSnomTemplate($returned['updatedData']['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['setting_id'], $returnedGet['setting_id']);
        $this->assertEquals($test['software_id'], $returnedGet['software_id']);
        
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['updatedData']['id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['updatedData']['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['setting_id'])));
    }
    
    /**
     * test update of snom template
     *
     */
    public function testUpdateSnomTemplate()
    {   
        $test = $this->_getSnomTemplate();
        $returned = $this->_backend->saveSnomTemplate(Zend_Json::encode($test));
        $returned['updatedData']['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomTemplate(Zend_Json::encode($returned['updatedData']));
        $this->assertEquals($returned['updatedData']['name'], $updated['updatedData']['name']);
        $this->assertEquals($returned['updatedData']['setting_id'], $updated['updatedData']['setting_id']);
        $this->assertEquals($returned['updatedData']['software_id'], $updated['updatedData']['software_id']);
        $this->assertNotNull($updated['updatedData']['id']);
        
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['updatedData']['id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['updatedData']['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['setting_id'])));
    }
    
    /**
     * test search of snom template
     *
     */
    public function testSearchSnomTemplate()
    {
        $test = $this->_getSnomTemplate();
        $returned = $this->_backend->saveSnomTemplate(Zend_Json::encode($test));
        
        $searchResult = $this->_backend->getSnomTemplates('description', 'ASC', $test['name']);
        $this->assertEquals(1, $searchResult['totalcount']);
        
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['updatedData']['id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['updatedData']['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['updatedData']['setting_id'])));
    }
    
    /**
     * get snom phone template
     *
     * @return array
     */
    protected function _getSnomTemplate()
    {
        $testSoftware = $this->_getSnomSoftware();
        $returnedSoftware = $this->_backend->saveSnomSoftware(Zend_Json::encode($testSoftware));
        
        $testSetting = $this->_getSnomSetting();
        $returnedSetting = $this->_backend->saveSnomSetting(Zend_Json::encode($testSetting));
        
        return array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'setting_id' => $returnedSetting['updatedData']['id'],
            'software_id' => $returnedSoftware['updatedData']['id']
        );
    }
}		
