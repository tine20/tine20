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
 * @todo        split into seperate controller tests?
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
     * @todo    use it?
     * @deprecated 
     */
    protected $_objects = array();

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
        $this->_backends['Asterisk_Context'] = Voipmanager_Controller_Asterisk_Context::getInstance();
        $this->_backends['Asterisk_Meetme'] = Voipmanager_Controller_Asterisk_Meetme::getInstance();
        $this->_backends['Asterisk_SipPeer'] = Voipmanager_Controller_Asterisk_SipPeer::getInstance();
        $this->_backends['Asterisk_Voicemail'] = Voipmanager_Controller_Asterisk_Voicemail::getInstance();
        $this->_backends['Snom_Line'] = Voipmanager_Controller_Snom_Line::getInstance();
        $this->_backends['Snom_Location'] = Voipmanager_Controller_Snom_Location::getInstance();
        $this->_backends['Snom_Phone'] = Voipmanager_Controller_Snom_Phone::getInstance();
        $this->_backends['Snom_Setting'] = Voipmanager_Controller_Snom_Setting::getInstance();
        $this->_backends['Snom_Software'] = Voipmanager_Controller_Snom_Software::getInstance();
        $this->_backends['Snom_Template'] = Voipmanager_Controller_Snom_Template::getInstance();
        $this->_backends['MyPhone'] = Voipmanager_Controller_MyPhone::getInstance();
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
        
        $filter = new Voipmanager_Model_AsteriskContextFilter(array(
            'query' => $test->name
        ));        
        $returned = $this->_backends['Asterisk_Context']->search($filter);
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
        
        $returned = $this->_backends['Asterisk_Meetme']->create($test);
        $this->assertEquals($test->confno, $returned->confno);
        $this->assertEquals($test->adminpin, $returned->adminpin);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_Meetme']->delete($returned->getId()); 
    }
    
    /**
     * test update of asterisk meetme room
     *
     */
    public function testUpdateAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        
        $test = $this->_backends['Asterisk_Meetme']->create($test);
        $test->adminpin = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Asterisk_Meetme']->update($test);
        $this->assertEquals($test->confno, $returned->confno);
        $this->assertEquals($test->adminpin, $returned->adminpin);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_Meetme']->delete($returned->getId()); 
    }
    
    /**
     * test search of asterisk meetme room
     *
     */
    public function testSearchAsteriskMeetme()
    {
        $test = $this->_getAsteriskMeetme();
        $test = $this->_backends['Asterisk_Meetme']->create($test);
        
        $filter = new Voipmanager_Model_AsteriskMeetmeFilter(array(
            'query' => $test->confno
        ));        
        $returned = $this->_backends['Asterisk_Meetme']->search($filter);
        $this->assertEquals(1, count($returned));
        
        $this->_backends['Asterisk_Meetme']->delete($returned->getId()); 
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
        
        $returned = $this->_backends['Asterisk_SipPeer']->create($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->qualify, $returned->qualify);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_SipPeer']->delete($returned->getId()); 
    }
    
    /**
     * test get of Aterisk sip peer
     *
     */
    public function testGetAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $test = $this->_backends['Asterisk_SipPeer']->create($test);
        $returned = $this->_backends['Asterisk_SipPeer']->get($test);

        $this->assertType('Voipmanager_Model_AsteriskSipPeer', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->callerid, $returned->callerid);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_SipPeer']->delete($returned->getId()); 
    }
    
    /**
     * test update of asterisk sip peer
     *
     */
    public function testUpdateAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $test = $this->_backends['Asterisk_SipPeer']->create($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Asterisk_SipPeer']->update($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->qualify, $returned->qualify);
        $this->assertNotNull($returned->id);
        
       $this->_backends['Asterisk_SipPeer']->delete($returned->getId()); 
    }
    
    /**
     * test search of asterisk sip peer
     *
     */
    public function testSearchAsteriskSipPeer()
    {
        $test = $this->_getAsteriskSipPeer();
        
        $test = $this->_backends['Asterisk_SipPeer']->create($test);
        
        $filter = new Voipmanager_Model_AsteriskSipPeerFilter(array(
            'name' => $test->name
        ));
        $returned = $this->_backends['Asterisk_SipPeer']->search($filter);
        $this->assertEquals(1, count($returned));
        
        $filter = new Voipmanager_Model_AsteriskSipPeerFilter(array(
            'query' => $test->name
        ));
        $returned = $this->_backends['Asterisk_SipPeer']->search($filter);
        $this->assertEquals(1, count($returned));
        
        $this->_backends['Asterisk_SipPeer']->delete($returned->getId()); 
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
        
        $returned = $this->_backends['Asterisk_Voicemail']->create($test);
        $this->assertEquals($test->mailbox, $returned->mailbox);
        $this->assertEquals($test->fullname, $returned->fullname);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_Voicemail']->delete($returned->getId()); 
    }
    
    /**
     * test update of asterisk sip peer
     *
     */
    public function testUpdateAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $test = $this->_backends['Asterisk_Voicemail']->create($test);
        $test->fullname = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Asterisk_Voicemail']->update($test);
        $this->assertEquals($test->mailbox, $returned->mailbox);
        $this->assertEquals($test->fullname, $returned->fullname);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Asterisk_Voicemail']->delete($returned->getId()); 
    }
    
    /**
     * test search of asterisk sip peer
     *
     */
    public function testSearchAsteriskVoicemail()
    {
        $test = $this->_getAsteriskVoicemail();
        
        $test = $this->_backends['Asterisk_Voicemail']->create($test);
        
        $filter = new Voipmanager_Model_AsteriskVoicemailFilter(array(
            'query' => $test->mailbox
        ));
        $returned = $this->_backends['Asterisk_Voicemail']->search($filter);
        $this->assertEquals(1, count($returned));
                
        $this->_backends['Asterisk_Voicemail']->delete($returned->getId()); 
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
        
        $returned = $this->_backends['Snom_Software']->create($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->softwareimage_snom320, $returned->softwareimage_snom320);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Software']->delete($returned->getId()); 
    }
    
    /**
     * test get of Snom software
     *
     */
    public function testGetSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $test = $this->_backends['Snom_Software']->create($test);
        $returned = $this->_backends['Snom_Software']->get($test);
        
        $this->assertType('Voipmanager_Model_SnomSoftware', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Software']->delete($returned->getId()); 
    }
    
    /**
     * test update of Snom software
     *
     */
    public function testUpdateSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $test = $this->_backends['Snom_Software']->create($test);
        $returned = $this->_backends['Snom_Software']->update($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->softwareimage_snom320, $returned->softwareimage_snom320);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Software']->delete($returned->getId()); 
    }
    
    /**
     * test search of Snom software
     *
     */
    public function testSearchSnomSoftware()
    {
        $test = $this->_getSnomSoftware();
        
        $test = $this->_backends['Snom_Software']->create($test);
        
        $filter = new Voipmanager_Model_SnomSoftwareFilter(array(
            'query' => $test->name
        ));
        $returned = $this->_backends['Snom_Software']->search($filter);
        $this->assertEquals(1, count($returned));
                
        $this->_backends['Snom_Software']->delete($returned->getId()); 
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
        
        $returned = $this->_backends['Snom_Setting']->create($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->display_method, $returned->display_method);
        $this->assertEquals($test->display_method_writable, $returned->display_method_writable);
        $this->assertEquals($test->mwi_notification, $returned->mwi_notification);
        $this->assertEquals($test->mwi_notification_writable, $returned->mwi_notification_writable);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Setting']->delete($returned->getId()); 
    }
    
    /**
     * test update of Snom setting
     *
     */
    public function testUpdateSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $test = $this->_backends['Snom_Setting']->create($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Snom_Setting']->update($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertEquals($test->display_method, $returned->display_method);
        $this->assertEquals($test->display_method_writable, $returned->display_method_writable);
        $this->assertEquals($test->mwi_notification, $returned->mwi_notification);
        $this->assertEquals($test->mwi_notification_writable, $returned->mwi_notification_writable);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Setting']->delete($returned->getId()); 
    }
    
    /**
     * test search of Snom setting
     *
     */
    public function testSearchSnomSetting()
    {
        $test = $this->_getSnomSetting();
        
        $test = $this->_backends['Snom_Setting']->create($test);
        
        $filter = new Voipmanager_Model_SnomSettingFilter(array(
            'query' => $test->name
        ));
        $returned = $this->_backends['Snom_Setting']->search($filter);
        $this->assertEquals(1, count($returned));
                
        $this->_backends['Snom_Setting']->delete($returned->getId()); 
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
        
        $returned = $this->_backends['Snom_Location']->create($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Location']->delete($returned->getId());
    }
    
    /**
     * test get of Snom location
     *
     */
    public function testGetSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $test = $this->_backends['Snom_Location']->create($test);
        $returned = $this->_backends['Snom_Location']->get($test);
        
        $this->assertType('Voipmanager_Model_SnomLocation', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Location']->delete($returned->getId());
    }
    
    /**
     * test update of Snom location
     *
     */
    public function testUpdateSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $test = $this->_backends['Snom_Location']->create($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Snom_Location']->update($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Location']->delete($returned->getId());
    }
    
    /**
     * test search of Snom setting
     *
     */
    public function testSearchSnomLocation()
    {
        $test = $this->_getSnomLocation();
        
        $test = $this->_backends['Snom_Location']->create($test);
        
        $filter = new Voipmanager_Model_SnomLocationFilter(array(
            'name' => $test->name
        ));
        
        $returned = $this->_backends['Snom_Location']->search($filter);
        $this->assertEquals(1, count($returned));
                
        $this->_backends['Snom_Location']->delete($returned->getId());
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
        $software = $this->_backends['Snom_Software']->create($this->_getSnomSoftware());
        $settings = $this->_backends['Snom_Setting']->create($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
        
        $returned = $this->_backends['Snom_Template']->create($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Template']->delete($returned->getId());
        $this->_backends['Snom_Setting']->delete($settings->getId());
        $this->_backends['Snom_Software']->delete($software->getId()); 
    }
    
    /**
     * test get of Snom template
     *
     */
    public function testGetSnomTemplate()
    {
        $software = $this->_backends['Snom_Software']->create($this->_getSnomSoftware());
        $settings = $this->_backends['Snom_Setting']->create($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
        
        $test = $this->_backends['Snom_Template']->create($test);
        $returned = $this->_backends['Snom_Template']->get($test);
        
        $this->assertType('Voipmanager_Model_SnomTemplate', $returned);
        $this->assertEquals($test->id, $returned->id);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Template']->delete($returned->getId());
        $this->_backends['Snom_Setting']->delete($settings->getId());
        $this->_backends['Snom_Software']->delete($software->getId()); 
    }
    
    /**
     * test update of Snom setting
     *
     */
    public function testUpdateSnomTemplate()
    {
        $software = $this->_backends['Snom_Software']->create($this->_getSnomSoftware());
        $settings = $this->_backends['Snom_Setting']->create($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
                
        $test = $this->_backends['Snom_Template']->create($test);
        $test->name = Tinebase_Record_Abstract::generateUID();
        
        $returned = $this->_backends['Snom_Template']->update($test);
        $this->assertEquals($test->name, $returned->name);
        $this->assertEquals($test->description, $returned->description);
        $this->assertNotNull($returned->id);
        
        $this->_backends['Snom_Template']->delete($returned->getId());
        $this->_backends['Snom_Setting']->delete($settings->getId());
        $this->_backends['Snom_Software']->delete($software->getId()); 
    }
    
    /**
     * test search of Snom setting
     *
     */
    public function testSearchSnomTemplate()
    {
        $software = $this->_backends['Snom_Software']->create($this->_getSnomSoftware());
        $settings = $this->_backends['Snom_Setting']->create($this->_getSnomSetting());
        $test = $this->_getSnomTemplate($software, $settings);
                
        $test = $this->_backends['Snom_Template']->create($test);
        
        $filter = new Voipmanager_Model_SnomTemplateFilter(array(
            'query' => $test->name
        ));
        $returned = $this->_backends['Snom_Template']->search($filter);
        $this->assertEquals(1, count($returned));
                
        $this->_backends['Snom_Template']->delete($returned->getId());
        $this->_backends['Snom_Setting']->delete($settings->getId());
        $this->_backends['Snom_Software']->delete($software->getId()); 
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
