<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Voipmanager
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Voipmanager_Backend_Snom_PhoneTest
 */
class Voipmanager_Backend_Snom_PhoneTest extends Voipmanager_Backend_Snom_AbstractTest
{
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
        parent::setUp();
        $this->_backend = new Voipmanager_Backend_Snom_Phone();
    }
    
    /**
     * test set phone rights
     * 
     * @todo move creation and removal of phone, location, template, ... to seperate tests
     */
    public function testSetPhoneRights()
    {
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
