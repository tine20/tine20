<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 * @todo        implement more tests!
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_ApplicationTest extends PHPUnit_Framework_TestCase
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
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        return;
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
     * test get application by name and id
     * 
     * @return void
     */
    public function testGetApplicationById()
    {
        $admin = Tinebase_Application::getInstance()->getApplicationByName('Admin');
        $adminById = Tinebase_Application::getInstance()->getApplicationById($admin->getId());
        
        $this->assertTrue($adminById instanceof Tinebase_Model_Application);
        $this->assertEquals($admin, $adminById);
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
