<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Tinebase_Frontend_Cli
 */
class Tinebase_Frontend_CliTest extends TestCase
{
    /**
     * Backend
     *
     * @var Tinebase_Frontend_Cli
     */
    protected $_cli;
    
    /**
     * test user
     * 
     * @var Tinebase_Model_FullUser
     */
    protected $_testUser;
    
    /**
     * user plugins, need to be reset after triggerAsyncEvents run
     * 
     * @var array
     */
    protected $_userPlugins = array();
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        parent::setUp();
        
        $this->_cli = new Tinebase_Frontend_Cli();
        $this->_testUser = Tinebase_Core::getUser();
        $this->_userPlugins = Tinebase_User::getInstance()->getPlugins();
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
        $currentUser = Tinebase_Core::getUser();
        if ($currentUser->accountLoginName !== $this->_testUser->accountLoginName) {
            Tinebase_Core::set(Tinebase_Core::USER, $this->_testUser);
        }
        
        parent::tearDown();
    }
    
    /**
     * test to clear accesslog table
     */
    public function testClearTableAccessLogWithDate()
    {
        $accessLogsBefore = Admin_Controller_AccessLog::getInstance()->search();
        $opts = $this->_getOpts('access_log');
        
        ob_start();
        $this->_cli->clearTable($opts);
        // TODO check $out
        $out = ob_get_clean();
        
        $accessLogsAfter = Admin_Controller_AccessLog::getInstance()->search();
        $this->assertGreaterThan(count($accessLogsAfter), count($accessLogsBefore));
        $this->assertEquals(0, count($accessLogsAfter));
    }
    
    /**
     * get options
     * 
     * @param string $_table
     * @return Zend_Console_Getopt
     */
    protected function _getOpts($_table = NULL)
    {
        $opts = new Zend_Console_Getopt('abp:');
        $tomorrow = Tinebase_DateTime::now()->addDay(1)->toString('Y-m-d');
        $params = array('date=' . $tomorrow);
        if ($_table !== NULL) {
            $params[] = $_table;
        }
        $opts->setArguments($params);
        
        return $opts;
    }

    /**
     * test purge deleted records
     */
    public function testPurgeDeletedRecordsAddressbook()
    {
        $opts = $this->_getOpts('addressbook');
        $deletedRecord = $this->_addAndDeleteContact();
        
        ob_start();
        $this->_cli->purgeDeletedRecords($opts);
        $out = ob_get_clean();
        
        $this->assertContains('Removing all deleted entries before', $out);
        $this->assertContains('Cleared table addressbook (deleted ', $out);

        $contactBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $this->setExpectedException('Tinebase_Exception_NotFound');
        $contactBackend->get($deletedRecord->getId(), TRUE);
    }

    /**
     * test purge deleted records
     *
     * @see 0010249: Tinebase.purgeDeletedRecords fails
     */
    public function testPurgeDeletedRecordsAllTables()
    {
        $opts = $this->_getOpts();
        $deletedContact = $this->_addAndDeleteContact();
        $deletedLead = $this->_addAndDeleteLead();

        // test deleted contact is still there
        $contactBackend = Addressbook_Backend_Factory::factory(Addressbook_Backend_Factory::SQL);
        $contacts = $contactBackend->getMultipleByProperty($deletedContact->getId(), 'id', TRUE);
        $this->assertEquals(1, count($contacts));

        // delete tag too
        Tinebase_Tags::getInstance()->deleteTags($deletedContact->tags->getFirstRecord()->getId());
        
        ob_start();
        $this->_cli->purgeDeletedRecords($opts);
        $out = ob_get_clean();

        $this->assertContains('Removing all deleted entries before', $out);
        $this->assertContains('Cleared table addressbook (deleted ', $out);
        $this->assertContains('Cleared table metacrm_lead (deleted ', $out);
        $this->assertNotContains('Failed to purge', $out);

        // test deleted contact is gone
        $contacts = $contactBackend->getMultipleByProperty($deletedContact->getId(), 'id', TRUE);
        $this->assertEquals(0, count($contacts));

        $leadsBackend = new Crm_Backend_Lead();
        $leads = $leadsBackend->getMultipleByProperty($deletedLead->getId(), 'id', TRUE);
        $this->assertEquals(0, count($leads));
    }
    
    /**
     * creates and deletes a contact + returns the deleted record
     * 
     * @return Addressbook_Model_Contact
     */
    protected function _addAndDeleteContact()
    {
        $newContact = new Addressbook_Model_Contact(array(
            'n_family'          => 'PHPUNIT',
            'container_id'      => $this->_getPersonalContainer('Addressbook')->getId(),
            'tel_cell_private'  => '+49TELCELLPRIVATE',
            'tags'              => array(array('name' => 'temptag')),
        ));
        $newContact = Addressbook_Controller_Contact::getInstance()->create($newContact);
        Addressbook_Controller_Contact::getInstance()->delete($newContact->getId());

        return $newContact;
    }

    /**
     * creates and deletes a lead + returns the deleted record
     * 
     * @return Crm_Model_Lead
     */
    protected function _addAndDeleteLead()
    {
        $newLead = new Crm_Model_Lead(array(
            'lead_name'     => 'PHPUNIT Lead',
            'container_id'  => Tinebase_Container::getInstance()->getDefaultContainer('Crm')->getId(),
            'leadstate_id'  => 1,
            'leadtype_id'   => 1,
            'leadsource_id' => 1,
            'start'         => Tinebase_DateTime::now(),
        ));
        $newLead = Crm_Controller_Lead::getInstance()->create($newLead);
        Crm_Controller_Lead::getInstance()->delete($newLead->getId());
        
        return $newLead;
    }
    
    /**
     * test trigger events
     */
    public function testTriggerAsyncEvents()
    {
        if (Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}->{Tinebase_Config::FILESYSTEM_CREATE_PREVIEWS}) {
            self::markTestSkipped('FIXME: this currently fails with enabled previews - might be some locking issue in the test setup');
        }

        $scheduler = Tinebase_Core::getScheduler();
        $serverTime = null;
        /** @var Tinebase_Model_SchedulerTask $task */
        foreach ($scheduler->getAll() as $task) {
            if ($serverTime === null) {
                $serverTime = $task->server_time->getClone();
            }
            $task->next_run = $task->server_time->getClone()->subDay(100);
            $task->lock_id = null;
            $scheduler->update($task);
        }
        $opts = new Zend_Console_Getopt('abp:');
        $opts->setArguments(array());
        $this->_usernamesToDelete[] = Tinebase_User::SYSTEM_USER_CRON;
        $this->_releaseDBLockIds[] = 'Tinebase_Frontend_Cli::triggerAsyncEvents::' . Tinebase_Core::getTinebaseId();

        ob_start();
        $result = $this->_cli->triggerAsyncEvents($opts);
        ob_get_clean();
        static::assertTrue($result, 'cli triggerAsyncEvents did not return true');
        
        $cronuserId = Tinebase_Config::getInstance()->get(Tinebase_Config::CRONUSERID);
        $this->assertTrue(! empty($cronuserId), 'got empty cronuser id');
        $cronuser = Tinebase_User::getInstance()->getFullUserById($cronuserId);
        $this->assertEquals(Tinebase_User::SYSTEM_USER_CRON, $cronuser->accountLoginName);
        $adminGroup = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        
        $this->assertEquals($adminGroup->getId(), $cronuser->accountPrimaryGroup);

        foreach ($scheduler->getAll() as $task) {
            static::assertNotEmpty($task->last_run, 'task ' . $task->name . ' did not run successfully: ' .
                print_r($task->toArray(), true));
            static::assertTrue($task->last_run->isLaterOrEquals($serverTime),
                'task ' . $task->name . ' did not run successfully');
        }
    }

    /**
     * testMonitoringCheckDB
     * 
     * NOTE deactivated this test as it might affect other tests
     * 
     * @todo fix this test / make cli method testable
     */
    public function _testMonitoringCheckDB()
    {
        ob_start();
        $result = $this->_cli->monitoringCheckDB();
        $out = ob_get_clean();
        
        $this->assertEquals("DB CONNECTION OK\n", $out);
        $this->assertEquals(0, $result);
    }

    /**
     * testMonitoringCheckConfig
     */
    public function testMonitoringCheckConfig()
    {
        ob_start();
        $result = $this->_cli->monitoringCheckConfig();
        $out = ob_get_clean();
        
        $this->assertEquals("CONFIG FILE OK\n", $out);
        $this->assertEquals(0, $result);
    }

    /**
     * testMonitoringCheckCron
     */
    public function testMonitoringCheckCron()
    {
        ob_start();
        $result = $this->_cli->monitoringCheckCron();
        $out = ob_get_clean();
        
        $lastJob = Tinebase_Scheduler::getInstance()->getLastRun();
        if ($lastJob && $lastJob->last_run instanceof Tinebase_DateTime) {
            if ($lastJob->server_time->isLater($lastJob->last_run->getClone()->addHour(1))) {
                $this->assertContains('CRON FAIL: NO JOB IN THE LAST HOUR', $out);
                $this->assertEquals(1, $result);
            } else {
                $this->assertContains('CRON OK', $out);
                $this->assertEquals(0, $result);
            }
        } else {
            $this->assertEquals("CRON FAIL: NO LAST JOB FOUND\n", $out);
            $this->assertEquals(1, $result);
        }
    }

    /**
     * testMonitoringLoginNumber
     */
    public function testMonitoringLoginNumber()
    {
        ob_start();
        $result = $this->_cli->monitoringLoginNumber();
        $out = ob_get_clean();
        $this->assertEquals(0, $result);

        preg_match('/LOGINS OK \| count=(\d+);;;;/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(0, $matches[1]);
    }

    /**
     * testMonitoringActiveUsers
     *
     * TODO generalize monitoring tests
     */
    public function testMonitoringActiveUsers()
    {
        ob_start();
        $result = $this->_cli->monitoringActiveUsers();
        $out = ob_get_clean();
        $this->assertEquals(0, $result);

        preg_match('/ACTIVE USERS OK \| count=(\d+);;;;/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(1, $matches[1], 'at least unittest user should have logged in once');
    }

    /**
     * testMonitoringCheckCache
     */
    public function testMonitoringCheckCache()
    {
        ob_start();
        $result = $this->_cli->monitoringCheckCache();
        $out = ob_get_clean();

        self::assertContains('CACHE ', $out);
        self::assertLessThanOrEqual(1, $result);
    }

    /**
     * test cleanNotes
     */
    public function testCleanNotes()
    {
        // initial clean... tests don't clean up properly
        ob_start();
        $this->_cli->cleanNotes(new Zend_Console_Getopt([], []));
        $out = ob_get_clean();

        $noteController = Tinebase_Notes::getInstance();
        $models = Tinebase_Application::getInstance()->getModelsOfAllApplications();

        $allNotes = $noteController->getAllNotes();
        $dbArtifacts = $allNotes->count();

        $notesCreated = 0;
        $realDataNotes = 0;
        foreach($models as $model) {
            /** @var Tinebase_Record_Interface $instance */
            $instance = new $model([], true);
            if ($instance->has('notes')) {

                if (strpos($model, 'Tinebase') === 0) {
                    continue;
                }

                if (! $this->_idPropertyIsVarChar($instance, $model)) {
                    continue;
                }

                //create dead notes for each of those models
                $note = new Tinebase_Model_Note(array(
                    'note_type_id' => 1,
                    'note'  => 'test note text',
                    'record_id' => Tinebase_Record_Abstract::generateUID(),
                    'record_model' => $model,
                ));

                $noteController->addNote($note);
                ++$notesCreated;
            }
        }

        // add some real data
        $contact = new Addressbook_Model_Contact(array(
            'n_family' => 'someone',
            'notes' => array(array(
                'note_type_id' => 1,
                'note'  => 'test note text for real record',
            ))
        ));
        try {
            Addressbook_Controller_Contact::getInstance()->create($contact);
        } catch (Tinebase_Exception_Duplicate $ted) {

        }
        $realDataNotes += 2; // created a custom note

        $event = new Calendar_Model_Event(array(
            'organizer' => 'a@b.shooho',
            'dtstart'   => '2015-01-01 00:00:00',
            'dtend'     => '2015-01-01 01:00:00',
            'summary'   => 'test event',
            'notes' => array(array(
                'note_type_id' => 1,
                'note'  => 'test note text for real record',
            ))
        ));
        Calendar_Controller_Event::getInstance()->create($event);
        $realDataNotes += 2;  // created a custom note

        $allNotes = $noteController->getAllNotes();
        $this->assertEquals($notesCreated + $realDataNotes + $dbArtifacts, $allNotes->count(), 'notes created and notes in DB mismatch');

        ob_start();
        $this->_cli->cleanNotes(new Zend_Console_Getopt([], []));
        $out = ob_get_clean();

        $this->assertTrue(preg_match('/deleted \d+ notes/', $out) == 1, 'CLI job produced output: ' . $out);

        $allNotes = $noteController->getAllNotes();
        $this->assertEquals($realDataNotes + $dbArtifacts, $allNotes->count(), 'notes not completely cleaned');
    }

    protected function _idPropertyIsVarChar($instance, $model)
    {
        $controller = Tinebase_Core::getApplicationInstance($model);
        $backend = $controller->getBackend();
        if (method_exists($backend, 'getSchema')) {
            $schema = $backend->getSchema();
            if (isset($schema[$instance->getIdProperty()]) && strtoupper($schema[$instance->getIdProperty()]['DATA_TYPE']) != 'VARCHAR'
                && strtoupper($schema[$instance->getIdProperty()]['DATA_TYPE']) != 'CHAR'
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * test cleanCustomfields
     */
    public function testCleanCustomfields()
    {
        $customFieldController = Tinebase_CustomField::getInstance();
        $models = Tinebase_Application::getInstance()->getModelsOfAllApplications();

        $customFieldConfigs = $customFieldController->searchConfig();
        foreach ($customFieldConfigs as $customFieldConfig) {
            $filter = new Tinebase_Model_CustomField_ValueFilter(array(
                array('field' => 'customfield_id', 'operator' => 'equals', 'value' => $customFieldConfig->id)
            ));
            $customFieldValues = $customFieldController->search($filter);

            $this->assertEquals(0, $customFieldValues->count(), 'custom field values found: '
                . print_r($customFieldValues->toArray(), true) . ' of customfield '
                . print_r($customFieldConfig->toArray(), true));
        }

        $customFieldsCreated = 0;
        $realDataCustomFields = 0;
        foreach($models as $model) {
            /** @var Tinebase_Record_Interface $instance */
            $instance = new $model([], true);
            list($appName) = explode('_', $model);

            if ($instance->has('customfields')) {

                if (strpos($model, 'Tinebase') === 0) {
                    continue;
                }

                if (! $this->_idPropertyIsVarChar($instance, $model)) {
                    continue;
                }

                $cf = $customFieldController->addCustomField(
                    new Tinebase_Model_CustomField_Config(array(
                        'application_id'    => Tinebase_Application::getInstance()->getApplicationByName($appName)->getId(),
                        'model'             => $model,
                        'name'              => $model,
                        'definition'        => array(
                            'label'             => $model,
                            'length'            => 255,
                            'required'          => false,
                            'type'              => 'string',
                        ),
                    ))
                );

                //create dead customfield value for each of those models
                $customFieldValue = new Tinebase_Model_CustomField_Value(array(
                    'record_id' => Tinebase_Record_Abstract::generateUID(),
                    'customfield_id' => $cf->getId(),
                    'value' => 'shoo value',
                ));

                $customFieldController->saveCustomFieldValue($customFieldValue);
                ++$customFieldsCreated;
            }
        }

        // add some real data
        $contact = new Addressbook_Model_Contact(array(
            'n_family' => 'someone',
            'customfields' => array(
                'Addressbook_Model_Contact' => 'test customfield text for real record',
            )
        ));
        Addressbook_Controller_Contact::getInstance()->create($contact);
        $realDataCustomFields += 1;

        $event = new Calendar_Model_Event(array(
            'organizer' => 'a@b.shooho',
            'dtstart'   => '2015-01-01 00:00:00',
            'dtend'     => '2015-01-01 01:00:00',
            'summary'   => 'test event',
            'customfields' => array(
                'Calendar_Model_Event' => 'test customfield text for real record',
            )
        ));
        Calendar_Controller_Event::getInstance()->create($event);
        $realDataCustomFields += 1;

        $sum = 0;
        $customFieldConfigs = $customFieldController->searchConfig();
        foreach($customFieldConfigs as $customFieldConfig) {
            $filter = new Tinebase_Model_CustomField_ValueFilter(array(
                array('field' => 'customfield_id', 'operator' => 'equals', 'value' => $customFieldConfig->id)
            ));
            $customFieldValues = $customFieldController->search($filter);

            $sum += $customFieldValues->count();
        }
        $this->assertEquals($customFieldsCreated + $realDataCustomFields, $sum, 'customfields created and customfields in DB mismatch');

        ob_start();
        $this->_cli->cleanCustomfields();
        $out = ob_get_clean();

        $this->assertTrue(preg_match('/deleted \d+ customfield values/', $out) == 1, 'CLI job produced output: ' . $out);

        $sum = 0;
        foreach($customFieldConfigs as $customFieldConfig) {
            $filter = new Tinebase_Model_CustomField_ValueFilter(array(
                array('field' => 'customfield_id', 'operator' => 'equals', 'value' => $customFieldConfig->id)
            ));
            $customFieldValues = $customFieldController->search($filter);

            $sum += $customFieldValues->count();
        }
        $this->assertEquals($realDataCustomFields, $sum, 'customfields not completely cleaned');
    }


    /**
     * testUserReport
     *
     * TODO maybe set locale to EN to ease string testing
     */
    public function testUserReport()
    {
        Zend_Registry::set('locale', new Zend_Locale('en'));
        ob_start();
        $result = $this->_cli->userReport();
        $out = ob_get_clean();
        $this->assertEquals(0, $result);

        preg_match('/Number of users \(total\): (\d+)/', $out, $matches);
        $this->assertGreaterThan(1, count($matches), $out);
        $this->assertGreaterThanOrEqual(1, $matches[1], 'at least unittest user should be in users: ' . $out);

        preg_match('/Number of users \(enabled\): (\d+)/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(1, $matches[1], 'at least unittest user should be in users');

        preg_match('/Number of users \(disabled\): (\d+)/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(0, $matches[1]);

        preg_match('/Number of users \(blocked\): (\d+)/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(0, $matches[1]);

        preg_match('/Number of users \(expired\): (\d+)/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(0, $matches[1]);

        preg_match('/Number of distinct users \(lastmonth\): (\d+)/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(1, $matches[1]);

        preg_match('/Number of distinct users \(last 3 months\): (\d+)/', $out, $matches);
        $this->assertGreaterThan(1, count($matches));
        $this->assertGreaterThanOrEqual(1, $matches[1]);

        $this->assertContains(Tinebase_Core::getUser()->accountLoginName, $out, 'unittest login name should be found');
        $this->assertContains('Unit Test Client', $out, 'unittest should have a user agent');
    }
}
