<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Phone_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Phone_JsonTest extends PHPUnit_Framework_TestCase
{
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
        $this->_backend = new Voipmanager_Backend_Snom_Phone();
        
        $this->_objects['location'] = new Voipmanager_Model_SnomLocation(array(
            'id' => 20001,
            'name' => 'phpunit test location'
        ));

        $this->_objects['software'] = new Voipmanager_Model_SnomSoftware(array(
            'id' => 20003
        ));       
        
        $this->_objects['template'] = new Voipmanager_Model_SnomTemplate(array(
            'id' => 20002,
            'name' => 'phpunit test location',
            'model' => 'snom320',
            'software_id' => $this->_objects['software']->getId()
        ));
        
        $this->_objects['phone'] = new Voipmanager_Model_SnomPhone(array(
            'id' => 1001,
            'macaddress' => "1234567890cd",
            'location_id' => $this->_objects['location']->getId(),
            'template_id' => $this->_objects['template']->getId(),
            'current_model' => 'snom320',
            'redirect_event' => 'none'
        ));
        
        $this->_objects['phoneOwner'] = array(
            'account_id' => Zend_Registry::get('currentAccount')->getId(),
            'account_type' => 'user'
        );
        
        $rights = new Tinebase_Record_RecordSet('Voipmanager_Model_SnomPhoneRight', array(
            $this->_objects['phoneOwner']
        )); 
        
        $this->_objects['phone']->rights = $rights;
        
        $this->_objects['line'] = new Voipmanager_Model_SnomLine(array(
            'id'                => 1001,
            'snomphone_id'      => $this->_objects['phone']->getId(),
            'asteriskline_id'   => 1001,
            'linenumber'        => 1,
            'lineactive'        => 1
        ));

        $this->_objects['sippeer'] = new Voipmanager_Model_AsteriskSipPeer(array(
            'id'                => 1001,
        ));
        
        // create phone, location, template, rights
        $phoneBackend               = new Voipmanager_Backend_Snom_Phone();
        $snomLocationBackend        = new Voipmanager_Backend_Snom_Location();
        $snomTemplateBackend        = new Voipmanager_Backend_Snom_Template();     
        $snomSoftwareBackend        = new Voipmanager_Backend_Snom_Software(); 
        $snomLineBackend            = new Voipmanager_Backend_Snom_Line();
        $asteriskSipPeerBackend     = new Voipmanager_Backend_Asterisk_SipPeer();
        
        try {
            $snomSoftwareBackend->create($this->_objects['software']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }
        try {
            $snomLocationBackend->create($this->_objects['location']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }
        try {
            $snomTemplateBackend->create($this->_objects['template']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }
        try {
            $phoneBackend->create($this->_objects['phone']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }
        try {
            $phoneBackend->setPhoneRights($this->_objects['phone']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }
        try {
            $asteriskSipPeerBackend->create($this->_objects['sippeer']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }
        try {
            $snomLineBackend->create($this->_objects['line']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }
        
        /******************** call history *************************/

        $callHistoryBackend = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);    
        
        $this->_objects['call'] = new Phone_Model_Call(array(
            'id'                    => 'phpunitcallhistoryid',
            'line_id'               => $this->_objects['line']->getId(),
            'phone_id'              => $this->_objects['phone']->getId(),
            'call_id'               => 'phpunitcallid',
            'direction'             => Phone_Model_Call::TYPE_INCOMING,
            'source'                => '26',
            'destination'           => '0406437435',    
        ));
        
        $this->_objects['filter'] = array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'start',
            'dir' => 'ASC',
            'containerType' => 'all',
            'query' => ''     
        );        
        
        // create call
        try {
            $call = $callHistoryBackend->startCall($this->_objects['call']);
        } catch (Zend_Db_Statement_Exception $e) {
            // exists
        }        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {	
        // remove phone, location, template
        $phoneBackend               = new Voipmanager_Backend_Snom_Phone();
        $snomLocationBackend        = new Voipmanager_Backend_Snom_Location();
        $snomTemplateBackend        = new Voipmanager_Backend_Snom_Template();     
        $snomSoftwareBackend        = new Voipmanager_Backend_Snom_Software(); 
        $snomLineBackend            = new Voipmanager_Backend_Snom_Line();
        $asteriskSipPeerBackend     = new Voipmanager_Backend_Asterisk_SipPeer();
        $callHistoryBackend         = Phone_Backend_Factory::factory(Phone_Backend_Factory::CALLHISTORY);    
        
        $phoneBackend->delete($this->_objects['phone']->getId());
        $snomLocationBackend->delete($this->_objects['location']->getId());
        $snomTemplateBackend->delete($this->_objects['template']->getId());
        $snomSoftwareBackend->delete($this->_objects['software']->getId());
        $snomLineBackend->delete($this->_objects['line']->getId());
        $asteriskSipPeerBackend->delete($this->_objects['sippeer']->getId());
        $callHistoryBackend->delete($this->_objects['call']->getId());
    }
    
    /**
     * try to get user phones
     *
     */
    public function testGetUserPhones()
    {        
        // get phone json
        $json = new Phone_Json();
        $phones = $json->getUserPhones(Zend_Registry::get('currentAccount')->getId());

        //print_r($phones);
        $this->assertGreaterThan(0, count($phones['results']), 'more than 1 phone expected');        
        $this->assertEquals($this->_objects['phone']->macaddress, $phones['results'][0]['macaddress'], 'got wrong phone');
        $this->assertGreaterThan(0, count($phones['results'][0]['lines']), 'no lines attached');
        $this->assertEquals($this->_objects['sippeer']->getId(), $phones['results'][0]['lines'][0]['asteriskline_id'], 'got wrong line');
    }    
    
    /**
     * try to get all calls
     *
     * @todo add more than one calls to test filter settings
     */
    public function testGetCalls()    
    {
        // search calls
        $json = new Phone_Json();
        $result = $json->searchCalls(Zend_Json::encode($this->_objects['filter']));
        
        //print_r($result);

        $this->assertGreaterThan(0, $result['totalcount']);
        
        /*
        $this->assertEquals($this->_objects['initialLead']->description, $initialLead['description']);        
        $this->assertEquals($this->_objects['contact']->assistent, $initialLead['relations'][0]['related_record']['assistent']);
        */
    }    
}		
