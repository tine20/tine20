<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for DemoData creation in Tinebase_Frontend_Cli
 */
class Tinebase_Frontend_DemoDataCliTest extends PHPUnit_Framework_TestCase
{
    /**
     * Backend
     *
     * @var Tinebase_Frontend_Cli
     */
    protected $_cli;
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Tinebase Cli Tests');
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
        $this->_cli = new Tinebase_Frontend_Cli();
    }
    
    /**
     * tests demo data creation for all applications having demodata prepared
     */
    public function testCreateAllDemoData()
    {
        $adbController = Addressbook_Controller_Contact::getInstance();
        $normalContactsFilter  = new Addressbook_Model_ContactFilter(
            array(array('field' => 'type', 'operator' => 'not', 'value' => 'user'))
        );
        $existingContacts  = $adbController->search($normalContactsFilter);
        
        // skip admin as it will be called before all tests
        $opts = new Zend_Console_Getopt('abp:', array('skipAdmin'));
        ob_start();
        $this->_cli->createAllDemoData($opts);
        ob_end_clean();
        
        // test addressbook contacts / admin user contacts
        
        $accountContactsFilter = new Addressbook_Model_ContactFilter(
            array(array('field' => 'type', 'operator' => 'equals', 'value' => 'user'))
        );
        
        $normalContactsFilter  = new Addressbook_Model_ContactFilter(
            array(array('field' => 'type', 'operator' => 'equals', 'value' => 'contact'))
        );
        $normalContacts  = $adbController->search($normalContactsFilter);
        $accountContacts = $adbController->search($accountContactsFilter);
        
        // shared should be 700
        $this->assertEquals((700 + $existingContacts->count()), $normalContacts->count(),  'NormalContacts');
        
        // internal contacts/accounts
        $this->assertEquals(6,    $accountContacts->count(), 'AccountContacts');
        
        // test calendar entries
        $calController = Calendar_Controller_Event::getInstance();
        $allEventsFilter = new Calendar_Model_EventFilter(array());
        $allEvents = $calController->search($allEventsFilter);
        $this->assertEquals(49, $allEvents->count(), 'Events');
        
        // test crm leads
        $crmController = Crm_Controller_Lead::getInstance();
        $allLeadsFilter = new Crm_Model_LeadFilter(array());
        $allLeads = $crmController->search($allLeadsFilter);
        $this->assertEquals(1, $allLeads->count(), 'One shared lead should have been found');
        
        // test human resources
        $employeeController = HumanResources_Controller_Employee::getInstance();
        $allEmployeesFilter = new HumanResources_Model_EmployeeFilter(array());
        $allEmployees = $employeeController->search($allEmployeesFilter);
        $this->assertEquals(6, $allEmployees->count());
        
        // remove employees again
        $employeeController->delete($allEmployees->id);
    }
}
