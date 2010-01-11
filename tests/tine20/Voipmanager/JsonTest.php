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
     *
     * @access protected
     */
    protected function setUp()
    {
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
        // delete all contexts
        $search = $this->_json->searchAsteriskContexts('', '');
        foreach ($search['results'] as $result) {
            try {
                $this->_json->deleteAsteriskContexts($result['id']);
            } catch (Zend_Db_Statement_Exception $zdse) {
                // integrity constraint
            }
        }        
    }
    
    /** Asterisk Context tests **/
    
    /**
     * test creation of asterisk context
     *
     */
    public function testCreateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_json->saveAsteriskContext(Zend_Json::encode($test->toArray()));
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskContext($contextId) as well
        $returnedGet = $this->_json->getAsteriskContext($returned['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        $this->_json->deleteAsteriskContexts(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk context
     *
     */
    public function testUpdateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_json->saveAsteriskContext(Zend_Json::encode($test->toArray()));
        $returned['name'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveAsteriskContext(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
        
        $this->_json->deleteAsteriskContexts(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test search of asterisk context
     *
     */
    public function testSearchAsteriskContext()
    {
        // create
        $asteriskContext = $this->_getAsteriskContext();
        $asteriskContextData = $this->_json->saveAsteriskContext(Zend_Json::encode($asteriskContext->toArray()));
        
        // search & check
        $search = $this->_json->searchAsteriskContexts(Zend_Json::encode($this->_getAsteriskContextFilter($asteriskContext->name)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($asteriskContext->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteAsteriskContexts($asteriskContextData['id']);
        /***********************************************************************************/
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
            'description'   => 'blabla',
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
                'field' => 'description', 
                'operator' => 'contains', 
                'value' => $_name
            ),   
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
        
        $returned = $this->_json->saveAsteriskSipPeer(Zend_Json::encode($test->toArray()));
        $this->assertEquals($test['name'], $returned['name']);
        //print_r($returned);
        $this->assertEquals($test['context'], $returned['context']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskSipPeer($SipPeerId) as well
        $returnedGet = $this->_json->getAsteriskSipPeer($returned['id']);
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['context'], $returnedGet['context']);
        $this->_json->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk SipPeer
     *
     */
    public function testUpdateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_json->saveAsteriskSipPeer(Zend_Json::encode($test->toArray()));
        //print_r($returned);
        $returned['name'] = Tinebase_Record_Abstract::generateUID();
        $returned['context_id'] = $returned['context_id']['value'];
        $regseconds = $returned['regseconds'];
        $returned['regseconds'] = 123;
        
        $updated = $this->_json->saveAsteriskSipPeer(Zend_Json::encode($returned));
        
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($regseconds, $updated['regseconds'], 'regseconds should not be updated');
        $this->assertEquals($returned['context_id'], $updated['context_id']['value']);
        $this->assertNotNull($updated['id']);
        
        $this->_json->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['id'])));
    }

    /**
     * test update properties of asterisk SipPeer
     *
     */
    public function testUpdatePropertiesAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_json->saveAsteriskSipPeer(Zend_Json::encode($test->toArray()));
        
        // update regseconds
        $data = array('regseconds' => 123);
        $updated = $this->_json->updatePropertiesAsteriskSipPeer($returned['id'], Zend_Json::encode($data));
        
        //print_r($updated);
        
        $this->assertEquals('1970-01-01 01:02:03', $updated['regseconds']);
        
        $this->_json->deleteAsteriskSipPeers(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test search of asterisk SipPeer
     *
     */
    public function testSearchAsteriskSipPeer()
    {
        // create
        $asteriskSipPeer = $this->_getAsteriskSipPeer();        
        $asteriskSipPeerData = $this->_json->saveAsteriskSipPeer(Zend_Json::encode($asteriskSipPeer->toArray()));
        
        // search & check
        $search = $this->_json->searchAsteriskSipPeers(Zend_Json::encode($this->_getAsteriskSipPeerFilter($asteriskSipPeer->name)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals(1, $search['totalcount']);
        $this->assertEquals($asteriskSipPeer->name, $search['results'][0]['name']);
        
        // cleanup
        $this->_json->deleteAsteriskSipPeers($asteriskSipPeerData['id']);
        /***********************************************************************************/
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
        $context = $this->_json->saveAsteriskContext(Zend_Json::encode($context->toArray()));
        
        return new Voipmanager_Model_Asterisk_SipPeer(array(
            'name'          => Tinebase_Record_Abstract::generateUID(),
            'context'       => $context['name'],
            'context_id'    => $context['id']
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
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => $_name
            ),  
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
        
        $returned = $this->_json->saveAsteriskMeetme(Zend_Json::encode($test->toArray()));
        
        $this->assertEquals($test['confno'], $returned['confno']);
        $this->assertEquals($test['adminpin'], $returned['adminpin']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskMeetme($meetmeId) as well
        $returnedGet = $this->_json->getAsteriskMeetme($returned['id']);
        
        $this->assertEquals($test['confno'], $returnedGet['confno']);
        $this->assertEquals($test['adminpin'], $returnedGet['adminpin']);
        
        $this->_json->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk meetme room
     *
     */
    public function testUpdateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_json->saveAsteriskMeetme(Zend_Json::encode($test->toArray()));
        $returned['adminpin'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveAsteriskMeetme(Zend_Json::encode($returned));
        $this->assertEquals($returned['confno'], $updated['confno']);
        $this->assertEquals($returned['adminpin'], $updated['adminpin']);
        $this->assertNotNull($updated['id']);
                
        $this->_json->deleteAsteriskMeetmes(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test search of asterisk meetme room
     *
     */
    public function testSearchAsteriskMeetme()
    {
        // create
        $asteriskMeetme = $this->_getAsteriskMeetme();        
        $asteriskMeetmeData = $this->_json->saveAsteriskMeetme(Zend_Json::encode($asteriskMeetme->toArray()));
        
        // search & check
        $search = $this->_json->searchAsteriskMeetmes(Zend_Json::encode($this->_getAsteriskMeetmeFilter($asteriskMeetme->confno)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($asteriskMeetme->confno, $search['results'][0]['confno']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteAsteriskMeetmes($asteriskMeetmeData['id']);
        /***********************************************************************************/
    }
    
    /**
     * get asterisk meetme data
     *
     * @return array
     */
    protected function _getAsteriskMeetme()
    {
        return new Voipmanager_Model_Asterisk_Meetme(array(
            'confno'  => Tinebase_Record_Abstract::generateUID(),
            'adminpin' => Tinebase_Record_Abstract::generateUID(),
            'pin' => Tinebase_Record_Abstract::generateUID(),
            'members' => 1,
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
                'field' => 'confno', 
                'operator' => 'contains', 
                'value' => $_name
            ),     
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
        
        $returned = $this->_json->saveAsteriskVoicemail(Zend_Json::encode($test->toArray()));
        
        $this->assertEquals($test['context'], $returned['context']);
        $this->assertEquals($test['fullname'], $returned['fullname']);
        $this->assertNotNull($returned['id']);
        
        // test getAsteriskVoicemail as well
        $returnedGet = $this->_json->getAsteriskVoicemail($returned['id']);
        
        $this->assertEquals($test['context'], $returnedGet['context']);
        $this->assertEquals($test['fullname'], $returnedGet['fullname']);
        
        $this->_json->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of asterisk voice mail
     *
     */
    public function testUpdateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $returned = $this->_json->saveAsteriskVoicemail(Zend_Json::encode($test->toArray()));
        $returned['fullname'] = Tinebase_Record_Abstract::generateUID();
        $returned['context_id'] = $returned['context_id']['value'];
        
        $updated = $this->_json->saveAsteriskVoicemail(Zend_Json::encode($returned));
        $this->assertEquals($returned['context'], $updated['context']);
        $this->assertEquals($returned['fullname'], $updated['fullname']);
        $this->assertNotNull($updated['id']);
                
        $this->_json->deleteAsteriskVoicemails(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test search of asterisk voicemail
     *
     */
    public function testSearchAsteriskVoicemail()
    {
        // create
        $asteriskVoicemail = $this->_getAsteriskVoicemail();        
        $asteriskVoicemailData = $this->_json->saveAsteriskVoicemail(Zend_Json::encode($asteriskVoicemail->toArray()));
        
        // search & check
        $search = $this->_json->searchAsteriskVoicemails(Zend_Json::encode($this->_getAsteriskVoicemailFilter($asteriskVoicemail->context)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($asteriskVoicemail->context, $search['results'][0]['context']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteAsteriskVoicemails($asteriskVoicemailData['id']);
        /***********************************************************************************/
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
        $context = $this->_json->saveAsteriskContext(Zend_Json::encode($context->toArray()));
        
        return new Voipmanager_Model_Asterisk_Voicemail(array(
            'context'       => $context['name'],
            'context_id'    => $context['id'],
            'fullname' => Tinebase_Record_Abstract::generateUID()
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
                'field' => 'context', 
                'operator' => 'contains', 
                'value' => $_name
            ),     
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
        
        $returned = $this->_json->saveSnomLocation(Zend_Json::encode($test->toArray()));
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertEquals($test['registrar'], $returned['registrar']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomLocation as well
        $returnedGet = $this->_json->getSnomLocation($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        $this->assertEquals($test['registrar'], $returnedGet['registrar']);
        
        $this->_json->deleteSnomLocations(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of snom location
     *
     */
    public function testUpdateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $returned = $this->_json->saveSnomLocation(Zend_Json::encode($test->toArray()));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveSnomLocation(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertEquals($returned['registrar'], $updated['registrar']);
        $this->assertNotNull($updated['id']);
                
        $this->_json->deleteSnomLocations(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test search of snom location
     *
     */
    public function testSearchSnomLocation()
    {
        // create
        $snomLocation = $this->_getSnomLocation();        
        $snomLocationData = $this->_json->saveSnomLocation(Zend_Json::encode($snomLocation->toArray()));
        
        // search & check
        $search = $this->_json->searchSnomLocations(Zend_Json::encode($this->_getSnomLocationFilter($snomLocation->name)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($snomLocation->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteSnomLocations($snomLocationData['id']);
        /***********************************************************************************/
    }
    
    /**
     * get snom location data
     *
     * @return array
     */
    protected function _getSnomLocation()
    {
        return new Voipmanager_Model_Snom_Location(array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID(),
            'registrar' => Tinebase_Record_Abstract::generateUID()
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
                'field' => 'name', 
                'operator' => 'contains', 
                'value' => $_name
            ),
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
        
        $returned = $this->_json->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
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
        
        $this->_json->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_json->deleteSnomLocations(Zend_Json::encode(array($returned['location_id']['value'])));
        $this->_json->deleteSnomTemplates(Zend_Json::encode(array($returned['template_id']['value'])));
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($phoneTemplate['software_id']['value'])));
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($phoneTemplate['setting_id']['value'])));
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
        
        $returned = $this->_json->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        $returned['template_id'] = $returned['template_id']['value'];
        $returned['location_id'] = $returned['location_id']['value'];
        
        $phoneTemplate = $this->_json->getSnomTemplate($testPhone['template_id']);
        
        $updated = $this->_json->saveSnomPhone(Zend_Json::encode($returned), Zend_Json::encode($returned['lines']), Zend_Json::encode($returned['rights']));
        
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertEquals($returned['macaddress'], $updated['macaddress']);
        $this->assertEquals($returned['location_id'], $updated['location_id']['value']);
        $this->assertEquals($returned['template_id'], $updated['template_id']['value']);
        $this->assertNotNull($updated['id']);
        
        $this->_json->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_json->deleteSnomLocations(Zend_Json::encode(array($returned['location_id'])));
        $this->_json->deleteSnomTemplates(Zend_Json::encode(array($returned['template_id'])));
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($phoneTemplate['software_id']['value'])));
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($phoneTemplate['setting_id']['value'])));
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
        
        $returned = $this->_json->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
        $phoneTemplate = $this->_json->getSnomTemplate($testPhone['template_id']);
        $phoneLocation = $this->_json->getSnomLocation($testPhone['location_id']);
        
        $searchResult = $this->_json->searchSnomPhones(Zend_Json::encode($this->_getSnomPhoneFilter($testPhone['description'])), Zend_Json::encode($this->_getPaging()));
        
        $this->assertEquals(1, $searchResult['totalcount']);
        $this->assertEquals($phoneTemplate['name'], $searchResult['results'][0]['template']);
        $this->assertEquals($phoneLocation['name'], $searchResult['results'][0]['location']);
        
        $this->_json->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_json->deleteSnomLocations(Zend_Json::encode(array($returned['location_id']['value'])));
        $this->_json->deleteSnomTemplates(Zend_Json::encode(array($returned['template_id']['value'])));
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($phoneTemplate['software_id']['value'])));
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($phoneTemplate['setting_id']['value'])));
    }
    
    /**
     * get Snom Phone filter
     *
     * @return array
     */
    protected function _getSnomPhoneFilter($_name)
    {
        return array(
            array(
                'field' => 'description', 
                'operator' => 'contains', 
                'value' => $_name
            ),
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
        $testPhone = $this->_getSnomPhone();
        
        $lineData = array();
        $rightsData = array();
        
        $returned = $this->_json->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
        $this->_json->resetHttpClientInfo(Zend_Json::encode(array($returned['id'])));
        
        $location_id = $testPhone['location_id'];
        $template_id = $testPhone['template_id'];
        
        $phoneTemplate = $this->_json->getSnomTemplate($template_id);
        
        $settings_id = $phoneTemplate['setting_id'];  
        $software_id = $phoneTemplate['software_id'];
        
        print_r($settings_id);
        
        // delete everything
        $this->_json->deleteSnomPhones(Zend_Json::encode(array($returned['id'])));
        $this->_json->deleteSnomTemplates(Zend_Json::encode(array($template_id)));
        $this->_json->deleteSnomLocations(Zend_Json::encode(array($location_id)));
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($settings_id)));
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($software_id)));
        */
    }
    
    /**
     * get snom phone data
     *
     * @return array
     */
    protected function _getSnomPhone()
    {
        $testLocation = $this->_getSnomLocation();
        $returnedLocation = $this->_json->saveSnomLocation(Zend_Json::encode($testLocation->toArray()));
        
        $testTemplate = $this->_getSnomTemplate();
        $returnedTemplate = $this->_json->saveSnomTemplate(Zend_Json::encode($testTemplate->toArray()));
        
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
        
        $returned = $this->_json->getSnomPhoneSettings($test['phone_id']);
        
        $returned['web_language'] = 'Deutsch';
        
        $updated = $this->_json->saveSnomPhoneSettings(Zend_Json::encode($returned));
        $this->assertEquals($returned['web_language'], $updated['web_language']);
        $this->assertNotNull($updated['phone_id']);
        
        // delete everything
        $settingsPhone = $this->_json->getSnomPhone($test['phone_id']);
        
        $location_id = $settingsPhone['location_id']['value'];
        $template_id = $settingsPhone['template_id']['value'];
        
        $phoneTemplate = $this->_json->getSnomTemplate($template_id);
        
        $settings_id = $phoneTemplate['setting_id']['value'];  
        $software_id = $phoneTemplate['software_id']['value'];
        
        $this->_json->deleteSnomPhoneSettings(Zend_Json::encode(array($returned['phone_id'])));
        $this->_json->deleteSnomPhones(Zend_Json::encode(array($settingsPhone['id'])));
        $this->_json->deleteSnomTemplates(Zend_Json::encode(array($template_id)));
        $this->_json->deleteSnomLocations(Zend_Json::encode(array($location_id)));
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($settings_id)));
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($software_id)));
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
        
        $returnedPhone = $this->_json->saveSnomPhone(Zend_Json::encode($testPhone), Zend_Json::encode($lineData), Zend_Json::encode($rightsData));
        
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
        $returned = $this->_json->saveSnomSetting(Zend_Json::encode($test->toArray()));
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomSettings as well
        $returnedGet = $this->_json->getSnomSetting($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of snom settings
     *
     */
    public function testUpdateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_json->saveSnomSetting(Zend_Json::encode($test->toArray()));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveSnomSetting(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
        
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test search of snom settings
     *
     */
    public function testSearchSnomSetting()
    {
        // create
        $snomSetting = $this->_getSnomSetting();        
        $snomSettingData = $this->_json->saveSnomSetting(Zend_Json::encode($snomSetting->toArray()));
        
        // search & check
        $search = $this->_json->searchSnomSettings(Zend_Json::encode($this->_getSnomSettingFilter($snomSetting->name)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($snomSetting->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteSnomSettings($snomSettingData['id']);
        /***********************************************************************************/
    }
    
    /**
     * get snom settings data
     *
     * @return array
     */
    protected function _getSnomSetting()
    {
        return new Voipmanager_Model_Snom_Setting(array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
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
                'field' => 'name', 
                'operator' => 'contains', 
                'value' => $_name
            ),
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
        
        $returned = $this->_json->saveSnomSoftware(Zend_Json::encode($test->toArray()));
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['description'], $returned['description']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomSoftware as well
        $returnedGet = $this->_json->getSnomSoftware($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['description'], $returnedGet['description']);
        
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test update of snom software
     *
     */
    public function testUpdateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $returned = $this->_json->saveSnomSoftware(Zend_Json::encode($test->toArray()));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        
        $updated = $this->_json->saveSnomSoftware(Zend_Json::encode($returned));
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['description'], $updated['description']);
        $this->assertNotNull($updated['id']);
                
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($returned['id'])));
    }
    
    /**
     * test search of snom software
     *
     */
    public function testSearchSnomSoftware()
    {
        // create
        $snomSoftware = $this->_getSnomSoftware();        
        $snomSoftwareData = $this->_json->saveSnomSoftware(Zend_Json::encode($snomSoftware->toArray()));
        
        // search & check
        $search = $this->_json->searchSnomSoftwares(Zend_Json::encode($this->_getSnomSoftwareFilter($snomSoftware->name)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($snomSoftware->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteSnomSoftwares($snomSoftwareData['id']);
        /***********************************************************************************/
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
                'field' => 'name', 
                'operator' => 'contains', 
                'value' => $_name
            ),
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
            'name'  => Tinebase_Record_Abstract::generateUID(),
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
        $returned = $this->_json->saveSnomTemplate(Zend_Json::encode($test->toArray()));
        
        $this->assertEquals($test['name'], $returned['name']);
        $this->assertEquals($test['setting_id'], $returned['setting_id']['value']);
        $this->assertEquals($test['software_id'], $returned['software_id']['value']);
        $this->assertNotNull($returned['id']);
        
        // test getSnomTemplate as well
        $returnedGet = $this->_json->getSnomTemplate($returned['id']);
        
        $this->assertEquals($test['name'], $returnedGet['name']);
        $this->assertEquals($test['setting_id'], $returnedGet['setting_id']['value']);
        $this->assertEquals($test['software_id'], $returnedGet['software_id']['value']);
        
        $this->_json->deleteSnomTemplates(Zend_Json::encode(array($returned['id'])));
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($returned['software_id']['value'])));
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($returned['setting_id']['value'])));
    }
    
    /**
     * test update of snom template
     *
     */
    public function testUpdateSnomTemplate()
    {   
        $test = $this->_getSnomTemplate();
        $returned = $this->_json->saveSnomTemplate(Zend_Json::encode($test->toArray()));
        $returned['description'] = Tinebase_Record_Abstract::generateUID();
        $returned['software_id'] = $returned['software_id']['value'];
        $returned['setting_id'] = $returned['setting_id']['value'];
        
        $updated = $this->_json->saveSnomTemplate(Zend_Json::encode($returned));
        
        $this->assertEquals($returned['name'], $updated['name']);
        $this->assertEquals($returned['setting_id'], $updated['setting_id']['value']);
        $this->assertEquals($returned['software_id'], $updated['software_id']['value']);
        $this->assertNotNull($updated['id']);
        
        $this->_json->deleteSnomTemplates(Zend_Json::encode(array($returned['id'])));
        $this->_json->deleteSnomSoftwares(Zend_Json::encode(array($updated['software_id']['value'])));
        $this->_json->deleteSnomSettings(Zend_Json::encode(array($updated['setting_id']['value'])));
    }
    
    /**
     * test search of snom template
     *
     */
    public function testSearchSnomTemplate()
    {
        // create
        $snomTemplate = $this->_getSnomTemplate();        
        $snomTemplateData = $this->_json->saveSnomTemplate(Zend_Json::encode($snomTemplate->toArray()));
        
        // search & check
        $search = $this->_json->searchSnomTemplates(Zend_Json::encode($this->_getSnomTemplateFilter($snomTemplate->name)), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($snomTemplate->name, $search['results'][0]['name']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_json->deleteSnomTemplates($snomTemplateData['id']);
        $this->_json->deleteSnomSoftwares($snomTemplateData['software_id']['value']);
        $this->_json->deleteSnomSettings($snomTemplateData['setting_id']['value']);
        /***********************************************************************************/
    }
    
    /**
     * get snom phone template
     *
     * @return array
     */
    protected function _getSnomTemplate()
    {
        $testSoftware = $this->_getSnomSoftware();
        $returnedSoftware = $this->_json->saveSnomSoftware(Zend_Json::encode($testSoftware->toArray()));
        
        $testSetting = $this->_getSnomSetting();
        $returnedSetting = $this->_json->saveSnomSetting(Zend_Json::encode($testSetting->toArray()));
        
        return new Voipmanager_Model_Snom_Template(array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'setting_id' => $returnedSetting['id'],
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
                'field' => 'name', 
                'operator' => 'contains', 
                'value' => $_name
            ),
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
            'sort' => 'id',
            'dir' => 'ASC'
        );
    }

}