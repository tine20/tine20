<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        add more tests
 * @todo        use $this->_toDelete in more tests
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
    protected $_json = array();
    
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
     */
    public function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_json = new Voipmanager_Frontend_Json();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /** Asterisk Context tests **/
    
    /**
     * test creation of asterisk context
     *
     */
    public function testCreateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_json->saveAsteriskContext($test->toArray());
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskContext($contextId) as well
        $returnedGet = $this->_json->getAsteriskContext($returned['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
    }
    
    /**
     * test update of asterisk context
     *
     */
    public function testUpdateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_json->saveAsteriskContext($test->toArray());
        $returned['name'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveAsteriskContext($returned);
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
    }
    
    /**
     * test search of asterisk context
     *
     */
    public function testSearchAsteriskContext()
    {
        // create
        $asteriskContext = $this->_getAsteriskContext();
        $asteriskContextData = $this->_json->saveAsteriskContext($asteriskContext->toArray());
        
        // search & check
        $search = $this->_json->searchAsteriskContexts($this->_getAsteriskContextFilter($asteriskContext->name), $this->_getPaging());
        $this->assertEquals($asteriskContext->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get asterisk context data
     *
     * @return array
     */
    protected function _getAsteriskContext()
    {
        return new Voipmanager_Model_Asterisk_Context(array(
            'name'         => Tinebase_Record_Abstract::generateUID(),
            'description'  => 'blabla'
        ), TRUE);
    }
    
    /**
     * get Asterisk Content filter
     *
     * @return array
     */
    protected function _getAsteriskContextFilter($_name)
    {
        return array(
            array(
                'field'    => 'description', 
                'operator' => 'contains', 
                'value'    => $_name
            )
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
        
        $returned = $this->_json->saveAsteriskSipPeer($test->toArray());
        $this->assertEquals($test['name'], $returned['name']);
        //print_r($returned);
        $this->assertEquals($test['context'], $returned['context']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskSipPeer($SipPeerId) as well
        $returnedGet = $this->_json->getAsteriskSipPeer($returned['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['context'], $returnedGet['context']);
    }
    
    /**
     * test update of asterisk SipPeer
     *
     */
    public function testUpdateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_json->saveAsteriskSipPeer($test->toArray());
        //print_r($returned);
        $returned['name'] = Tinebase_Record_Abstract::generateUID();
        $returned['context_id'] = $returned['context_id']['value'];
        $regseconds = $returned['regseconds'];
        $returned['regseconds'] = 123;
        
        $updated = $this->_json->saveAsteriskSipPeer($returned);
        
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($regseconds, $updated['regseconds'], 'regseconds should not be updated');
        $this->assertEquals($returned['context_id'], $updated['context_id']['value']);
        $this->assertNotNull($updated['id']);
    }

    /**
     * test update properties of asterisk SipPeer
     *
     */
    public function testUpdatePropertiesAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_json->saveAsteriskSipPeer($test->toArray());
        
        // update regseconds
        $now = Tinebase_DateTime::now();
        $data = array('regseconds' => $now);
        $updated = $this->_json->updatePropertiesAsteriskSipPeer($returned['id'], $data);
        
        //print_r($updated);
        date_default_timezone_set(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $now->setTimezone(Tinebase_Core::get(Tinebase_Core::USERTIMEZONE));
        $nowConverted = $now->get(Tinebase_Record_Abstract::ISO8601LONG);
        date_default_timezone_set('UTC');
        $this->assertEquals($nowConverted, $updated['regseconds']);
    }
    
    /**
     * test search of asterisk SipPeer
     *
     */
    public function testSearchAsteriskSipPeer()
    {
        // create
        $asteriskSipPeer = $this->_getAsteriskSipPeer();
        $asteriskSipPeerData = $this->_json->saveAsteriskSipPeer($asteriskSipPeer->toArray());
        
        // search & check
        $search = $this->_json->searchAsteriskSipPeers($this->_getAsteriskSipPeerFilter($asteriskSipPeer->name), $this->_getPaging());
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals($asteriskSipPeer->name, $search['results'][0]['name']);
    }
    
    /**
     * get asterisk SipPeer data
     *
     * @return array
     */
    protected function _getAsteriskSipPeer()
    {
        // create context
        $context = $this->_getAsteriskContext();
        $context = $this->_json->saveAsteriskContext($context->toArray());
        
        return new Voipmanager_Model_Asterisk_SipPeer(array(
            'name'       => Tinebase_Record_Abstract::generateUID(),
            'context'    => $context['name'],
            'context_id' => $context['id']
        ), TRUE);
    }
    
    /**
     * get Asterisk SipPeer filter
     *
     * @param string $_name to search for
     * @return array
     */
    protected function _getAsteriskSipPeerFilter($_name)
    {
        return array(
            array(
                'field'    => 'query', 
                'operator' => 'contains', 
                'value'    => $_name
            )
        );
    }
    
    
    
    /** Asterisk Meetme tests **/
    
    /**
     * test creation of asterisk meetme room
     *
     */
    public function testCreateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_json->saveAsteriskMeetme($test->toArray());
        
        $this->assertEquals($test['confno'], $returned['confno']);
        $this->assertEquals($test['adminpin'], $returned['adminpin']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskMeetme($meetmeId) as well
        $returnedGet = $this->_json->getAsteriskMeetme($returned['id']);
        
        $this->assertEquals($test['confno'], $returnedGet['confno']);
        $this->assertEquals($test['adminpin'], $returnedGet['adminpin']);
    }
    
    /**
     * test update of asterisk meetme room
     *
     */
    public function testUpdateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_json->saveAsteriskMeetme($test->toArray());
        $returned['adminpin'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveAsteriskMeetme($returned);
        $this->assertEquals($returned['confno'], $updated['confno']);
        $this->assertEquals($returned['adminpin'], $updated['adminpin']);
        $this->assertNotNull($updated['id']);
    }
    
    /**
     * test search of asterisk meetme room
     *
     */
    public function testSearchAsteriskMeetme()
    {
        // create
        $asteriskMeetme = $this->_getAsteriskMeetme();
        $asteriskMeetmeData = $this->_json->saveAsteriskMeetme($asteriskMeetme->toArray());
        
        // search & check
        $search = $this->_json->searchAsteriskMeetmes($this->_getAsteriskMeetmeFilter($asteriskMeetme->confno), $this->_getPaging());
        $this->assertEquals($asteriskMeetme->confno, $search['results'][0]['confno']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get asterisk meetme data
     *
     * @return array
     */
    protected function _getAsteriskMeetme()
    {
        return new Voipmanager_Model_Asterisk_Meetme(array(
            'confno'   => Tinebase_Record_Abstract::generateUID(),
            'adminpin' => Tinebase_Record_Abstract::generateUID(),
            'pin'      => Tinebase_Record_Abstract::generateUID(),
            'members'  => 1
        ), TRUE);
    }
    
    /**
     * get Asterisk Meetme filter
     *
     * @return array
     */
    protected function _getAsteriskMeetmeFilter($_name)
    {
        return array(
            array(
                'field'    => 'confno', 
                'operator' => 'contains', 
                'value'    => $_name
            )
        );
    }
    
    
    
    /** Asterisk Voicemail tests **/
    
    /**
     * test creation of asterisk voice mail
     *
     */
    public function testCreateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $returned = $this->_json->saveAsteriskVoicemail($test->toArray());
        
        $this->assertEquals($test['context'], $returned['context']);
        $this->assertEquals($test['fullname'], $returned['fullname']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskVoicemail as well
        $returnedGet = $this->_json->getAsteriskVoicemail($returned['id']);
        
        $this->assertEquals($test['context'], $returnedGet['context']);
        $this->assertEquals($test['fullname'], $returnedGet['fullname']);
    }
    
    /**
     * test update of asterisk voice mail
     *
     */
    public function testUpdateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $returned = $this->_json->saveAsteriskVoicemail($test->toArray());
        $returned['fullname'] = Tinebase_Record_Abstract::generateUID();
        $returned['context_id'] = $returned['context_id']['value'];
        
        $updated = $this->_json->saveAsteriskVoicemail($returned);
        $this->assertEquals($returned['context'], $updated['context']);
        $this->assertEquals($returned['fullname'], $updated['fullname']);
        $this->assertNotNull($updated['id']);
    }
    
    /**
     * test search of asterisk voicemail
     *
     */
    public function testSearchAsteriskVoicemail()
    {
        // create
        $asteriskVoicemail = $this->_getAsteriskVoicemail();
        $asteriskVoicemailData = $this->_json->saveAsteriskVoicemail($asteriskVoicemail->toArray());
        
        // search & check
        $search = $this->_json->searchAsteriskVoicemails($this->_getAsteriskVoicemailFilter($asteriskVoicemail->context), $this->_getPaging());
        $this->assertEquals($asteriskVoicemail->context, $search['results'][0]['context']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get Asterisk Voicemail data
     *
     * @return array
     */
    protected function _getAsteriskVoicemail()
    {
        // create context
        $context = $this->_getAsteriskContext();
        $context = $this->_json->saveAsteriskContext($context->toArray());
        
        return new Voipmanager_Model_Asterisk_Voicemail(array(
            'context'    => $context['name'],
            'context_id' => $context['id'],
            'fullname'   => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    /**
     * get Asterisk Voicemail filter
     *
     * @return array
     */
    protected function _getAsteriskVoicemailFilter($_name)
    {
        return array(
            array(
                'field'    => 'context', 
                'operator' => 'contains', 
                'value'    => $_name
            )
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
        
        $returned = $this->_json->saveSnomLocation($test->toArray());
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertEquals($test['registrar'], $returned['registrar']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomLocation as well
        $returnedGet = $this->_json->getSnomLocation($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        $this->assertEquals($test['registrar'], $returnedGet['registrar']);
    }
    
    /**
     * test update of snom location
     *
     */
    public function testUpdateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $returned = $this->_json->saveSnomLocation($test->toArray());
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveSnomLocation($returned);
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertEquals($returned['registrar'], $updated['registrar']);
        $this->assertNotNull($updated['id']);
    }
    
    /**
     * test search of snom location
     *
     */
    public function testSearchSnomLocation()
    {
        // create
        $snomLocation = $this->_getSnomLocation();
        $snomLocationData = $this->_json->saveSnomLocation($snomLocation->toArray());
        
        // search & check
        $search = $this->_json->searchSnomLocations($this->_getSnomLocationFilter($snomLocation->name), $this->_getPaging());
        $this->assertEquals($snomLocation->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get snom location data
     *
     * @return array
     */
    protected function _getSnomLocation()
    {
        return new Voipmanager_Model_Snom_Location(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID(),
            'registrar'   => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    /**
     * get Snom Location filter
     *
     * @return array
     */
    protected function _getSnomLocationFilter($_name)
    {
        return array(
            array(
                'field'    => 'name', 
                'operator' => 'contains', 
                'value'    => $_name
            )
        );
    }
    
    
    
    /** Snom Phone tests **/
    
    /**
     * test creation of snom phone
     *
     */
    public function testCreateSnomPhone()
    {
        $testPhone = $this->getSnomPhone();
        
        $lineData   = array();
        $rightsData = array();
        
        $returned = $this->_json->saveSnomPhone($testPhone, $lineData, $rightsData);
        
        $phoneTemplate = $this->_json->getSnomTemplate($testPhone['template_id']);
        
        $this->assertEquals($testPhone['description'], $returned['description']);
        $this->assertEquals(strtoupper($testPhone['macaddress']), $returned['macaddress']);
        $this->assertEquals($testPhone['location_id'], $returned['location_id']['value']);
        $this->assertEquals($testPhone['template_id'], $returned['template_id']['value']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomPhone as well
        $returnedGet = $this->_json->getSnomPhone($returned['id']);
        
        $this->assertEquals($testPhone['description'], $returnedGet['description']);
        $this->assertEquals(strtoupper($testPhone['macaddress']), $returnedGet['macaddress']);
        $this->assertEquals($testPhone['location_id'], $returnedGet['location_id']['value']);
        $this->assertEquals($testPhone['template_id'], $returnedGet['template_id']['value']);
    }
    
    /**
     * test update of snom phone
     *
     */
    public function testUpdateSnomPhone()
    {
        $testPhone = $this->getSnomPhone();
        
        $lineData   = array();
        $rightsData = array();
        
        $returned = $this->_json->saveSnomPhone($testPhone, $lineData, $rightsData);
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        $returned['template_id'] = $returned['template_id']['value'];
        $returned['location_id'] = $returned['location_id']['value'];
        
        $phoneTemplate = $this->_json->getSnomTemplate($testPhone['template_id']);
        
        $updated = $this->_json->saveSnomPhone($returned, $returned['lines'], $returned['rights']);
        
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertEquals($returned['macaddress'], $updated['macaddress']);
        $this->assertEquals($returned['location_id'], $updated['location_id']['value']);
        $this->assertEquals($returned['template_id'], $updated['template_id']['value']);
        $this->assertNotNull($updated['id']);
    }
    
/**
     * test update of snom phone
     *
     */
    public function testCreateSnomPhoneWithLines()
    {
        $testPhone = $this->getSnomPhone();
        $sipPeer = $this->_json->saveAsteriskSipPeer($this->_getAsteriskSipPeer()->toArray());
        $this->_toDelete['sippeer'][] = $sipPeer;
        $sipPeer['context_id'] = $sipPeer['context_id']['value'];
        $sipPeer['cfi_mode'] = 'number';
        $snomLine = new Voipmanager_Model_Snom_Line(array(
            'asteriskline_id' => $sipPeer,
            'linenumber'      => 1,
            'lineactive'      => 1,
            'idletext'        => 'idle'
        ));
        $testPhone['lines'] = array($snomLine->toArray());
        
        // save
        $returned = $this->_json->saveSnomPhone($testPhone);
        
        // check result
        $this->assertGreaterThan(0, count($returned['lines']));
        $this->assertEquals($sipPeer, $returned['lines'][0]['asteriskline_id']);
    }
    
    /**
     * test search of snom phone
     *
     */
    public function testSearchSnomPhone()
    {
        $testPhone = $this->getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returned = $this->_json->saveSnomPhone($testPhone, $lineData, $rightsData);
        
        $phoneTemplate = $this->_json->getSnomTemplate($testPhone['template_id']);
        $phoneLocation = $this->_json->getSnomLocation($testPhone['location_id']);
        
        $searchResult = $this->_json->searchSnomPhones($this->getSnomPhoneFilter($testPhone['description']), $this->_getPaging());
        
        $this->assertEquals(1, $searchResult['totalcount']);
        $this->assertEquals($phoneTemplate['name'], $searchResult['results'][0]['template']);
        $this->assertEquals($phoneLocation['name'], $searchResult['results'][0]['location']);
    }
    
    /**
     * get Snom Phone filter
     *
     * @return array
     */
    protected function getSnomPhoneFilter($_name)
    {
        return array(
            array(
                'field'    => 'description', 
                'operator' => 'contains', 
                'value'    => $_name
            )
        );
    }
    
    /**
     * reset http client info
     *
     * @return array
     * 
     * @todo this test does nothing ... add assertions
     */
    public function testResetHttpClientInfo()
    {
        /*
        $testPhone = $this->getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returned = $this->_json->saveSnomPhone($testPhone, $lineData, $rightsData);
        
        $this->_json->resetHttpClientInfo(array($returned['id']));
        
        $location_id = $testPhone['location_id'];
        $template_id = $testPhone['template_id'];
        
        $phoneTemplate = $this->_json->getSnomTemplate($template_id);
        
        $settings_id = $phoneTemplate['setting_id'];
        $software_id = $phoneTemplate['software_id'];
        
        print_r($settings_id);
        
        // delete everything
        $this->_json->deleteSnomPhones(array($returned['id']);
        $this->_json->deleteSnomTemplates(array($template_id));
        $this->_json->deleteSnomLocations(array($location_id));
        $this->_json->deleteSnomSettings(array($settings_id));
        $this->_json->deleteSnomSoftwares(array($software_id));
        */
    }
    
    /**
     * get snom phone data
     *
     * @return array
     */
    public function getSnomPhone()
    {
        $testLocation = $this->_getSnomLocation();
        $returnedLocation = $this->_json->saveSnomLocation($testLocation->toArray());
        
        $testTemplate = $this->_getSnomTemplate();
        $returnedTemplate = $this->_json->saveSnomTemplate($testTemplate->toArray());
        
        return array(
            'description'       => Tinebase_Record_Abstract::generateUID(),
            'macaddress'        => substr(Tinebase_Record_Abstract::generateUID(), 0, 12),
            'location_id'       => $returnedLocation['id'],
            'template_id'       => $returnedTemplate['id'],
            'current_model'     => 'snom300',
            'redirect_event'    => 'none',
            'http_client_info_sent' => '1',
            'http_client_user'  => Tinebase_Record_Abstract::generateUID(),
            'http_client_pass'  => Tinebase_Record_Abstract::generateUID()
        );
    }
    
    /** Snom Phone Settings tests **/
    
    /**
     * test update of snom phone settings
     *
     */
    public function testUpdateSnomPhoneSettings()
    {
        $test = $this->getSnomPhoneSettings();
        
        $returned = $this->_json->getSnomPhoneSettings($test['phone_id']);
        
        $returned['web_language'] = 'Deutsch';
        
        $updated = $this->_json->saveSnomPhoneSettings($returned);
        $this->assertEquals($returned['web_language'], $updated['web_language']);
        $this->assertNotNull($updated['phone_id']);
        
        // delete everything
        $settingsPhone = $this->_json->getSnomPhone($test['phone_id']);
        
        $location_id = $settingsPhone['location_id']['value'];
        $template_id = $settingsPhone['template_id']['value'];
        
        $phoneTemplate = $this->_json->getSnomTemplate($template_id);
        
        $settings_id = $phoneTemplate['setting_id']['value'];
        $software_id = $phoneTemplate['software_id']['value'];
    }
    
    /**
     * get snom phone settings data
     *
     * @return array
     */
    protected function getSnomPhoneSettings()
    {
        $testPhone = $this->getSnomPhone();
        
        $lineData   = array();
        $rightsData = array();
        
        $returnedPhone = $this->_json->saveSnomPhone($testPhone, $lineData, $rightsData);
        
        return array(
            'phone_id'     => $returnedPhone['id'],
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
        $returned = $this->_json->saveSnomSetting($test->toArray());
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomSettings as well
        $returnedGet = $this->_json->getSnomSetting($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
    }
    
    /**
     * test update of snom settings
     *
     */
    public function testUpdateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_json->saveSnomSetting($test->toArray());
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveSnomSetting($returned);
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
    }
    
    /**
     * test search of snom settings
     *
     */
    public function testSearchSnomSetting()
    {
        // create
        $snomSetting = $this->_getSnomSetting();
        $snomSettingData = $this->_json->saveSnomSetting($snomSetting->toArray());
        
        // search & check
        $search = $this->_json->searchSnomSettings($this->_getSnomSettingFilter($snomSetting->name), $this->_getPaging());
        $this->assertEquals($snomSetting->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get snom settings data
     *
     * @return array
     */
    protected function _getSnomSetting()
    {
        return new Voipmanager_Model_Snom_Setting(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    /**
     * get Snom Setting filter
     *
     * @return array
     */
    protected function _getSnomSettingFilter($_name)
    {
        return array(
            array(
                'field'    => 'name', 
                'operator' => 'contains', 
                'value'    => $_name
            )
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
        
        $returned = $this->_json->saveSnomSoftware($test->toArray());
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomSoftware as well
        $returnedGet = $this->_json->getSnomSoftware($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
    }
    
    /**
     * test update of snom software
     *
     */
    public function testUpdateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $returned = $this->_json->saveSnomSoftware($test->toArray());
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveSnomSoftware($returned);
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
    }
    
    /**
     * test search of snom software
     *
     */
    public function testSearchSnomSoftware()
    {
        // create
        $snomSoftware = $this->_getSnomSoftware();
        $snomSoftwareData = $this->_json->saveSnomSoftware($snomSoftware->toArray());
        
        // search & check
        $search = $this->_json->searchSnomSoftwares($this->_getSnomSoftwareFilter($snomSoftware->name), $this->_getPaging());
        $this->assertEquals($snomSoftware->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get Snom Software filter
     *
     * @return array
     */
    protected function _getSnomSoftwareFilter($_name)
    {
        return array(
            array(
                'field'    => 'name', 
                'operator' => 'contains', 
                'value'    => $_name
            )
        );
    }
    
    /**
     * get snom software data
     *
     * @return array
     */
    protected function _getSnomSoftware()
    {
        return new Voipmanager_Model_Snom_Software(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        ), TRUE);
    }
    
    
    
    /** Snom Template tests **/
    
    /**
     * test creation of snom template
     *
     */
    public function testCreateSnomTemplate()
    {
        $test = $this->_getSnomTemplate();
        $returned = $this->_json->saveSnomTemplate($test->toArray());
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['setting_id'], $returned['setting_id']['value']);
        $this->assertEquals($test['software_id'], $returned['software_id']['value']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomTemplate as well
        $returnedGet = $this->_json->getSnomTemplate($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['setting_id'], $returnedGet['setting_id']['value']);
        $this->assertEquals($test['software_id'], $returnedGet['software_id']['value']);
    }
    
    /**
     * test update of snom template
     *
     */
    public function testUpdateSnomTemplate()
    {
        $test = $this->_getSnomTemplate();
        $returned = $this->_json->saveSnomTemplate($test->toArray());
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        $returned['software_id'] = $returned['software_id']['value'];
        $returned['setting_id'] = $returned['setting_id']['value'];
        
        $updated = $this->_json->saveSnomTemplate($returned);
        
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['setting_id'], $updated['setting_id']['value']);
        $this->assertEquals($returned['software_id'], $updated['software_id']['value']);
        $this->assertNotNull($updated['id']);
    }
    
    /**
     * test search of snom template
     *
     */
    public function testSearchSnomTemplate()
    {
        // create
        $snomTemplate = $this->_getSnomTemplate();
        $snomTemplateData = $this->_json->saveSnomTemplate($snomTemplate->toArray());
        
        // search & check
        $search = $this->_json->searchSnomTemplates($this->_getSnomTemplateFilter($snomTemplate->name), $this->_getPaging());
        $this->assertEquals($snomTemplate->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
    }
    
    /**
     * get snom phone template
     *
     * @return array
     */
    protected function _getSnomTemplate()
    {
        $testSoftware = $this->_getSnomSoftware();
        $returnedSoftware = $this->_json->saveSnomSoftware($testSoftware->toArray());
        
        $testSetting = $this->_getSnomSetting();
        $returnedSetting = $this->_json->saveSnomSetting($testSetting->toArray());
        
        return new Voipmanager_Model_Snom_Template(array(
            'name'        => Tinebase_Record_Abstract::generateUID(),
            'setting_id'  => $returnedSetting['id'],
            'software_id' => $returnedSoftware['id']
        ), TRUE);
    }
    
    /**
     * get Snom Template filter
     *
     * @return array
     */
    protected function _getSnomTemplateFilter($_name)
    {
        return array(
            array(
                'field'    => 'name', 
                'operator' => 'contains', 
                'value'    => $_name
            )
        );
    }
    
    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort'  => 'id',
            'dir'   => 'ASC'
        );
    }
}
