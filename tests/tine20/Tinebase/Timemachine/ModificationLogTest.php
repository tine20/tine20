<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_Timemachine_ModificationLogTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Tinebase_Timemachine_ModificationLog
     */
    protected $_modLogClass;

    /**
     * @var Tinebase_Record_RecordSet
     */
    protected $_logEntries;

    /**
     * @var Tinebase_Record_RecordSet
     * Persistant Records we need to cleanup at tearDown()
     */
    protected $_persistantLogEntries;

    /**
     * @var array holds recordId's we create log entries for
     */
    protected $_recordIds = array();

    protected $_oldFileSystemConfig = null;


    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        $suite = new PHPUnit_Framework_TestSuite('Tinebase_Timemachine_ModificationLogTest');
        PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Lets update a record tree times
     *
     * @access protected
     */
    protected function setUp()
    {
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $this->_oldFileSystemConfig = clone Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM};

        $now = new Tinebase_DateTime();
        $this->_modLogClass = Tinebase_Timemachine_ModificationLog::getInstance();
        $this->_persistantLogEntries = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog');
        $this->_recordIds = array('5dea69be9c72ea3d263613277c3b02d529fbd8bc');

        $tinebaseApp = Tinebase_Application::getInstance()->getApplicationByName('Tinebase');

        $this->_logEntries = new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array(
            array(
                'application_id' => $tinebaseApp,
                'record_id' => $this->_recordIds[0],
                'record_type' => 'TestType',
                'record_backend' => 'TestBackend',
                'modification_time' => $this->_cloner($now)->addDay(-2),
                'modification_account' => 7,
                'modified_attribute' => 'FirstTestAttribute',
                'old_value' => 'Hamburg',
                'new_value' => 'Bremen',
                'client' => 'unittest'
            ),
            array(
                'application_id' => $tinebaseApp,
                'record_id' => $this->_recordIds[0],
                'record_type' => 'TestType',
                'record_backend' => 'TestBackend',
                'modification_time' => $this->_cloner($now)->addDay(-1),
                'modification_account' => 7,
                'modified_attribute' => 'FirstTestAttribute',
                'old_value' => 'Bremen',
                'new_value' => 'Frankfurt',
                'client' => 'unittest'
            ),
            array(
                'application_id' => $tinebaseApp,
                'record_id' => $this->_recordIds[0],
                'record_type' => 'TestType',
                'record_backend' => 'TestBackend',
                'modification_time' => $this->_cloner($now),
                'modification_account' => 7,
                'modified_attribute' => 'FirstTestAttribute',
                'old_value' => 'Frankfurt',
                'new_value' => 'Stuttgart',
                'client' => 'unittest'
            ),
            array(
                'application_id' => $tinebaseApp,
                'record_id' => $this->_recordIds[0],
                'record_type' => 'TestType',
                'record_backend' => 'TestBackend',
                'modification_time' => $this->_cloner($now)->addDay(-2),
                'modification_account' => 7,
                'modified_attribute' => 'SecondTestAttribute',
                'old_value' => 'Deutschland',
                'new_value' => 'Östereich',
                'client' => 'unittest'
            ),
            array(
                'application_id' => $tinebaseApp,
                'record_id' => $this->_recordIds[0],
                'record_type' => 'TestType',
                'record_backend' => 'TestBackend',
                'modification_time' => $this->_cloner($now)->addDay(-1)->addSecond(1),
                'modification_account' => 7,
                'modified_attribute' => 'SecondTestAttribute',
                'old_value' => 'Östereich',
                'new_value' => 'Schweitz',
                'client' => 'unittest'
            ),
            array(
                'application_id' => $tinebaseApp->getId(),
                'record_id' => $this->_recordIds[0],
                'record_type' => 'TestType',
                'record_backend' => 'TestBackend',
                'modification_time' => $this->_cloner($now),
                'modification_account' => 7,
                'modified_attribute' => 'SecondTestAttribute',
                'old_value' => 'Schweitz',
                'new_value' => 'Italien',
                'client' => 'unittest'
            )), true, false);

        foreach ($this->_logEntries as $logEntry) {
            $this->_modLogClass->setModification($logEntry);
            $this->_persistantLogEntries->addRecord($logEntry/*$this->_modLogClass->getModification($id)*/);
        }
    }

    /**
     * cleanup database
     * @access protected
     */
    protected function tearDown()
    {
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM} = $this->_oldFileSystemConfig;

        Tinebase_TransactionManager::getInstance()->rollBack();
    }

    /**
     * tests that the returned mod logs equal the initial ones we defined
     * in this test setup.
     * If this works, also the setting of logs works!
     *
     */
    public function testGetModification()
    {
        foreach ($this->_logEntries as $num => $logEntry) {
            $rawLogEntry = $logEntry->toArray();
            $rawPersistantLogEntry = $this->_persistantLogEntries[$num]->toArray();

            foreach ($rawLogEntry as $field => $value) {
                $persistantValue = $rawPersistantLogEntry[$field];
                if ($value != $persistantValue) {
                    $this->fail("Failed asserting that contents of saved LogEntry #$num in field $field equals initial datas. \n" .
                        "Expected '$value', got '$persistantValue'");
                }
            }
        }
        $this->assertTrue(true);
    }

    /**
     * tests computation of a records differences described by a set of modification logs
     */
    public function testComputeDiff()
    {
        $diff = $this->_modLogClass->computeDiff($this->_persistantLogEntries);
        $this->assertEquals(2, count($diff->diff)); // we changed two attributes
        $changedAttributes = Tinebase_Timemachine_ModificationLog::getModifiedAttributes($this->_persistantLogEntries);
        foreach ($changedAttributes as $attrb) {
            switch ($attrb) {
                case 'FirstTestAttribute':
                    $this->assertEquals('Hamburg', $diff->oldData[$attrb]);
                    $this->assertEquals('Stuttgart', $diff->diff[$attrb]);
                    break;
                case 'SecondTestAttribute':
                    $this->assertEquals('Deutschland', $diff->oldData[$attrb]);
                    $this->assertEquals('Italien', $diff->diff[$attrb]);
            }
        }
    }

    /**
     * get modifications test
     */
    public function testGetModifications()
    {
        $testBase = array(
            'record_id' => '5dea69be9c72ea3d263613277c3b02d529fbd8bc',
            'type' => 'TestType',
            'backend' => 'TestBackend'
        );
        $firstModificationTime = $this->_persistantLogEntries[0]->modification_time;
        $lastModificationTime = $this->_persistantLogEntries[count($this->_persistantLogEntries) - 1]->modification_time;

        $toTest[] = $testBase + array(
                'from_add' => 'addDay,-3',
                'until_add' => 'addDay,1',
                'nums' => 6
            );
        $toTest[] = $testBase + array(
                'nums' => 4
            );
        $toTest[] = $testBase + array(
                'account' => Tinebase_Record_Abstract::generateUID(),
                'nums' => 0
            );

        foreach ($toTest as $params) {
            $from = clone $firstModificationTime;
            $until = clone $lastModificationTime;

            if (isset($params['from_add'])) {
                list($fn, $p) = explode(',', $params['from_add']);
                $from->$fn($p);
            }
            if (isset($params['until_add'])) {
                list($fn, $p) = explode(',', $params['until_add']);
                $until->$fn($p);
            }

            $account = isset($params['account']) ? $params['account'] : NULL;
            $diffs = $this->_modLogClass->getModifications('Tinebase', $params['record_id'], $params['type'], $params['backend'], $from, $until, $account);
            $count = 0;
            foreach ($diffs as $diff) {
                if ($diff->record_id == $params['record_id']) {
                    $count++;
                }
            }
            $this->assertEquals($params['nums'], $diffs->count());
        }
    }

    /**
     * test modlog undo
     *
     * @see 0006252: allow to undo history items (modlog)
     * @see 0000554: modlog: records can't be updated in less than 1 second intervals
     */
    public function testUndo()
    {
        // create a record
        $contact = Addressbook_Controller_Contact::getInstance()->create(new Addressbook_Model_Contact(array(
            'n_family' => 'tester',
            'tel_cell' => '+491234',
        )));
        // change something using the record controller
        $contact->tel_cell = NULL;
        $contact = Addressbook_Controller_Contact::getInstance()->update($contact);

        // fetch modlog and test seq
        /** @var Tinebase_Model_ModificationLog $modlog */
        $modlog = $this->_modLogClass->getModifications('Addressbook', $contact->getId(), NULL, 'Sql',
            Tinebase_DateTime::now()->subSecond(5), Tinebase_DateTime::now())->getLastRecord();
        $diff = new Tinebase_Record_Diff(json_decode($modlog->new_value, true));
        $this->assertTrue($modlog !== NULL);
        $this->assertEquals(2, $modlog->seq);
        $this->assertEquals('+491234', $diff->oldData['tel_cell']);

        // delete
        Addressbook_Controller_Contact::getInstance()->delete($contact->getId());

        $filter = new Tinebase_Model_ModificationLogFilter(array(
            array('field' => 'record_type',         'operator' => 'equals', 'value' => 'Addressbook_Model_Contact'),
            array('field' => 'record_id',           'operator' => 'equals', 'value' => $contact->getId()),
            array('field' => 'modification_time',   'operator' => 'after',  'value' =>  Tinebase_DateTime::now()->subDay(1)),
            array('field' => 'change_type',         'operator' => 'not',    'value' => Tinebase_Timemachine_ModificationLog::CREATED)
        ));

        $result = $this->_modLogClass->undo($filter, true);
        $this->assertEquals(2, $result['totalcount'], 'did not get 2 undone modlog: ' . print_r($result, TRUE));

        // check record after undo
        $contact = Addressbook_Controller_Contact::getInstance()->get($contact);
        $this->assertEquals('+491234', $contact->tel_cell);
    }

    /**
     * purges mod log entries of given recordIds
     *
     * @param mixed [string|array|Tinebase_Record_RecordSet] $_recordIds
     *
     * @todo should be removed when other tests do not need this anymore
     */
    public static function purgeLogs($_recordIds)
    {
        $table = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'timemachine_modlog'));

        foreach ((array)$_recordIds as $recordId) {
            $table->delete($table->getAdapter()->quoteInto('record_id = ?', $recordId));
        }
    }

    /**
     * Workaround as the php clone operator does not return cloned
     * objects right hand sided
     *
     * @param object $_object
     * @return object
     */
    protected function _cloner($_object)
    {
        return clone $_object;
    }

    /**
     * testDateTimeModlog
     *
     * @see 0000996: add changes in relations/linked objects to modlog/history
     */
    public function testDateTimeModlog()
    {
        $task = Tasks_Controller_Task::getInstance()->create(new Tasks_Model_Task(array(
            'summary' => 'test task',
        )));

        $task->due = Tinebase_DateTime::now();
        Tasks_Controller_Task::getInstance()->update($task);

        $task->seq = 1;
        $modlog = $this->_modLogClass->getModificationsBySeq(
            Tinebase_Application::getInstance()->getApplicationByName('Tasks')->getId(),
            $task, 2);

        $diff = new Tinebase_Record_Diff(json_decode($modlog->getFirstRecord()->new_value, true));
        $this->assertEquals(1, count($modlog));
        $this->assertEquals((string)$task->due, (string)($diff->diff['due']), 'new value mismatch: ' . print_r($modlog->toArray(), TRUE));
    }

    public function testGetReplicationModificationsByInstanceSeq()
    {
        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        /** @var Tinebase_Acl_Roles $roleController */
        $roleController = Tinebase_Core::getApplicationInstance('Tinebase_Model_Role');
        $this->assertEquals('Tinebase_Role', get_class($roleController));

        $role = new Tinebase_Model_Role(array('name' => 'unittest test role'));
        $role = $roleController->create($role);

        $roleController->addRoleMember($role->getId(), array(
                'id' => Tinebase_Core::getUser()->getId(),
                'type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
        );
        $roleController->addRoleMember($role->getId(), array(
                'id' => 'test1',
                'type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
        );
        $roleController->addRoleMember($role->getId(), array(
                'id' => 'test2',
                'type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER)
        );
        $roleController->removeRoleMember($role->getId(), array(
            'id' => 'test2',
            'type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_USER
        ));

        $role = $roleController->get($role->getId());

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $roleModifications = $modifications->filter('record_type', 'Tinebase_Model_Role');
        static::assertEquals(5, $roleModifications->count(), 'should have 5 mod logs to process');
        //$groupModifications = $modifications->filter('record_type', 'Tinebase_Model_Group');
        //$userModifications = $modifications->filter('record_type', '/Tinebase_Model_User.*/', true);

        // rollback
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $notFound = false;
        try {
            $roleController->get($role->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'roll back did not work...');

        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs($roleModifications);
        $this->assertTrue($result, 'applyReplicationModLogs failed');

        $newRole = $roleController->get($role->getId());

        $diff = $role->diff($newRole, array('seq', 'creation_time', 'created_by', 'last_modified_by', 'last_modified_time'));

        $this->assertTrue($diff->isEmpty(), 'diff should be empty: ' . print_r($diff, true));

        $mod = clone ($roleModifications->getByIndex(2));
        $diff = new Tinebase_Record_Diff(json_decode($mod->new_value, true));
        $rsDiff = new Tinebase_Record_RecordSetDiff($diff->diff['members']);
        $rsDiff->removed = $rsDiff->added;
        $modified = $rsDiff->added;
        $modified[0]['account_id'] = 'test3';
        $rsDiff->added = $modified;
        $diffArray = $diff->diff;
        $diffArray['members'] = $rsDiff;
        $diff->diff = $diffArray;
        $mod->new_value = json_encode($diff->toArray());

        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');

        /** @var Tinebase_Model_Role $newRole */
        $newRole = $roleController->get($role->getId());
        $this->assertEquals(1, $newRole->members->filter('account_id', 'test3')->count(), 'record set diff modified didn\'t work, test3 not found');
    }

    public function testCalendarEventNoReplicatable()
    {
        $container = new Tinebase_Model_Container([
            'name'              => 'unittest test cal repl container',
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        ]);
        $container = Tinebase_Container::getInstance()->addContainer($container);

        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();
        $eventCntr = Calendar_Controller_Event::getInstance();

        $event = new Calendar_Model_Event([], true);
        $event->container_id = $container;
        static::assertFalse($event->isReplicable(), 'expected event not to be replicatable');
        $event->dtstart = Tinebase_DateTime::now();
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->summary = 'St. Martin';
        $event->organizer = Tinebase_Core::getUser()->contact_id;

        $eventCntr->create($event);

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instance_seq);
        $containerModifications = $modifications->filter('record_type', Calendar_Model_Event::class);
        static::assertSame(0, $containerModifications->count(), 'should have 0 mod logs to process');
    }

    public function testCalendarEventReplication()
    {
        $container = new Tinebase_Model_Container([
            'name'              => 'unittest test cal repl container',
            'type'              => Tinebase_Model_Container::TYPE_SHARED,
            'backend'           => 'sql',
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
            'xprops'            => [Calendar_Model_Event::XPROPS_REPLICATABLE => true],
        ]);
        $container = Tinebase_Container::getInstance()->addContainer($container);

        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();
        $eventCntr = Calendar_Controller_Event::getInstance();

        $event = new Calendar_Model_Event([], true);
        $event->container_id = $container;
        static::assertTrue($event->isReplicable(), 'expected event to be replicatable');
        $event->dtstart = Tinebase_DateTime::now();
        $event->dtend = clone $event->dtstart;
        $event->dtend->addMinute(30);
        $event->summary = 'St. Martin';
        $event->organizer = Tinebase_Core::getUser()->contact_id;
        //$event->rrule

        $createdEvent = $eventCntr->create($event);

        $updatedEvent = clone $createdEvent;
        $updatedEvent->summary = 'Nikolaus';
        $updatedEvent->dtstart->subDay(1);
        $updatedEvent->dtend->subDay(1);
        $updatedEvent = $eventCntr->update($updatedEvent);

        $eventCntr->delete($updatedEvent->getId());

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()
            ->getReplicationModificationsByInstanceSeq($instance_seq);
        $containerModifications = $modifications->filter('record_type', Calendar_Model_Event::class);
        static::assertSame(3, $containerModifications->count(), 'should have 3 mod logs to process');

        // rollback
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());
        Tinebase_Container::getInstance()->addContainer($container);

        // create the event
        $mod = $containerModifications->getFirstRecord();
        static::assertNotNull($mod);
        $containerModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()
            ->applyReplicationModLogs(new Tinebase_Record_RecordSet(Tinebase_Model_ModificationLog::class, [$mod]));
        static::assertTrue($result, 'applyReplicationModLogs failed');
        $newEvent = $eventCntr->get($createdEvent->getId());
        static::assertSame($createdEvent->summary, $newEvent->summary);
        static::assertSame($createdEvent->container_id, $newEvent->container_id);
        static::assertEquals($createdEvent->organizer, $newEvent->organizer);
        static::assertEquals($createdEvent->dtstart, $newEvent->dtstart);
        static::assertEquals($createdEvent->dtend, $newEvent->dtend);

        // update the event
        $mod = $containerModifications->getFirstRecord();
        static::assertNotNull($mod);
        $containerModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()
            ->applyReplicationModLogs(new Tinebase_Record_RecordSet(Tinebase_Model_ModificationLog::class, [$mod]));
        static::assertTrue($result, 'applyReplicationModLogs failed');
        $newEvent = $eventCntr->get($updatedEvent->getId());
        static::assertSame($updatedEvent->summary, $newEvent->summary);
        static::assertSame($updatedEvent->container_id, $newEvent->container_id);
        static::assertEquals($updatedEvent->organizer, $newEvent->organizer);
        static::assertEquals($updatedEvent->dtstart, $newEvent->dtstart);
        static::assertEquals($updatedEvent->dtend, $newEvent->dtend);

        // delete the event
        $mod = $containerModifications->getFirstRecord();
        static::assertNotNull($mod);
        $containerModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()
            ->applyReplicationModLogs(new Tinebase_Record_RecordSet(Tinebase_Model_ModificationLog::class, [$mod]));
        static::assertTrue($result, 'applyReplicationModLogs failed');
        try {
            $eventCntr->get($updatedEvent->getId());
            static::fail('expect not found exception');
        } catch (Tinebase_Exception_NotFound $e) {}
    }

    public function testContainerReplication()
    {
        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $containerController = Tinebase_Container::getInstance();

        $container = new Tinebase_Model_Container(array(
            'name' => 'unittest test container',
            'type' => Tinebase_Model_Container::TYPE_SHARED,
            'backend' => 'sql',
            'application_id' => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
            'model'             => Calendar_Model_Event::class,
        ));
        $container = $containerController->addContainer($container);

        $container->color = '#FFFFFF';
        $containerController->update($container);

        $grants = $containerController->getGrantsOfContainer($container, true);
        static::assertEquals(2, $grants->count(), 'should find 2 grant records');
        /** @var Tinebase_Model_Grants $grant */
        $grant = $grants->filter('account_type', 'anyone')->getFirstRecord();
        static::assertNotNull($grant);
        static::assertEquals(false, $grant->{Tinebase_Model_Grants::GRANT_EDIT}, 'edit grant should be false');
        $grant->{Tinebase_Model_Grants::GRANT_EDIT} = true;
        $grant = $grants->filter('account_id', Tinebase_Core::getUser()->getId())->getFirstRecord();
        static::assertNotNull($grant);
        static::assertEquals(true, $grant->{Tinebase_Model_Grants::GRANT_EDIT}, 'edit grant should be true');
        $grant->{Tinebase_Model_Grants::GRANT_EDIT} = false;
        $containerController->setGrants($container->getId(), $grants, true);

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $containerModifications = $modifications->filter('record_type', Tinebase_Model_Container::class);
        static::assertEquals(4, $containerModifications->count(), 'should have 4 mod logs to process');

        // rollback
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $containerController->resetClassCache();
        $notFound = false;
        try {
            // don't avoid Cache, so use getContainerById, not get!
            $containerController->getContainerById($container->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        static::assertTrue($notFound, 'roll back did not work...');

        // create the container
        $mod = $containerModifications->getFirstRecord();
        static::assertNotNull($mod);
        $containerModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        static::assertTrue($result, 'applyReplicationModLogs failed');
        // avoid Cache, so use get, not getContainerById
        /** @var Tinebase_Model_Container $newContainer */
        $newContainer = $containerController->get($container->getId());
        static::assertEquals($container->name, $newContainer->name);
        static::assertTrue(empty($newContainer->color), 'color not empty');

        // set grants from initial container create, nothing changes actually
        $mod = $containerModifications->getFirstRecord();
        static::assertNotNull($mod);
        $containerModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        static::assertTrue($result, 'applyReplicationModLogs failed');

        // change color
        $mod = $containerModifications->getFirstRecord();
        static::assertNotNull($mod);
        $containerModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        static::assertTrue($result, 'applyReplicationModLogs failed');
        // avoid Cache, so use get, not getContainerById
        $newContainer = $containerController->get($container->getId());
        static::assertEquals('#FFFFFF', $newContainer->color, 'color not set properly');

        //change grants
        $mod = $containerModifications->getFirstRecord();
        static::assertNotNull($mod);
        $containerModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $grants = $containerController->getGrantsOfContainer($container, true);
        static::assertEquals(2, $grants->count(), 'should find 2 grant records');
        $grant = $grants->filter('account_type', 'anyone')->getFirstRecord();
        static::assertNotNull($grant);
        static::assertEquals(true, $grant->{Tinebase_Model_Grants::GRANT_EDIT}, 'edit grant should be true');
        $grant = $grants->filter('account_id', Tinebase_Core::getUser()->getId())->getFirstRecord();
        static::assertNotNull($grant);
        static::assertEquals(false, $grant->{Tinebase_Model_Grants::GRANT_EDIT}, 'edit grant should be false');
    }

    /**
     * testGroupReplication
     */
    public function testGroupReplication()
    {
        if (Tinebase_User::getConfiguredBackend() === Tinebase_User::LDAP ||
            Tinebase_User::getConfiguredBackend() === Tinebase_User::ACTIVEDIRECTORY) {
            $this->markTestSkipped('FIXME: Does not work with LDAP/AD backend');
        }

        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $groupController = Tinebase_Group::getInstance();

        $group = new Tinebase_Model_Group(array('name' => 'unittest test group'));
        $group = $groupController->addGroup($group);

        $groupController->addGroupMember($group->getId(), Tinebase_Core::getUser()->getId());
        $groupController->removeGroupMember($group->getId(), Tinebase_Core::getUser()->getId());
        $group->description = 'test description';
        $group = $groupController->updateGroup($group);
        $groupController->deleteGroups($group->getId());

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $groupModifications = $modifications->filter('record_type', 'Tinebase_Model_Group');
        static::assertEquals(5, $groupModifications->count(), 'should have 5 mod logs to process');

        if ($groupController instanceof Tinebase_Group_Interface_SyncAble) {
            $this->assertEquals(0, $groupModifications->count(), ' for syncables group replication should be turned off!');
            // syncables should not create any replication logs, we can skip this test as of here
            return;
        }

        // rollback
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $notFound = false;
        try {
            $groupController->getGroupById($group->getId());
        } catch (Tinebase_Exception_Record_NotDefined $ternd) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'roll back did not work...');

        // create the group
        $mod = $groupModifications->getFirstRecord();
        $groupModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $newGroup = $groupController->getGroupById($group->getId());
        $this->assertEquals($group->name, $newGroup->name);
        $this->assertEmpty($groupController->getGroupMembers($newGroup->getId()), 'group members not empty');

        // add group members
        $mod = $groupModifications->getFirstRecord();
        $groupModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $this->assertEquals(1, count($groupController->getGroupMembers($newGroup->getId())), 'group members not created');

        // remove group members
        $mod = $groupModifications->getFirstRecord();
        $groupModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $this->assertEmpty($groupController->getGroupMembers($newGroup->getId()), 'group members not deleted');

        // update group description
        $mod = $groupModifications->getFirstRecord();
        $groupModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $newGroup = $groupController->getGroupById($group->getId());
        $this->assertEquals('test description', $newGroup->description);

        // delete group
        $mod = $groupModifications->getFirstRecord();
        $groupModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $notFound = false;
        try {
            $groupController->getGroupById($group->getId());
        } catch (Tinebase_Exception_Record_NotDefined $ternd) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'delete group did not work');
    }

    public function testUserReplication()
    {
        if (PHP_VERSION_ID >= 70200) {
            static::markTestSkipped('FIXME fix for php 7.2+');
        }

        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            static::markTestSkipped('pgsql gets dropped anyway');
        }

        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();

        $userController = Tinebase_User::getInstance();

        $newUser = Tinebase_User_SqlTest::getTestRecord();
        $newUser->accountEmailAddress = Tinebase_Record_Abstract::generateUID(5) . $newUser->accountEmailAddress;
        $newUser = $userController->addUser($newUser);
        $userController->setPassword($newUser->getId(), 'ssha256Password');
        $userController->setStatus($newUser->getId(), Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED);
        $userController->setStatus($newUser->getId(), Tinebase_Model_User::ACCOUNT_STATUS_ENABLED);
        $expiryDate = Tinebase_DateTime::now();
        $userController->setExpiryDate($newUser->getId(), $expiryDate);
        /** @var Addressbook_Model_Contact $contact */
        $contact = Addressbook_Controller_Contact::getInstance()->get($newUser->contact_id);
        $contact->n_given = 'shoo';
        Addressbook_Controller_Contact::getInstance()->update($contact);
        $userController->deleteUser($newUser->getId());

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $userModifications = $modifications->filter('record_type', '/Tinebase_Model_(Full)?User/', true);

        if ($userController instanceof Tinebase_User_Interface_SyncAble) {
            $this->assertEquals(0, $userModifications->count(), ' for syncables user replication should be turned off!');
            // syncables should not create any replication logs, we can skip this test as of here
            return;
        }

        // rollback
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        $notFound = false;
        try {
            $userController->getUserById($newUser->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'roll back did not work...');

        // create the user
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $mods[] = $mod;
        // set container id
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $mods[] = $mod;
        // set visibility
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $mods[] = $mod;
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', $mods));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $replicationUser = $userController->getUserById($newUser->getId(), 'Tinebase_Model_FullUser');
        $this->assertEquals($replicationUser->name, $newUser->name);

        // reset the user pwd
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        /** @var Tinebase_Model_FullUser $replicationUser */
        $replicationUser = $userController->getUserById($newUser->getId(), 'Tinebase_Model_FullUser');
        $authBackend = Tinebase_Auth_Factory::factory('Sql');
        $authBackend->setIdentity($replicationUser->accountLoginName);
        $authBackend->setCredential('ssha256Password');
        $authResult = $authBackend->authenticate();
        $this->assertEquals(Zend_Auth_Result::SUCCESS, $authResult->getCode(), 'changing password did not work: '
            . print_r($authResult->getMessages(), true));

        // set status to expired
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        /** @var Tinebase_Model_FullUser $replicationUser */
        $replicationUser = $userController->getUserById($newUser->getId(), 'Tinebase_Model_FullUser');
        $this->assertEquals(Tinebase_Model_User::ACCOUNT_STATUS_EXPIRED, $replicationUser->accountStatus);

        // set status to enabled
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        /** @var Tinebase_Model_FullUser $replicationUser */
        $replicationUser = $userController->getUserById($newUser->getId(), 'Tinebase_Model_FullUser');
        $this->assertEquals(Tinebase_Model_User::ACCOUNT_STATUS_ENABLED, $replicationUser->accountStatus);

        // set expiry date
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        /** @var Tinebase_Model_FullUser $replicationUser */
        $replicationUser = $userController->getUserById($newUser->getId(), 'Tinebase_Model_FullUser');
        $this->assertTrue($expiryDate->equals($replicationUser->accountExpires));

        // update contact
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        /** @var Tinebase_Model_FullUser $replicationUser */
        $replicationUser = $userController->getUserById($newUser->getId(), 'Tinebase_Model_FullUser');
        $this->assertEquals('shoo', $replicationUser->accountFirstName);

        // delete user
        $mod = $userModifications->getFirstRecord();
        $userModifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $notFound = false;
        try {
            $userController->getUserById($newUser->getId());
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'delete didn\'t work');
    }

    public function testFileManagerReplication()
    {
        if (Tinebase_Core::getDb() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
            static::markTestSkipped('on CI it failes, locally on ansible vagrant with pgsql it runs, even if all tests run!');
        }
        
        Tinebase_Config::getInstance()->{Tinebase_Config::FILESYSTEM}
            ->{Tinebase_Config::FILESYSTEM_MODLOGACTIVE} = true;
        $instance_seq = Tinebase_Timemachine_ModificationLog::getInstance()->getMaxInstanceSeq();
        $testPath = '/' . Tinebase_Model_Container::TYPE_PERSONAL . '/' . Tinebase_Core::getUser()->accountLoginName
            . '/unittestTestPath';
        $fmController = Filemanager_Controller_Node::getInstance();
        $filesystem = Tinebase_FileSystem::getInstance();
        $filesystem->resetBackends();
        Tinebase_Core::clearAppInstanceCache();

        $nodes = $filesystem->_getTreeNodeBackend()->search(new Tinebase_Model_Tree_Node_Filter(array(
            array('field' => 'is_deleted', 'operator' => 'equals', 'value' => true)
        )));
        if ($nodes->count() > 0) {
            $filesystem->_getTreeNodeBackend()->delete($nodes->getId());
        }
        $objects = $filesystem->getFileObjectBackend()->search(new Tinebase_Model_Tree_FileObjectFilter(array(
            array('field' => 'is_deleted', 'operator' => 'equals', 'value' => true)
        )));
        if ($objects->count() > 0) {
            $filesystem->_getTreeNodeBackend()->delete($objects->getId());
        }

        // create two folders
        $fmController->createNodes(array($testPath, $testPath . '/subfolder'),
            Tinebase_Model_Tree_FileObject::TYPE_FOLDER);


        // set Grants
        $testPathNode = $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        $testPathNode = $fmController->resolveGrants($testPathNode)->getFirstRecord();
        $grantRecord = $testPathNode->grants->getFirstRecord();
        $grantRecord->id = null;
        $testPathNode->grants = new Tinebase_Record_RecordSet('Tinebase_Model_Grants', array($grantRecord));
        $testPathNode->acl_node = $testPathNode->getId();
        $testNodeGrants = $fmController->update($testPathNode);
        $testNodeGrants->grants = null;
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($testNodeGrants);

        // update Grants 1
        $testPathNode->grants = null;
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($testPathNode);
        foreach (Tinebase_Model_Grants::getAllGrants() as $grant) {
            $testPathNode->grants->getFirstRecord()->$grant = false;
        }
        $testPathNode->grants->getFirstRecord()->{Tinebase_Model_Grants::GRANT_ADMIN} = true;
        $testPathNode->grants->getFirstRecord()->{Tinebase_Model_Grants::GRANT_EDIT} = true;
        $updatedNodeGrants = $fmController->update($testPathNode);
        $updatedNodeGrants->grants = null;
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($updatedNodeGrants);

        // update Grants 2
        $testPathNode->grants = null;
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($testPathNode);
        $testPathNode->grants->getFirstRecord()->{Tinebase_Model_Grants::GRANT_EDIT} = false;
        $updatedTwoNodeGrants = $fmController->update($testPathNode);
        $updatedTwoNodeGrants->grants = null;
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($updatedTwoNodeGrants);

        // unset Grants
        $testPathNode->acl_node = null;
        $fmController->update($testPathNode);

        // move subfolder to new name
        $fmController->moveNodes(array($testPath . '/subfolder'), array($testPath . '/newsubfolder'))->getFirstRecord();
        // copy it back to old name
        $fmController->copyNodes(array($testPath . '/newsubfolder'), array($testPath . '/subfolder'))->getFirstRecord();

        // create file
        $tempPath = Tinebase_TempFile::getTempPath();
        $tempFileId = Tinebase_TempFile::getInstance()->createTempFile($tempPath);
        file_put_contents($tempPath, 'someData');
        $fmController->createNodes(array($testPath . '/newsubfolder/testFile'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE, array($tempFileId));

        // delete file
        $fmController->deleteNodes(array($testPath . '/newsubfolder/testFile'));

        // recreate file
        $tempPath = Tinebase_TempFile::getTempPath();
        $tempFileId = Tinebase_TempFile::getInstance()->createTempFile($tempPath);
        file_put_contents($tempPath, 'otherData');
        $fmController->createNodes(array($testPath . '/newsubfolder/testFile'),
            Tinebase_Model_Tree_FileObject::TYPE_FILE, array($tempFileId));

        //this is not supported for folders!
        //$fmController->delete($subFolderNode->getId());

        // delete first newsubfolder, then testpath
        $fmController->deleteNodes(array($testPath . '/newsubfolder', $testPath));

        $modifications = Tinebase_Timemachine_ModificationLog::getInstance()->getReplicationModificationsByInstanceSeq($instance_seq);
        $fmModifications = $modifications->filter('record_type', 'Filemanager_Model_Node');
        $fnModifications = $modifications->filter('record_type', 'Tinebase_Model_Tree_Node');
        $foModifications = $modifications->filter('record_type', 'Tinebase_Model_Tree_FileObject');
        $this->assertEquals($modifications->count(), $fmModifications->count() + $fnModifications->count() +
            $foModifications->count(), 'other changes than to Tinebase_Model_Tree_Node or Filemanager_Model_Node detected');

        // rollback
        Tinebase_TransactionManager::getInstance()->rollBack();
        Tinebase_TransactionManager::getInstance()->startTransaction(Tinebase_Core::getDb());

        // clean up file system... roll back only delete db entries!

        $notFound = false;
        try {
            $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath))->statpath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'roll back did not work...');

        // create first folder
        // create FileObject
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        // create FileNode
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        // update hash of FileObject
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        // update acl_node of FileNode
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath))->statpath);

        // create second folder
        // create FileObject
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        // create FileNode
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);

        // set grants
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $filesystem->clearStatCache();
        $testPathNode = $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        static::assertEquals($testPathNode->getId(), $testPathNode->acl_node, 'grants not set');
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($testPathNode);
        static::assertEquals($testNodeGrants->grants->count(), $testPathNode->grants->count());
        $oldGrants = $testNodeGrants->grants->getFirstRecord()->toArray();
        unset($oldGrants['id']);
        $newGrants = $testPathNode->grants->getFirstRecord()->toArray();
        unset($newGrants['id']);
        static::assertEquals($oldGrants, $newGrants);

        // update grants 1
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $filesystem->clearStatCache();
        $testPathNode = $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($testPathNode);
        static::assertEquals($updatedNodeGrants->grants->count(), $testPathNode->grants->count());
        $oldGrants = $updatedNodeGrants->grants->getFirstRecord()->toArray();
        unset($oldGrants['id']);
        $newGrants = $testPathNode->grants->getFirstRecord()->toArray();
        unset($newGrants['id']);
        static::assertEquals($oldGrants, $newGrants);

        // update grants 2
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $filesystem->clearStatCache();
        $testPathNode = $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        Tinebase_Tree_NodeGrants::getInstance()->getGrantsForRecord($testPathNode);
        static::assertEquals($updatedTwoNodeGrants->grants->count(), $testPathNode->grants->count());
        $oldGrants = $updatedTwoNodeGrants->grants->getFirstRecord()->toArray();
        unset($oldGrants['id']);
        $newGrants = $testPathNode->grants->getFirstRecord()->toArray();
        unset($newGrants['id']);
        static::assertEquals($oldGrants, $newGrants);

        // unset grants
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $filesystem->clearStatCache();
        $testPathNode = $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        static::assertNotEquals($testPathNode->getId(), $testPathNode->acl_node, 'grants still set');

        // move subfolder to new name
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/newsubfolder'))->statpath);
        $notFound = false;
        try {
            $filesystem->clearStatCache();
            $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'move did not work...');

        // copy it back to old name
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (copy)');
        $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/newsubfolder'))->statpath);
        $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);

        // create file
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (create file)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (create file)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (create file)');
        $path = Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/newsubfolder/testFile'));
        $node = $filesystem->stat($path->statpath);
        static::assertEquals('someData', $filesystem->getNodeContents($node->getId()));

        // delete file
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (delete 1)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (delete 2)');
        $path = Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/newsubfolder/testFile'));
        static::assertFalse($filesystem->fileExists($path->statpath));

        // recreate file
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (recreate)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (recreate)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (recreate)');
        $path = Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/newsubfolder/testFile'));
        $node = $filesystem->stat($path->statpath);
        static::assertEquals('otherData', $filesystem->getNodeContents($node->getId()));

        // delete new subfolder
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (delete subfolder)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (delete subfolder)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (delete subfolder)');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed (delete subfolder)');
        $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        $notFound = false;
        try {
            $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/newsubfolder'))->statpath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'delete did not work...');

        // delete new folder
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $mod = $modifications->getFirstRecord();
        $modifications->removeRecord($mod);
        $result = Tinebase_Timemachine_ModificationLog::getInstance()->applyReplicationModLogs(new Tinebase_Record_RecordSet('Tinebase_Model_ModificationLog', array($mod)));
        $this->assertTrue($result, 'applyReplicationModLogs failed');
        $notFound = false;
        try {
            $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath . '/subfolder'))->statpath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'delete did not work...');
        $notFound = false;
        try {
            $filesystem->stat(Tinebase_Model_Tree_Node_Path::createFromPath($fmController->addBasePath($testPath))->statpath);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $notFound = true;
        }
        $this->assertTrue($notFound, 'delete did not work...');
        
        $this->assertEquals(0, $modifications->count(), 'not all modifications processed');
    }
}
