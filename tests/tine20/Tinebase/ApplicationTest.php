<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Group
 */
class Tinebase_ApplicationTest extends TestCase
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_ApplicationTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * try to get all application rights
     */
    public function testGetAllRights()
    {
        $application = Tinebase_Application::getInstance()->getApplicationByName('Admin');
        $rights = Tinebase_Application::getInstance()->getAllRights($application->getId());
        
        //print_r($rights);
        
        $this->assertGreaterThan(0, count($rights));

        $application = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $rights = Tinebase_Application::getInstance()->getAllRights($application->getId());
        
        //print_r($rights);
        
        $this->assertGreaterThan(0, count($rights));
    }
    
    /**
     * test create application
     * 
     * @return Tinebase_Model_Application
     */
    public function testAddApplication()
    {
        $application = Tinebase_Application::getInstance()->addApplication(new Tinebase_Model_Application(array(
            'name'      => Tinebase_Record_Abstract::generateUID(25),
            'status'    => Tinebase_Application::ENABLED,
            'order'     => 99,
            'version'   => 1
        )));
        
        $this->assertTrue($application instanceof Tinebase_Model_Application);
        
        return $application;
    }
    
    /**
     * test update application
     * 
     * @return Tinebase_Model_Application
     */
    public function testUpdateApplication()
    {
        $application = $this->testAddApplication();
        $application->name = Tinebase_Record_Abstract::generateUID(25);
        
        $testApplication = Tinebase_Application::getInstance()->updateApplication($application);
        
        $this->assertEquals($testApplication->name, $application->name);
        
        return $application;
    }
    
    /**
     * test update application
     * 
     * @return Tinebase_Model_Application
     */
    public function testDeleteApplication()
    {
        $application = $this->testAddApplication();
        
        Tinebase_Application::getInstance()->deleteApplication($application);
        
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        Tinebase_Application::getInstance()->getApplicationById($application);
    }
    
    /**
     * test get application by name and id
     * 
     * @return void
     */
    public function testGetApplicationById()
    {
        $application = $this->testAddApplication();
        
        $applicationByName = Tinebase_Application::getInstance()->getApplicationByName($application->name);
        $applicationById = Tinebase_Application::getInstance()->getApplicationById($application->getId());
        
        $this->assertTrue($applicationByName instanceof Tinebase_Model_Application);
        $this->assertTrue($applicationById instanceof Tinebase_Model_Application);
        $this->assertEquals($application, $applicationByName);
        $this->assertEquals($application, $applicationById);
    }
    
    /**
     * test get application by invalid id
     * 
     * @return void
     */
    public function testGetApplicationByInvalidId()
    {
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        Tinebase_Application::getInstance()->getApplicationById(Tinebase_Record_Abstract::generateUID());
    }
    
    /**
     * test get applications
     * 
     * @return void
     */
    public function testGetApplications()
    {
        $applications = Tinebase_Application::getInstance()->getApplications('Ad');
        
        $this->assertInstanceOf('Tinebase_Record_RecordSet', $applications);
        $this->assertGreaterThanOrEqual(2, count($applications));
    }
    
    /**
     * test get applications by state
     * 
     * @return void
     */
    public function testGetApplicationByState()
    {
        $application = $this->testAddApplication();
        
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        
        $this->assertInstanceOf('Tinebase_Record_RecordSet', $applications);
        $this->assertGreaterThanOrEqual(2, count($applications));
        $this->assertContains($application->id, $applications->id);
        
        
        Tinebase_Application::getInstance()->setApplicationState($application, Tinebase_Application::DISABLED);
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $this->assertNotContains($application->id, $applications->id);
        
        
        $application2 = $this->testAddApplication();
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $this->assertContains($application2->id, $applications->id);
        
        
        Tinebase_Application::getInstance()->deleteApplication($application2);
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $this->assertNotContains($application2->id, $applications->id);
        
        
        Tinebase_Application::getInstance()->setApplicationState($application, Tinebase_Application::ENABLED);
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $this->assertContains($application->id, $applications->id);
    }
    
    /**
     * test get applications by invalid state
     * 
     * @return void
     */
    public function testGetApplicationByInvalidState()
    {
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        
        $applications = Tinebase_Application::getInstance()->getApplicationsByState('foobar');
    }
    
    /**
     * test get application by invalid id
     * 
     * @return void
     */
    public function testGetApplicationByInvalidName()
    {
        $this->setExpectedException('Tinebase_Exception_NotFound');
        
        Tinebase_Application::getInstance()->getApplicationByName(Tinebase_Record_Abstract::generateUID());
    }
    
    /**
     * test get application total count
     * 
     * @return void
     */
    public function testGetTotalApplicationCount()
    {
        $result = Tinebase_Application::getInstance()->getTotalApplicationCount();
        
        $this->assertGreaterThanOrEqual(3, $result);
    }
    
    /**
     * Test length name for table name and column name (Oracle Database limitation) 
     * 
     * @see 0007452: use json encoded array for saving of policy settings
     */
    public function testSetupXML()
    {
        $_applications = Tinebase_Application::getInstance()->getApplications();
        foreach ($_applications->name as $applicationName) {
            // skip ActiveSync
            // @todo remove that when #7452 is resolved
            if ($applicationName === 'ActiveSync') {
                continue;
            }
            
            $xml = Setup_Controller::getInstance()->getSetupXml($applicationName);
            if (isset($xml->tables)) {
                foreach ($xml->tables[0] as $tableXML) {
                    $table = Setup_Backend_Schema_Table_Factory::factory('Xml', $tableXML);
                    $currentTable = $table->name;
                    $this->assertLessThan(29, strlen($currentTable), $applicationName." -> ". $table->name . "  (" . strlen($currentTable).")");
                    foreach ($table->fields as $field) {
                        $this->assertLessThan(31, strlen($field->name), $applicationName." -> ". $table->name . "  (" . strlen($field->name).")");
                    }
                }
            }
        }
    }
}
