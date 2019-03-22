<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Application
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
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

        // make the record dirty
        $application->version = 2;
        $application->version = 1;

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
        
        
        Tinebase_Application::getInstance()->setApplicationStatus($application, Tinebase_Application::DISABLED);
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $this->assertNotContains($application->id, $applications->id);
        
        
        $application2 = $this->testAddApplication();
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $this->assertContains($application2->id, $applications->id);
        
        
        Tinebase_Application::getInstance()->deleteApplication($application2);
        $applications = Tinebase_Application::getInstance()->getApplicationsByState(Tinebase_Application::ENABLED);
        $this->assertNotContains($application2->id, $applications->id);
        
        
        Tinebase_Application::getInstance()->setApplicationStatus($application, Tinebase_Application::ENABLED);
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
        
        Tinebase_Application::getInstance()->getApplicationsByState('foobar');
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
                ActiveSync_Model_Policy::class,
                ActiveSync_Model_Device::class,
            ),
            'Addressbook' => array(
                Addressbook_Model_Salutation::class,
                Addressbook_Model_List::class,
                Addressbook_Model_ListRole::class,
                Addressbook_Model_ListMemberRole::class,
                Addressbook_Model_Contact::class,
                Addressbook_Model_Industry::class,
            ),
            'Admin' => array(
                Admin_Model_Config::class,
                Admin_Model_SambaMachine::class,
            ),
            'Calendar' => array(
                Calendar_Model_Resource::class,
                Calendar_Model_ResourceType::class,
                Calendar_Model_ResourceGrants::class,
                Calendar_Model_iMIP::class,
                Calendar_Model_Rrule::class,
                Calendar_Model_AttendeeRole::class,
                Calendar_Model_Event::class,
                Calendar_Model_EventPersonalGrants::class,
                Calendar_Model_ExternalInvitationGrants::class,
                Calendar_Model_Poll::class,
                Calendar_Model_FreeBusy::class,
                Calendar_Model_Exdate::class,
                Calendar_Model_Attender::class,
                Calendar_Model_AttendeeStatus::class,
            ),
            'CoreData' => array(
                CoreData_Model_CoreData::class,
            ),
            'Courses' => array(
                Courses_Model_Course::class,
            ),
            'Crm' => array(
                Crm_Model_LeadType::class,
                Crm_Model_LeadSource::class,
                Crm_Model_LeadState::class,
                Crm_Model_Lead::class,
            ),
            'ExampleApplication' => array(
                ExampleApplication_Model_ExampleRecord::class,
                ExampleApplication_Model_Status::class,
            ),
            'Felamimail' => array(
                Felamimail_Model_Account::class,
                Felamimail_Model_Sieve_Vacation::class,
                Felamimail_Model_Sieve_Rule::class,
                Felamimail_Model_Sieve_ScriptPart::class,
                Felamimail_Model_PreparedMessagePart::class,
                Felamimail_Model_Message::class,
                Felamimail_Model_MessageFileLocation::class,
                Felamimail_Model_MessageFileSuggestion::class,
                Felamimail_Model_Folder::class,
            ),
            'Filemanager' => array(
                Filemanager_Model_Node::class,
                Filemanager_Model_DownloadLink::class,
            ),
            'HumanResources' => array(
                HumanResources_Model_ExtraFreeTime::class,
                HumanResources_Model_Account::class,
                HumanResources_Model_Employee::class,
                HumanResources_Model_FreeTimeType::class,
                HumanResources_Model_ExtraFreeTimeType::class,
                HumanResources_Model_FreeTimeStatus::class,
                HumanResources_Model_Contract::class,
                HumanResources_Model_CostCenter::class,
                HumanResources_Model_FreeDay::class,
                HumanResources_Model_WorkingTime::class,
                HumanResources_Model_FreeTime::class,
                HumanResources_Model_DailyWTReport::class,
                HumanResources_Model_Break::class,
            ),
            'Inventory' => array(
                Inventory_Model_Status::class,
                Inventory_Model_InventoryItem::class,
            ),
            'Phone' => array(
                Phone_Model_Call::class,
                Phone_Model_MyPhone::class,
            ),
            'Projects' => array(
                Projects_Model_Project::class,
                Projects_Model_AttendeeRole::class,
                Projects_Model_Status::class,
            ),
            'Sales' => array(
                Sales_Model_Number::class,
                Sales_Model_Config::class,
                Sales_Model_PurchaseInvoice::class,
                Sales_Model_Supplier::class,
                Sales_Model_PaymentMethod::class,
                Sales_Model_OrderConfirmation::class,
                Sales_Model_Customer::class,
                Sales_Model_Address::class,
                Sales_Model_ProductCategory::class,
                Sales_Model_InvoiceCleared::class,
                Sales_Model_InvoiceType::class,
                Sales_Model_Invoice::class,
                Sales_Model_Contract::class,
                Sales_Model_InvoicePosition::class,
                Sales_Model_Division::class,
                Sales_Model_ProductAggregate::class,
                Sales_Model_Offer::class,
                Sales_Model_CostCenter::class,
                Sales_Model_Product::class,
            ),
            'SimpleFAQ' => array(
                SimpleFAQ_Model_Faq::class,
                SimpleFAQ_Model_Config::class,
            ),
            'Tasks' => array(
                Tasks_Model_Task::class,
                Tasks_Model_Priority::class,
                Tasks_Model_Pagination::class,
                Tasks_Model_Status::class,
            ),
            'Timetracker' => array(
                Timetracker_Model_TimeaccountGrants::class,
                Timetracker_Model_Timesheet::class,
                Timetracker_Model_Timeaccount::class,
                Timetracker_Model_TimeaccountFavorite::class,
            ),
            'Tinebase' => array(
                Tinebase_Model_AccessLog::class,
                Tinebase_Model_AreaLockConfig::class,
                Tinebase_Model_AreaLockState::class,
                Tinebase_Model_BLConfig::class,
                Tinebase_Model_ContainerContent::class,
                Tinebase_Model_Application::class,
                Tinebase_Model_Registration::class,
                Tinebase_Model_Image::class,
                Tinebase_Model_Tree_Node::class,
                Tinebase_Model_Tree_FileObject::class,
                Tinebase_Model_ModificationLog::class,
                Tinebase_Model_Config::class,
                Tinebase_Model_Group::class,
                Tinebase_Model_State::class,
                Tinebase_Model_CredentialCache::class,
                Tinebase_Model_PersistentFilterGrant::class,
                Tinebase_Model_AsyncJob::class,
                Tinebase_Model_CustomField_Value::class,
                Tinebase_Model_CustomField_Config::class,
                Tinebase_Model_CustomField_Grant::class,
                Tinebase_Model_Container::class,
                Tinebase_Model_Tag::class,
                Tinebase_Model_Relation::class,
                Tinebase_Model_TagRight::class,
                Tinebase_Model_NoteType::class,
                Tinebase_Model_Alarm::class,
                Tinebase_Model_FullTag::class,
                Tinebase_Model_SAMGroup::class,
                Tinebase_Model_EmailUser::class,
                Tinebase_Model_Department::class,
                Tinebase_Model_ImportException::class,
                Tinebase_Model_User::class,
                Tinebase_Model_UserPassword::class,
                Tinebase_Model_Role::class,
                Tinebase_Model_RoleRight::class,
                Tinebase_Model_RoleMember::class,
                Tinebase_Model_Note::class,
                Tinebase_Model_Pagination::class,
                Tinebase_Model_TempFile::class,
                Tinebase_Model_ImportExportDefinition::class,
                Tinebase_Model_OpenId_Association::class,
                Tinebase_Model_OpenId_TrustedSite::class,
                Tinebase_Model_FullUser::class,
                Tinebase_Model_Import::class,
                Tinebase_Model_UpdateMultipleException::class,
                Tinebase_Model_SAMUser::class,
                Tinebase_Model_Path::class,
                Tinebase_Model_Preference::class,
                Tinebase_Model_PersistentObserver::class,
                Tinebase_Model_Grants::class,
                Tinebase_Model_SchedulerTask::class,
                Tinebase_Model_FilterSyncToken::class,
            ),
            'Voipmanager' => array(
                Voipmanager_Model_Snom_Template::class,
                Voipmanager_Model_Snom_Software::class,
                Voipmanager_Model_Snom_PhoneRight::class,
                Voipmanager_Model_Snom_Line::class,
                Voipmanager_Model_Snom_Phone::class,
                Voipmanager_Model_Snom_PhoneSettings::class,
                Voipmanager_Model_Snom_Setting::class,
                Voipmanager_Model_Snom_Location::class,
                Voipmanager_Model_Asterisk_SipPeer::class,
                Voipmanager_Model_Asterisk_Voicemail::class,
                Voipmanager_Model_Asterisk_Meetme::class,
                Voipmanager_Model_Asterisk_Context::class,
            ),
        );

        // check all expected models are there
        foreach ($expectedData as $appName => $expectedModels) {
            if (array_search($appName, $appNames) !== false) {
                foreach ($expectedModels as $expectedModel) {
                    $this->assertTrue(array_search($expectedModel, $models) !== false, 'did not find model: ' . $expectedModel);
                }
            }
        }

        // if there is at least one model, remove the app
        foreach ($models as $model) {
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

        // remove custom apps
        foreach ($appNames as $key => $appName) {
            if (!isset($expectedData[$appName])) {
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

        // remove custom app models
        foreach ($models as $key => $modelName) {
            list($appName,) = explode('_', $modelName, 2);
            if (!isset($expectedData[$appName])) {
                unset($models[$key]);
            }
        }

        // no models should remain
        $this->assertEquals(0, count($models), 'unexpected models found: '.print_r($models, true));
    }

    public function testInstallApplicationWithId()
    {
        Setup_Core::set(Setup_Core::CHECKDB, true);
        Setup_Controller::destroyInstance();
        Setup_Controller::getInstance()->uninstallApplications(['ExampleApplication']);

        $this->_testNeedsTransaction();

        $appId = Tinebase_Record_Abstract::generateUID();
        Setup_Controller::getInstance()->installApplications([$appId => 'ExampleApplication'],
            [Setup_Controller::INSTALL_NO_IMPORT_EXPORT_DEFINITIONS => true]);

        $app = Tinebase_Application::getInstance()->getApplicationByName('ExampleApplication');

        static::assertEquals($appId, $app->getId());
    }
}
