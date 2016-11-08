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
    
    protected function tearDown()
    {
        parent::tearDown();
        
        // delete an non existing application to trigger file system cache cleanup
        Tinebase_Application::getInstance()->deleteApplication('1234567890123456789012345678901234567890');
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
     * Table name is less than 30 at least since Oracle 7
     * 
     * @see 0007452: use json encoded array for saving of policy settings
     */
    public function testSetupXML()
    {
        $applications = Tinebase_Application::getInstance()->getApplications();
        
        foreach ($applications->name as $applicationName) {
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
                    $this->assertLessThan(30, strlen($currentTable), $applicationName." -> ". $table->name . "  (" . strlen($currentTable).")");
                    foreach ($table->fields as $field) {
                        $this->assertLessThan(31, strlen($field->name), $applicationName." -> ". $table->name . "  (" . strlen($field->name).")");
                    }
                }
            }
        }
    }

    /**
     * Test
     */
    public function testGetModelsOfAllApplications()
    {
        $models = Tinebase_Application::getInstance()->getModelsOfAllApplications();
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $appNames = $applications->name;

        $expectedData = array(
            'ActiveSync' => array(
                'ActiveSync_Model_Policy',
                'ActiveSync_Model_Device',
            ),
            'Addressbook' => array(
                'Addressbook_Model_Salutation',
                'Addressbook_Model_List',
                'Addressbook_Model_ListRole',
                'Addressbook_Model_ListMemberRole',
                'Addressbook_Model_Contact',
            ),
            'Admin' => array(
                'Admin_Model_Config',
                'Admin_Model_SambaMachine',
            ),
            'Calendar' => array(
                'Calendar_Model_Resource',
                'Calendar_Model_iMIP',
                'Calendar_Model_Rrule',
                'Calendar_Model_AttendeeRole',
                'Calendar_Model_Event',
                'Calendar_Model_FreeBusy',
                'Calendar_Model_Exdate',
                'Calendar_Model_Attender',
                'Calendar_Model_AttendeeStatus',
            ),
            'CoreData' => array(
                'CoreData_Model_CoreData',
            ),
            'Courses' => array(
                'Courses_Model_Course',
            ),
            'Crm' => array(
                'Crm_Model_LeadType',
                'Crm_Model_LeadSource',
                'Crm_Model_LeadState',
                'Crm_Model_Lead',
            ),
            'Events' => array(
                'Events_Model_Status',
                'Events_Model_Event',
            ),
            'ExampleApplication' => array(
                'ExampleApplication_Model_ExampleRecord',
                'ExampleApplication_Model_Status',
            ),
            'Expressomail' => array(
                'Expressomail_Model_Account',
                'Expressomail_Model_Sieve_Vacation',
                'Expressomail_Model_Sieve_Rule',
                'Expressomail_Model_PreparedMessagePart',
                'Expressomail_Model_Message',
                'Expressomail_Model_Folder',
            ),
            'Felamimail' => array(
                'Felamimail_Model_Account',
                'Felamimail_Model_Sieve_Vacation',
                'Felamimail_Model_Sieve_Rule',
                'Felamimail_Model_PreparedMessagePart',
                'Felamimail_Model_Message',
                'Felamimail_Model_Folder',
            ),
            'Filemanager' => array(
                'Filemanager_Model_Node',
                'Filemanager_Model_DownloadLink',
            ),
            'HumanResources' => array(
                'HumanResources_Model_ExtraFreeTime',
                'HumanResources_Model_Account',
                'HumanResources_Model_Employee',
                'HumanResources_Model_FreeTimeType',
                'HumanResources_Model_ExtraFreeTimeType',
                'HumanResources_Model_FreeTimeStatus',
                'HumanResources_Model_Contract',
                'HumanResources_Model_CostCenter',
                'HumanResources_Model_FreeDay',
                'HumanResources_Model_WorkingTime',
                'HumanResources_Model_FreeTime',
            ),
            'Inventory' => array(
                'Inventory_Model_Status',
                'Inventory_Model_InventoryItem',
            ),
            'Phone' => array(
                'Phone_Model_Call',
                'Phone_Model_MyPhone',
            ),
            'Projects' => array(
                'Projects_Model_Project',
                'Projects_Model_AttendeeRole',
                'Projects_Model_Status',
            ),
            'Sales' => array(
                'Sales_Model_Number',
                'Sales_Model_Config',
                'Sales_Model_PurchaseInvoice',
                'Sales_Model_Supplier',
                'Sales_Model_PaymentMethod',
                'Sales_Model_OrderConfirmation',
                'Sales_Model_Customer',
                'Sales_Model_Address',
                'Sales_Model_ProductCategory',
                'Sales_Model_InvoiceCleared',
                'Sales_Model_InvoiceType',
                'Sales_Model_Invoice',
                'Sales_Model_Contract',
                'Sales_Model_InvoicePosition',
                'Sales_Model_Division',
                'Sales_Model_ProductAggregate',
                'Sales_Model_Offer',
                'Sales_Model_CostCenter',
                'Sales_Model_Product',
            ),
            'SimpleFAQ' => array(
                'SimpleFAQ_Model_Faq',
                'SimpleFAQ_Model_Config',
            ),
            'Tasks' => array(
                'Tasks_Model_Task',
                'Tasks_Model_Priority',
                'Tasks_Model_Pagination',
                'Tasks_Model_Status',
            ),
            'Timetracker' => array(
                'Timetracker_Model_TimeaccountGrants',
                'Timetracker_Model_Timesheet',
                'Timetracker_Model_Timeaccount',
            ),
            'Tinebase' => array(
                'Tinebase_Model_AccessLog',
                'Tinebase_Model_ContainerContent',
                'Tinebase_Model_Application',
                'Tinebase_Model_Registration',
                'Tinebase_Model_Image',
                'Tinebase_Model_Tree_Node',
                'Tinebase_Model_Tree_FileObject',
                'Tinebase_Model_ModificationLog',
                'Tinebase_Model_Config',
                'Tinebase_Model_Group',
                'Tinebase_Model_State',
                'Tinebase_Model_CredentialCache',
                'Tinebase_Model_PersistentFilterGrant',
                'Tinebase_Model_AsyncJob',
                'Tinebase_Model_CustomField_Value',
                'Tinebase_Model_CustomField_Config',
                'Tinebase_Model_CustomField_Grant',
                'Tinebase_Model_Container',
                'Tinebase_Model_Tag',
                'Tinebase_Model_Relation',
                'Tinebase_Model_TagRight',
                'Tinebase_Model_NoteType',
                'Tinebase_Model_Alarm',
                'Tinebase_Model_FullTag',
                'Tinebase_Model_SAMGroup',
                'Tinebase_Model_EmailUser',
                'Tinebase_Model_Department',
                'Tinebase_Model_ImportException',
                'Tinebase_Model_User',
                'Tinebase_Model_Role',
                'Tinebase_Model_Note',
                'Tinebase_Model_RoleRight',
                'Tinebase_Model_Pagination',
                'Tinebase_Model_TempFile',
                'Tinebase_Model_ImportExportDefinition',
                'Tinebase_Model_OpenId_Association',
                'Tinebase_Model_OpenId_TrustedSite',
                'Tinebase_Model_FullUser',
                'Tinebase_Model_Import',
                'Tinebase_Model_UpdateMultipleException',
                'Tinebase_Model_SAMUser',
                'Tinebase_Model_Path',
                'Tinebase_Model_Preference',
                'Tinebase_Model_PersistentObserver',
                'Tinebase_Model_Grants',
            ),
        );

        // remove bogus apps
        $remove = array('Voipmanager', 'RequestTracker', 'Sipgate', 'Expressodriver', 'MailFiler');
        foreach($remove as $r)
        {
            if (($key = array_search($r, $appNames)) !== false) {
                unset($appNames[$key]);
            }
        }

        // check all expected models are there
        foreach($expectedData as $appName => $expectedModels) {
            if (array_search($appName, $appNames) !== false) {
                foreach ($expectedModels as $expectedModel) {
                    $this->assertTrue(array_search($expectedModel, $models) !== false, 'did not find model: ' . $expectedModel);
                }
            }
        }

        // if there is at least one model, remove the app
        foreach($models as $model) {
            list($appName) = explode('_', $model);
            if (($key = array_search($appName, $appNames)) !== false) {
                unset($appNames[$key]);
            }
        }

        // check model dir -> app might have no models
        foreach ($appNames as $key => $appName) {
            $modelDir = __DIR__ . "../../tine20/$appName/Model/";
            if (! file_exists($modelDir)) {
                unset($appNames[$key]);
            }
        }
        
        // no apps should remain => we found models for each app, expect the bogus ones from above
        $this->assertEquals(0, count($appNames), 'applications found for which no models where found: '.print_r($appNames, true));

        // check if we found to much models
        $appNames = $applications->name;
        foreach($expectedData as $appName => $expectedModels) {
            if (array_search($appName, $appNames) !== false) {
                foreach ($expectedModels as $expectedModel) {
                    if (($key = array_search($expectedModel, $models)) !== false) {
                        unset($models[$key]);
                    }
                }
            }
        }

        // no models should remain
        $this->assertEquals(0, count($models), 'unexpected models found: '.print_r($models, true));
    }
}
