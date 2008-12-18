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
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskContext($contextId) as well
        $returnedGet = $this->_backend->getAsteriskContext($returned['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_backend->deleteAsteriskContexts(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk context
     *
     */
    public function testUpdateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_backend->saveAsteriskContext(Zend_Json::encode($test));
        $returned['name'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskContext(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteAsteriskContexts(Zend_Json::encode(array($returned['id'])));
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
        
        $this->_backend->deleteAsteriskContexts(Zend_Json::encode(array($returned['id'])));
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
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['context'], $returned['context']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskSipPeer($SipPeerId) as well
        $returnedGet = $this->_backend->getAsteriskSipPeer($returned['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['context'], $returnedGet['context']);
        $this->_backend->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk SipPeer
     *
     */
    public function testUpdateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_backend->saveAsteriskSipPeer(Zend_Json::encode($test));
        $returned['name'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskSipPeer(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['context'], $updated['context']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['id'])));
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
        
        $this->_backend->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['id'])));
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
        
        $this->assertEquals($test['confno'], $returned['confno']);
        $this->assertEquals($test['adminpin'], $returned['adminpin']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskMeetme($meetmeId) as well
        $returnedGet = $this->_backend->getAsteriskMeetme($returned['id']);
        //print_r($returnedGet);
        $this->assertEquals($test['confno'], $returnedGet['confno']);
        $this->assertEquals($test['adminpin'], $returnedGet['adminpin']);
        
        $this->_backend->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk meetme room
     *
     */
    public function testUpdateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_backend->saveAsteriskMeetme(Zend_Json::encode($test));
        $returned['adminpin'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskMeetme(Zend_Json::encode($returned));
        $this->assertEquals($returned['confno'], $updated['confno']);
        $this->assertEquals($returned['adminpin'], $updated['adminpin']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['id'])));
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
        
        $this->_backend->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['id'])));
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
        $this->assertEquals($test['context'], $returned['context']);
        $this->assertEquals($test['fullname'], $returned['fullname']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskVoicemail as well
        $returnedGet = $this->_backend->getAsteriskVoicemail($returned['id']);
        // print_r($returnedGet)
        $this->assertEquals($test['context'], $returnedGet['context']);
        $this->assertEquals($test['fullname'], $returnedGet['fullname']);
        
        $this->_backend->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk voice mail
     *
     */
    public function testUpdateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $returned = $this->_backend->saveAsteriskVoicemail(Zend_Json::encode($test));
        $returned['fullname'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveAsteriskVoicemail(Zend_Json::encode($returned));
        $this->assertEquals($returned['context'], $updated['context']);
        $this->assertEquals($returned['fullname'], $updated['fullname']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['id'])));
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
        
        $this->_backend->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['id'])));
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
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertEquals($test['registrar'], $returned['registrar']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomLocation as well
        $returnedGet = $this->_backend->getSnomLocation($returned['id']);
        // print_r($returnedGet)
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        $this->assertEquals($test['registrar'], $returnedGet['registrar']);
        
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of snom location
     *
     */
    public function testUpdateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $returned = $this->_backend->saveSnomLocation(Zend_Json::encode($test));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomLocation(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertEquals($returned['registrar'], $updated['registrar']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['id'])));
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
        
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['id'])));
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
        
        $this->assertEquals($testPhone['description'], $returned['description']);
        $this->assertEquals(strtoupper($testPhone['macaddress']), $returned['macaddress']);
        $this->assertEquals($testPhone['location_id'], $returned['location_id']);
        $this->assertEquals($testPhone['template_id'], $returned['template_id']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomPhone as well
        $returnedGet = $this->_backend->getSnomPhone($returned['id']);
        
        $this->assertEquals($testPhone['description'], $returnedGet['description']);
        $this->assertEquals(strtoupper($testPhone['macaddress']), $returnedGet['macaddress']);
        $this->assertEquals($testPhone['location_id'], $returnedGet['location_id']);
        $this->assertEquals($testPhone['template_id'], $returnedGet['template_id']);
        
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['location_id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['template_id'])));
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
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $phoneTemplate = $this->_backend->getSnomTemplate($testPhone['template_id']);
        
        $updated = $this->_backend->saveSnomPhone(Zend_Json::encode($returned), Zend_Json::encode($returned['lines']), Zend_Json::encode($returned['rights']));
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertEquals($returned['macaddress'], $updated['macaddress']);
        $this->assertEquals($returned['location_id'], $updated['location_id']);
        $this->assertEquals($returned['template_id'], $updated['template_id']);
        $this->assertNotNull($updated['id']);
        
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['location_id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['template_id'])));
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
        
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($returned['location_id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['template_id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($phoneTemplate['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($phoneTemplate['setting_id'])));
    }
    
    /**
     * reset http client info
     *
     * @return array
     */
    
    public function testResetHttpClientInfo()
    {
        $testPhone = $this->_getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returned = $this->_backend->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
        $this->_backend->resetHttpClientInfo(Zend_Json::encode(array($returned['id'])));
        
        // delete everything
        $location_id = $testPhone['location_id'];
        $template_id = $testPhone['template_id'];
        
        $phoneTemplate = $this->_backend->getSnomTemplate($template_id);
        
        $settings_id = $phoneTemplate['setting_id'];  
        $software_id = $phoneTemplate['software_id'];
        
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($template_id)));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($location_id)));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($settings_id)));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($software_id)));
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
            'location_id' => $returnedLocation['id'],
            'template_id' => $returnedTemplate['id'],
            'current_model' => 'snom300',
            'redirect_event' => 'none',
            'http_client_info_sent' => '1',
            'http_client_user' => Tinebase_Record_Abstract::generateUID(),
            'http_client_pass' => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Snom Phone Settings tests **/
    
    /**
     * test update of snom phone settings
     *
     */
    public function testUpdateSnomPhoneSettings()
    {
        $test = $this->_getSnomPhoneSettings();
        
        $returned = $this->_backend->getSnomPhoneSettings($test['phone_id']);
        $returned['web_language'] = 'Deutsch';
                
        $updated = $this->_backend->saveSnomPhoneSettings(Zend_Json::encode($returned));
        $this->assertEquals($returned['web_language'], $updated['web_language']);
        $this->assertNotNull($updated['phone_id']);
        
        // delete everything
        $settingsPhone = $this->_backend->getSnomPhone($test['phone_id']);
        
        $location_id = $settingsPhone['location_id'];
        $template_id = $settingsPhone['template_id'];
        
        $phoneTemplate = $this->_backend->getSnomTemplate($template_id);
        
        $settings_id = $phoneTemplate['setting_id'];  
        $software_id = $phoneTemplate['software_id'];
        
        $this->_backend->deleteSnomPhoneSettings(Zend_Json::encode(array($returned['phone_id'])));
        $this->_backend->deleteSnomPhones(Zend_Json::encode(array($settingsPhone['id'])));
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($template_id)));
        $this->_backend->deleteSnomLocations(Zend_Json::encode(array($location_id)));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($settings_id)));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($software_id)));
    }
    
    /**
     * get snom phone settings data
     *
     * @return array
     */
    protected function _getSnomPhoneSettings()
    {
        $testPhone = $this->_getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returnedPhone = $this->_backend->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
        return array(
            'phone_id'  => $returnedPhone['id'],
            'web_language' => 'English'
        );
    }
    
    /**
     * test creation of snom settings
     *
     */
    public function testCreateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomSettings as well
        $returnedGet = $this->_backend->getSnomSetting($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of snom settings
     *
     */
    public function testUpdateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_backend->saveSnomSetting(Zend_Json::encode($test));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomSetting(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['id'])));
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
        
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['id'])));
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
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomSoftware as well
        $returnedGet = $this->_backend->getSnomSoftware($returned['id']);
        // print_r($returnedGet)
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of snom software
     *
     */
    public function testUpdateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $returned = $this->_backend->saveSnomSoftware(Zend_Json::encode($test));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomSoftware(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
                
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['id'])));
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
        
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['id'])));
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
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['setting_id'], $returned['setting_id']);
        $this->assertEquals($test['software_id'], $returned['software_id']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomTemplate as well
        $returnedGet = $this->_backend->getSnomTemplate($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['setting_id'], $returnedGet['setting_id']);
        $this->assertEquals($test['software_id'], $returnedGet['software_id']);
        
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['setting_id'])));
    }
    
    /**
     * test update of snom template
     *
     */
    public function testUpdateSnomTemplate()
    {   
        $test = $this->_getSnomTemplate();
        $returned = $this->_backend->saveSnomTemplate(Zend_Json::encode($test));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_backend->saveSnomTemplate(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['setting_id'], $updated['setting_id']);
        $this->assertEquals($returned['software_id'], $updated['software_id']);
        $this->assertNotNull($updated['id']);
        
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['setting_id'])));
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
        
        $this->_backend->deleteSnomTemplates(Zend_Json::encode(array($returned['id'])));
        $this->_backend->deleteSnomSoftware(Zend_Json::encode(array($returned['software_id'])));
        $this->_backend->deleteSnomSettings(Zend_Json::encode(array($returned['setting_id'])));
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
            'setting_id' => $returnedSetting['id'],
            'software_id' => $returnedSoftware['id']
        );
    }
}		
