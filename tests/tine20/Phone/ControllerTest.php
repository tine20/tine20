<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Group
 */
class Phone_ControllerTest extends TestCase
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
     * @var Phone_Controller
     */
    protected $_backend;
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
{
        parent::setUp();

        $this->_backend = Phone_Controller::getInstance();

        $this->_objects['call'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallid',
            'line_id'               => 'phpunitlineid',
            'phone_id'              => 'phpunitphoneid',
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => '0406437435',    
        ));
    }

    /**
     * test a whole call cycle - start, connect, disconnect
     * 
     */
    public function testWholeCall()
    {
        // start call
        $call = $this->_backend->callStarted($this->_objects['call']);
        
        self::assertEquals($this->_objects['call']->destination, $call->destination);
        self::assertTrue(Tinebase_DateTime::now()->sub($call->start)->getTimestamp() >= 0);
        
        // sleep for 2 secs (ringing...)
        sleep(2);

        // connect call
        $call = $this->_backend->getCall($this->_objects['call']->getId());

        $connectedCall = $this->_backend->callConnected($call);

        self::assertEquals($this->_objects['call']->destination, $connectedCall->destination);
        self::assertEquals(-1, $call->start->compare($call->connected));

        // sleep for 5 secs (talking...)
        sleep(5);

        // disconnect call
        $call = $this->_backend->getCall($this->_objects['call']->getId());
        $duration = $call->duration;

        $disconnectedCall = $this->_backend->callDisconnected($call);

        self::assertGreaterThan($duration, $disconnectedCall->duration);
        self::assertLessThan(10, $disconnectedCall->ringing, 'wrong ringing duration');
        self::assertLessThan(22, $disconnectedCall->duration, 'wrong duration');
        self::assertEquals(-1, $disconnectedCall->connected->compare($disconnectedCall->disconnected));
    }

    /**
     * testGetLastCall
     */
    public function testGetLastCall()
    {
        // TODO move this to a common place (dry - see \Phone_JsonTest::setUp)
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

        $snomLocationBackend        = new Voipmanager_Backend_Snom_Location();
        $snomSettingBackend         = new Voipmanager_Backend_Snom_Setting();
        $snomTemplateBackend        = new Voipmanager_Backend_Snom_Template();
        $snomSoftwareBackend        = new Voipmanager_Backend_Snom_Software();
        $phoneBackend               = new Voipmanager_Backend_Snom_Phone(array(
            'modelName' => 'Phone_Model_MyPhone'
        ));

        $snomSoftwareBackend->create($this->_objects['software']);
        $snomLocationBackend->create($this->_objects['location']);
        $snomSettingBackend->create($this->_objects['setting']);
        $snomTemplateBackend->create($this->_objects['template']);

        $phoneBackend->create($this->_objects['phone']);

        $call = $this->_objects['call'];
        $call->phone_id = $this->_objects['phone']->getId();
        $call = $this->_backend->callStarted($call);

        $lastCall = Phone_Controller::getInstance()->getLastCall($this->_objects['phone']->macaddress);

        self::assertTrue($lastCall !== null, 'last call not found');
        self::assertEquals($call->phpunitlineid, $lastCall->phpunitlineid, print_r($lastCall, true));
    }
}
