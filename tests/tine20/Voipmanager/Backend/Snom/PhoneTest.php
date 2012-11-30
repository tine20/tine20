<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Crm
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        add more tests
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Voipmanager_Backend_Snom_PhoneTest::main');
}

/**
 * Test class for Voipmanager_Backend_Snom_PhoneTest
 */
class Voipmanager_Backend_Snom_PhoneTest extends PHPUnit_Framework_TestCase
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
     * @var Voipmanager_Backend_Snom_Phone
     */
    protected $_backend;

    /**
     * Runs the test methods of this class.
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Voipmanager Snom Phone Backend Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture.
     * 
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        
        // we need that because the voip db tables can have a different prefix
        Tinebase_Core::set('voipdbTablePrefix', SQL_TABLE_PREFIX);
        
        $this->_backend = new Voipmanager_Backend_Snom_Phone();
        
        $this->_objects['location'] = new Voipmanager_Model_Snom_Location(array(
            'id'        => Tinebase_Record_Abstract::generateUID(),
            'name'      => 'phpunit test location',
            'registrar' => 'registrar'
        ));

        $this->_objects['software'] = new Voipmanager_Model_Snom_Software(array(
            'id' => Tinebase_Record_Abstract::generateUID()
        ));
        
        $this->_objects['setting'] = new Voipmanager_Model_Snom_Setting(array(
            'id' => Tinebase_Record_Abstract::generateUID()
        ));
        
        $this->_objects['template'] = new Voipmanager_Model_Snom_Template(array(
            'id'          => Tinebase_Record_Abstract::generateUID(),
            'name'        => 'phpunit test location',
            'software_id' => $this->_objects['software']->getId(),
            'setting_id'  => $this->_objects['setting']->getId()
        ));
        
        $this->_objects['phone'] = new Voipmanager_Model_Snom_Phone(array(
            'id'             => Tinebase_Record_Abstract::generateUID(),
            'macaddress'     => "1234567890cd",
            'location_id'    => $this->_objects['location']->getId(),
            'template_id'    => $this->_objects['template']->getId(),
            'current_model'  => 'snom320',
            'redirect_event' => 'none'
        ));
        
        $this->_objects['phoneOwner'] = array(
            'account_id'   => Zend_Registry::get('currentAccount')->getId(),
            'account_type' => 'user'
        );
        
        $rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight', array(
            $this->_objects['phoneOwner']
        ));
        
        $this->_objects['phone']->rights = $rights;
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     */
    protected function tearDown()
    {
        Tinebase_TransactionManager::getInstance()->rollBack();
    }
    
    /**
     * test set phone rights
     * 
     * @todo move creation and removal of phone, location, template, ... to seperate tests
     */
    public function testSetPhoneRights()
    {
        // create phone, location, template
        $snomLocationBackend         = new Voipmanager_Backend_Snom_Location();
        $snomTemplateBackend         = new Voipmanager_Backend_Snom_Template();
        $snomSoftwareBackend         = new Voipmanager_Backend_Snom_Software();
        
        $snomSoftwareBackend->create($this->_objects['software']);
        $snomLocationBackend->create($this->_objects['location']);
        $snomTemplateBackend->create($this->_objects['template']);
        $this->_backend->create($this->_objects['phone']);
        
        // add phone rights
        $this->_backend->setPhoneRights($this->_objects['phone']);
        
        $rights = $this->_backend->getPhoneRights($this->_objects['phone']->getId());
        $testRight = $rights[0];
        
        $this->assertEquals(1, $testRight->read_right);
        $this->assertEquals(Zend_Registry::get('currentAccount')->getId(), $testRight->account_id);
        
        // delete rights
        $this->_objects['phone']->rights = new Tinebase_Record_RecordSet('Voipmanager_Model_Snom_PhoneRight');
        $this->_backend->setPhoneRights($this->_objects['phone']);

        $rights = $this->_backend->getPhoneRights($this->_objects['phone']->getId());
        
        $this->assertEquals(0, count($rights));
    }
}
