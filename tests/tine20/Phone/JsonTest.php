<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 * @todo        add test for saveMyPhone
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Phone_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Phone_Frontend_Json
     */
    protected $_json;
    
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
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Phone Json Tests');
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
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        $this->_json = new Phone_Frontend_Json();
        
        $this->_objects['location'] = new Voipmanager_Model_Snom_Location(array(
            'id'        => Tinebase_Record_Abstract::generateUID(),
            'name'      => 'phpunit test location',
            'registrar' => 'registrar'
        ));

        $this->_objects['software'] = new Voipmanager_Model_Snom_Software(array(
            'id' => Tinebase_Record_Abstract::generateUID()
        ));
        
        $this->_objects['setting'] = new Voipmanager_Model_Snom_Setting(array(
            'id'          => Tinebase_Record_Abstract::generateUID(),
            'name'        => 'test setting',
            'description' => 'test setting',
        ));

        $this->_objects['template'] = new Voipmanager_Model_Snom_Template(array(
            'id'          => Tinebase_Record_Abstract::generateUID(),
            'name'        => 'phpunit test location',
            'model'       => 'snom320',
            'software_id' => $this->_objects['software']->getId(),
            'setting_id'  => $this->_objects['setting']->getId()
        ));
        
        $this->_objects['phone'] = new Voipmanager_Model_Snom_Phone(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'macaddress'     => "1234567890cd",
            'description'    => 'user phone',
            'location_id'    => $this->_objects['location']->getId(),
            'template_id'    => $this->_objects['template']->getId(),
            'current_model'  => 'snom320',
            'redirect_event' => 'none'
        ));
        
        $this->_objects['phonesetting'] = new Voipmanager_Model_Snom_PhoneSettings(array(
            'phone_id'     => $this->_objects['phone']->getId(),
            'web_language' => 'English'
        ));
        
        $this->_objects['phoneOwner'] = array(
            'account_id' => Zend_Registry::get('currentAccount')->getId(),
            'account_type' => 'user'
        );
        
        $rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', array(
            $this->_objects['phoneOwner']
        ));
        
        $this->_objects['phone']->rights = $rights;
        
        $this->_objects['context'] = new Voipmanager_Model_Asterisk_Context(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'name'              => 'test context',
            'description'       => 'test context',
        ));
        
        $this->_objects['sippeer'] = new Voipmanager_Model_Asterisk_SipPeer(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'context_id'        => $this->_objects['context']->getId()
        ));
        
        $this->_objects['line'] = new Voipmanager_Model_Snom_Line(array(
            'id'                => Tinebase_Record_Abstract::generateUID(),
            'snomphone_id'      => $this->_objects['phone']->getId(),
            'asteriskline_id'   => $this->_objects['sippeer']->getId(),
            'linenumber'        => 1,
            'lineactive'        => 1
        ));
        
        // create phone, location, template, rights
        $phoneBackend               = new Voipmanager_Backend_Snom_Phone(array(
            'modelName' => 'Phone_Model_MyPhone'
        ));
        $snomLocationBackend        = new Voipmanager_Backend_Snom_Location();
        $snomSettingBackend         = new Voipmanager_Backend_Snom_Setting();
        $snomPhoneSettingBackend    = new Voipmanager_Backend_Snom_PhoneSettings();
        $snomTemplateBackend        = new Voipmanager_Backend_Snom_Template();
        $snomSoftwareBackend        = new Voipmanager_Backend_Snom_Software();
        $snomLineBackend            = new Voipmanager_Backend_Snom_Line();
        $asteriskSipPeerBackend     = new Voipmanager_Backend_Asterisk_SipPeer();
        $asteriskContextBackend     = new Voipmanager_Backend_Asterisk_Context();
        
        $snomSoftwareBackend->create($this->_objects['software']);
        $snomLocationBackend->create($this->_objects['location']);
        $snomSettingBackend->create($this->_objects['setting']);
        $snomTemplateBackend->create($this->_objects['template']);
        $phoneBackend->create($this->_objects['phone']);
        $phoneBackend->setPhoneRights($this->_objects['phone']);
        $asteriskContextBackend->create($this->_objects['context']);
        $asteriskSipPeerBackend->create($this->_objects['sippeer']);
        $snomLineBackend->create($this->_objects['line']);
        $snomPhoneSettingBackend->create($this->_objects['phonesetting']);
        
        /******************** call history *************************/

        $phoneController = Phone_Controller::getInstance();
        
        $this->_objects['call1'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid1',
            'line_id'               => $this->_objects['line']->getId(),
            'phone_id'              => $this->_objects['phone']->getId(),
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => '0406437435',
        ));

        $this->_objects['call2'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid2',
            'line_id'               => $this->_objects['line']->getId(),
            'phone_id'              => $this->_objects['phone']->getId(),
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => '050364354',
        ));
        
        $this->_objects['paging'] = array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'start',
            'dir' => 'ASC',
        );
        
        $this->_objects['filter1'] = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '')
        );

        $this->_objects['filter2'] = array(
            array('field' => 'query', 'operator' => 'contains', 'value' => '050')
        );

        $this->_objects['filter3'] = array(
            array('field' => 'phone_id', 'operator' => 'equals', 'value' => $this->_objects['phone']->getId())
        );
        
        // create calls
        $call = $phoneController->callStarted($this->_objects['call1']);
        $call = $phoneController->callStarted($this->_objects['call2']);
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
    
    /**
     * try to get all calls
     *
     */
    public function testGetCalls()
    {
        // search calls
        $result = $this->_json->searchCalls($this->_objects['filter1'], $this->_objects['paging']);
        $this->assertGreaterThan(1, $result['totalcount']);
        
        // search query -> '050'
        $result = $this->_json->searchCalls($this->_objects['filter2'], $this->_objects['paging']);
        $this->assertEquals(1, $result['totalcount'], 'query filter not working');
        
        $call2 = $result['results'][0];
        
        $this->assertEquals($this->_objects['call2']->destination, $call2['destination']);
        $this->assertEquals($this->_objects['call2']->getId(), $call2['id']);
        
        // search for phone_id
        $result = $this->_json->searchCalls($this->_objects['filter3'], $this->_objects['paging']);
        $this->assertGreaterThan(1, $result['totalcount'], 'phone_id filter not working');
    }

    /**
     * get and update user phone
     * 
     * @return void
     */
    public function testGetUpdateSnomPhone()
    {
        $userPhone = $this->_json->getMyPhone($this->_objects['phone']->getId());
        
        $this->assertEquals('user phone', $userPhone['description'], 'no description');
        $this->assertTrue(array_key_exists('web_language', $userPhone), 'missing web_language:' . print_r($userPhone, TRUE));
        $this->assertEquals('English', $userPhone['web_language'], 'wrong web_language');
        $this->assertGreaterThan(0, count($userPhone['lines']), 'no lines attached');

        // update phone
        $userPhone['web_language'] = 'Deutsch';
        $userPhone['lines'][0]['idletext'] = 'idle';
        $userPhone['lines'][0]['asteriskline_id']['cfd_time'] = 60;
        $userPhoneUpdated = $this->_json->saveMyPhone($userPhone);
        
        $this->assertEquals('Deutsch', $userPhoneUpdated['web_language'], 'no updated web_language');
        $this->assertEquals('idle', $userPhoneUpdated['lines'][0]['idletext'], 'no updated idletext');
        $this->assertEquals(60, $userPhoneUpdated['lines'][0]['asteriskline_id']['cfd_time'], 'no updated cfd time');
    }
    
    /**
     * try to get registry data
     *
     */
    public function testGetRegistryData()
    {
        // get phone json
        $data = $this->_json->getRegistryData();
        
        $this->assertGreaterThan(0, count($data['Phones']), 'more than 1 phone expected');
        $this->assertGreaterThan(0, count($data['Phones'][0]['lines']), 'no lines attached');
        $this->assertEquals('user phone', $data['Phones'][0]['description'], 'no description');
    }
    
    // we need some mocks for asterisk backends...
    public function _testDialNumber()
    {
        $number = '+494031703167';
        $phoneId = $this->_objects['phone']->getId();
        $lineId = $this->_objects['line']->getId();
        
        $status = $this->_json->dialNumber($number, $phoneId, $lineId);
        
        $this->assertEquals('array', gettype($status));
        $this->assertTrue(array_key_exists('success', $status));
        $this->assertTrue($status['success']);
    }
}
