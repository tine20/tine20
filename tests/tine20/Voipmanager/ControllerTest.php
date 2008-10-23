<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 * 
 * @todo        replace single controller by controllers from Voipmanager_Controller_*
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Voipmanager_ControllerTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Voipmanager_ControllerTest extends PHPUnit_Framework_TestCase
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
     * @var Voipmanager_Controller
     * @deprecated 
     */
    protected $_backend;
    
    /**
     * the voipmanager controllers
     *
     * @var array
     */
    protected $_backends;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Voipmanager Controller Tests');
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
        $this->_backend = Voipmanager_Controller::getInstance();    

        $this->_backends['Asterisk_Context'] = Voipmanager_Controller_Asterisk_Context::getInstance();
        
        #$this->_objects['call'] = new Phone_Model_Call(array(
        #    'id'                    => 'phpunitcallid',
        #    'line_id'               => 'phpunitlineid',
        #    'phone_id'              => 'phpunitphoneid',
        #    'direction'             => Phone_Model_Call::TYPE_INCOMING,
        #    'source'                => '26',
        #    'destination'           => '0406437435',    
        #));
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
     * test getDBInstance
     * 
     */
    public function testGetDBInstance()
    {
        $db = $this->_backend->getDBInstance();
        
        $this->assertType('Zend_Db_Adapter_Abstract', $db);
    }
    
    /**
     * test creation of asterisk context
     *
     */
    public function testCreateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $returned = $this->_backends['Asterisk_Context']->create($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_Context']->delete($returned->getId()); 
    }
    
    /**
     * test update of asterisk context
     *
     */
    public function testUpdateAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $test = $this->_backends['Asterisk_Context']->create($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Asterisk_Context']->update($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_Context']->delete($returned->getId()); 
    }
    
    /**
     * test search of asterisk context
     *
     */
    public function testSearchAsteriskContext()
    {
        $test = $this->_getAsteriskContext();
        
        $test = $this->_backends['Asterisk_Context']->create($test);
        
        $returned = $this->_backends['Asterisk_Context']->search('id', 'ASC', $test->name);
        $this->assertEquals(1, count($returned));
        
        $this->_backends['Asterisk_Context']->delete($returned->getId()); 
    }
    
    protected function _getAsteriskContext()
    {
        return new Voipmanager_Model_AsteriskContext(array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'description' => Tinebase_Record_Abstract::generateUID()
        ));
    }

    /** MeetMe tests **/
    
    /**
     * test creation of asterisk meetme room
     *
     */
    public function testCreateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $returned = $this->_backend->createAsteriskMeetme($test);
        $this->assertEquals($test->confno, $returned->confno);
        $this->assertEquals($test->adminpin, $returned->adminpin);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteAsteriskMeetmes($returned->getId()); 
    }
    
    /**
     * test update of asterisk meetme room
     *
     */
    public function testUpdateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $test = $this->_backend->createAsteriskMeetme($test);
        $test->adminpin = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backend->updateAsteriskMeetme($test);
        $this->assertEquals($test->confno, $returned->confno);
        $this->assertEquals($test->adminpin, $returned->adminpin);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteAsteriskMeetmes($returned->getId()); 
    }
    
    /**
     * test search of asterisk meetme room
     *
     */
    public function testSearchAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $test = $this->_backend->createAsteriskMeetme($test);
        
        $returned = $this->_backend->getAsteriskMeetmes('id', 'ASC', $test->confno);
        $this->assertEquals(1, count($returned));
        
        $this->_backend->deleteAsteriskMeetmes($returned->getId()); 
    }
    
    protected function _getAsteriskMeetme()
    {
        return new Voipmanager_Model_AsteriskMeetme(array(
            'confno'  => Tinebase_Record_Abstract::generateUID(),
            'adminpin' => Tinebase_Record_Abstract::generateUID(),
            'pin' => Tinebase_Record_Abstract::generateUID()
        ));
    }    

    /** SipPeer tests **/
    
    /**
     * test creation of asterisk sip peer
     *
     */
    public function testCreateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $returned = $this->_backend->createAsteriskSipPeer($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->qualify, $returned->qualify);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteAsteriskSipPeers($returned->getId()); 
    }
    
    /**
     * test get of Aterisk sip peer
     *
     */
    public function testGetAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $test = $this->_backend->createAsteriskSipPeer($test);
        $returned = $this->_backend->getAsteriskSipPeer($test);

        $this->assertType('Voipmanager_Model_AsteriskSipPeer', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->callerid, $returned->callerid);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteAsteriskSipPeers($returned->getId()); 
    }
    
    /**
     * test update of asterisk sip peer
     *
     */
    public function testUpdateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $test = $this->_backend->createAsteriskSipPeer($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backend->updateAsteriskSipPeer($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->qualify, $returned->qualify);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteAsteriskSipPeers($returned->getId()); 
    }
    
    /**
     * test search of asterisk sip peer
     *
     */
    public function testSearchAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $test = $this->_backend->createAsteriskSipPeer($test);
        
        $filter = new Voipmanager_Model_AsteriskSipPeerFilter(array(
            'name' => $test->name
        ));
        $returned = $this->_backend->searchAsteriskSipPeers($filter);
        $this->assertEquals(1, count($returned));
        
        $filter = new Voipmanager_Model_AsteriskSipPeerFilter(array(
            'query' => $test->name
        ));
        $returned = $this->_backend->searchAsteriskSipPeers($filter);
        $this->assertEquals(1, count($returned));
        
        $this->_backend->deleteAsteriskSipPeers($returned->getId()); 
    }
    
    protected function _getAsteriskSipPeer()
    {
        return new Voipmanager_Model_AsteriskSipPeer(array(
            'name'  => Tinebase_Record_Abstract::generateUID(),
            'callerid' => Tinebase_Record_Abstract::generateUID(),
            'qualify' => 'yes'
        ));
    }    
    
    /** Voicemail tests **/
    
    /**
     * test creation of asterisk sip peer
     *
     */
    public function testCreateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $returned = $this->_backend->createAsteriskVoicemail($test);
        $this->assertEquals($test->mailbox, $returned->mailbox);
        $this->assertEquals($test->fullname, $returned->fullname);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteAsteriskVoicemails($returned->getId()); 
    }
    
    /**
     * test update of asterisk sip peer
     *
     */
    public function testUpdateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $test = $this->_backend->createAsteriskVoicemail($test);
        $test->fullname = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backend->updateAsteriskVoicemail($test);
        $this->assertEquals($test->mailbox, $returned->mailbox);
        $this->assertEquals($test->fullname, $returned->fullname);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteAsteriskVoicemails($returned->getId()); 
    }
    
    /**
     * test search of asterisk sip peer
     *
     */
    public function testSearchAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $test = $this->_backend->createAsteriskVoicemail($test);
        
        $returned = $this->_backend->getAsteriskVoicemails('id', 'ASC', $test->mailbox);
        $this->assertEquals(1, count($returned));
                
        $this->_backend->deleteAsteriskVoicemails($returned->getId()); 
    }
    
    /**
     * return random Voipmanager_Model_AsteriskVoicemail
     *
     * @return Voipmanager_Model_AsteriskVoicemail
     */
    protected function _getAsteriskVoicemail()
    {
        return new Voipmanager_Model_AsteriskVoicemail(array(
            'mailbox'  => substr(Tinebase_Record_Abstract::generateUID(), 0, 11),
            'fullname' => Tinebase_Record_Abstract::generateUID()
        ));
    }    

    /** Snom software tests **/
    
    /**
     * test creation of Snom software
     *
     */
    public function testCreateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $returned = $this->_backend->createSnomSoftware($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->softwareimage_snom320, $returned->softwareimage_snom320);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomSoftware($returned->getId()); 
    }
    
    /**
     * test get of Snom software
     *
     */
    public function testGetSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $test = $this->_backend->createSnomSoftware($test);
        $returned = $this->_backend->getSnomSoftware($test);
        
        $this->assertType('Voipmanager_Model_SnomSoftware', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomSoftware($returned->getId()); 
    }
    
    /**
     * test update of Snom software
     *
     */
    public function testUpdateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $test = $this->_backend->createSnomSoftware($test);
        $returned = $this->_backend->updateSnomSoftware($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->softwareimage_snom320, $returned->softwareimage_snom320);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomSoftware($returned->getId()); 
    }
    
    /**
     * test search of Snom software
     *
     */
    public function testSearchSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $test = $this->_backend->createSnomSoftware($test);
        
        $returned = $this->_backend->searchSnomSoftware('id', 'ASC', $test->name);
        $this->assertEquals(1, count($returned));
                
        $this->_backend->deleteSnomSoftware($returned->getId()); 
    }
    
    /**
     * return random Voipmanager_Model_SnomSoftware
     *
     * @return Voipmanager_Model_SnomSoftware
     */
    protected function _getSnomSoftware()
    {
        return new Voipmanager_Model_SnomSoftware(array(
            'name'                  => Tinebase_Record_Abstract::generateUID(),
            'description'           => Tinebase_Record_Abstract::generateUID(),
            'softwareimage_snom320' => Tinebase_Record_Abstract::generateUID()
        ));
    }    

    /** Snom settings tests **/
    
    /**
     * test creation of Snom setting
     *
     */
    public function testCreateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $returned = $this->_backend->createSnomSetting($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->display_method, $returned->display_method);
        $this->assertEquals($test->display_method_writable, $returned->display_method_writable);
        $this->assertEquals($test->mwi_notification, $returned->mwi_notification);
        $this->assertEquals($test->mwi_notification_writable, $returned->mwi_notification_writable);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomSettings($returned->getId()); 
    }
    
    /**
     * test update of Snom setting
     *
     */
    public function testUpdateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $test = $this->_backend->createSnomSetting($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backend->updateSnomSetting($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->display_method, $returned->display_method);
        $this->assertEquals($test->display_method_writable, $returned->display_method_writable);
        $this->assertEquals($test->mwi_notification, $returned->mwi_notification);
        $this->assertEquals($test->mwi_notification_writable, $returned->mwi_notification_writable);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomSettings($returned->getId()); 
    }
    
    /**
     * test search of Snom setting
     *
     */
    public function testSearchSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $test = $this->_backend->createSnomSetting($test);
        
        $returned = $this->_backend->getSnomSettings('id', 'ASC', $test->name);
        $this->assertEquals(1, count($returned));
                
        $this->_backend->deleteSnomSettings($returned->getId()); 
    }
    
    /**
     * return random Voipmanager_Model_SnomSetting
     *
     * @return Voipmanager_Model_SnomSetting
     */
    protected function _getSnomSetting()
    {
        return new Voipmanager_Model_SnomSetting(array(
            'name'                      => Tinebase_Record_Abstract::generateUID(),
            'description'               => Tinebase_Record_Abstract::generateUID(),
            'display_method'            => 'display_name_number',
            'display_method_writable'   => 1,
            'mwi_notification'          => 'silent',
            'mwi_notification_writable' => 0
        ));
    }    
    
    /** Snom location tests **/
    
    /**
     * test creation of Snom location
     *
     */
    public function testCreateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $returned = $this->_backend->createSnomLocation($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomLocations($returned->getId()); 
    }
    
    /**
     * test get of Snom location
     *
     */
    public function testGetSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $test = $this->_backend->createSnomLocation($test);
        $returned = $this->_backend->getSnomLocation($test);
        
        $this->assertType('Voipmanager_Model_SnomLocation', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomLocations($returned->getId()); 
    }
    
    /**
     * test update of Snom location
     *
     */
    public function testUpdateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $test = $this->_backend->createSnomLocation($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backend->updateSnomLocation($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomLocations($returned->getId()); 
    }
    
    /**
     * test search of Snom setting
     *
     */
    public function testSearchSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $test = $this->_backend->createSnomLocation($test);
        
        $returned = $this->_backend->getSnomLocations('id', 'ASC', $test->name);
        $this->assertEquals(1, count($returned));
                
        $this->_backend->deleteSnomLocations($returned->getId()); 
    }
    
    /**
     * return random Voipmanager_Model_SnomLocation
     *
     * @return Voipmanager_Model_SnomLocation
     */
    protected function _getSnomLocation()
    {
        return new Voipmanager_Model_SnomLocation(array(
            'name'                      => Tinebase_Record_Abstract::generateUID(),
            'description'               => Tinebase_Record_Abstract::generateUID(),
            'registrar'                 => Tinebase_Record_Abstract::generateUID()
        ));
    }    
    
    /** Snom settings tests **/
    
    /**
     * test creation of Snom setting
     *
     */
    public function testCreateSnomTemplate()
    {
        $software = $this->_backend->createSnomSoftware($this->_getSnomSoftware());
        $settings = $this->_backend->createSnomSetting($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
        
        $returned = $this->_backend->createSnomTemplate($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomTemplates($returned->getId());
        $this->_backend->deleteSnomSettings($settings->getId());
        $this->_backend->deleteSnomSoftware($software->getId()); 
    }
    
    /**
     * test get of Snom template
     *
     */
    public function testGetSnomTemplate()
    {
        $software = $this->_backend->createSnomSoftware($this->_getSnomSoftware());
        $settings = $this->_backend->createSnomSetting($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
        
        $test = $this->_backend->createSnomTemplate($test);
        $returned = $this->_backend->getSnomTemplate($test);
        
        $this->assertType('Voipmanager_Model_SnomTemplate', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomTemplates($returned->getId());
        $this->_backend->deleteSnomSettings($settings->getId());
        $this->_backend->deleteSnomSoftware($software->getId()); 
    }
    
    /**
     * test update of Snom setting
     *
     */
    public function testUpdateSnomTemplate()
    {
        $software = $this->_backend->createSnomSoftware($this->_getSnomSoftware());
        $settings = $this->_backend->createSnomSetting($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
                
        $test = $this->_backend->createSnomTemplate($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backend->updateSnomTemplate($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backend->deleteSnomTemplates($returned->getId());
        $this->_backend->deleteSnomSettings($settings->getId());
        $this->_backend->deleteSnomSoftware($software->getId()); 
    }
    
    /**
     * test search of Snom setting
     *
     */
    public function testSearchSnomTemplate()
    {
        $software = $this->_backend->createSnomSoftware($this->_getSnomSoftware());
        $settings = $this->_backend->createSnomSetting($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
                
        $test = $this->_backend->createSnomTemplate($test);
        
        $returned = $this->_backend->getSnomTemplates('id', 'ASC', $test->name);
        $this->assertEquals(1, count($returned));
                
        $this->_backend->deleteSnomTemplates($returned->getId());
        $this->_backend->deleteSnomSettings($settings->getId());
        $this->_backend->deleteSnomSoftware($software->getId()); 
    }
    
    /**
     * return random Voipmanager_Model_SnomTemplate
     *
     * @return Voipmanager_Model_SnomTemplate
     */
    protected function _getSnomTemplate(Voipmanager_Model_SnomSoftware $_software, Voipmanager_Model_SnomSetting $_settings)
    {
        return new Voipmanager_Model_SnomTemplate(array(
            'name'          => Tinebase_Record_Abstract::generateUID(),
            'description'   => Tinebase_Record_Abstract::generateUID(),
            'software_id'   => $_software->getId(),
            'setting_id'    => $_settings->getId()
        ));
    }    
    
}		
