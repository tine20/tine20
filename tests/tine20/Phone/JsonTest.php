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
        
        // create phone, location, template, rights
        $phoneBackend                = new Voipmanager_Backend_Snom_Phone();
        $snomLocationBackend         = new Voipmanager_Backend_Snom_Location();
        $snomTemplateBackend         = new Voipmanager_Backend_Snom_Template();     
        $snomSoftwareBackend         = new Voipmanager_Backend_Snom_Software(); 
        
        $snomSoftwareBackend->create($this->_objects['software']);
        $snomLocationBackend->create($this->_objects['location']);
        $snomTemplateBackend->create($this->_objects['template']);
        $phoneBackend->create($this->_objects['phone']);
        $phoneBackend->setPhoneRights($this->_objects['phone']);
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
        $phoneBackend                = new Voipmanager_Backend_Snom_Phone();
        $snomLocationBackend         = new Voipmanager_Backend_Snom_Location();
        $snomTemplateBackend         = new Voipmanager_Backend_Snom_Template();     
        $snomSoftwareBackend         = new Voipmanager_Backend_Snom_Software(); 
        
        $phoneBackend->delete($this->_objects['phone']->getId());
        $snomLocationBackend->delete($this->_objects['location']->getId());
        $snomTemplateBackend->delete($this->_objects['template']->getId());
        $snomSoftwareBackend->delete($this->_objects['software']->getId());
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
        $this->assertEquals(1, count($phones['results']), 'only 1 phone expected');        
        $this->assertEquals($this->_objects['phone']->macaddress, $phones['results'][0]['macaddress'], 'got wrong phone');
    }    
}		
