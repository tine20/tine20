<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2014-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Michael Spahn <m.spahn@metaways.de>
 */

/**
 * Test class for Tinebase_ScheduledImport
 */
class Tinebase_ScheduledImportTest extends TestCase
{
    /**
     * unit in test
     *
     * @var Tinebase_Controller_ScheduledImport
     */
    protected $_uit = null;
    
    /**
     * @var Tinebase_Model_Container
     */
    protected $_testCalendar;
    
    /**
     * set up tests
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_testCalendar = $this->_getTestContainer('Calendar', Calendar_Model_Event::class);
        $this->_uit = Tinebase_Controller_ScheduledImport::getInstance();
    }
    
    /**
     * Test create a scheduled import
     */
    public function createScheduledImport($source = '')
    {
        $source = $source ?: __DIR__ . '/../Calendar/Import/files/lightning.ics';

        $import = new Tinebase_Model_Import(
            array(
                'user_id'           => $this->_originalTestUser->getId(),
                'interval'          => Tinebase_Model_Import::INTERVAL_HOURLY,
                'model'             => Calendar_Model_Event::class,
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'sourcetype'        => Tinebase_Model_Import::SOURCETYPE_REMOTE,
                'source'            => $source,
                'options'           => array(
                    'forceUpdateExisting'   => TRUE,
                    'importFileByScheduler' => TRUE,
                    'plugin'                => 'Calendar_Import_Ical',
                    'container_id'          => $this->_testCalendar->getId(),
                )
            ), true
        );
        
        $record = $this->_uit->create($import);

        $this->assertEquals(Calendar_Model_Event::class, $this->_uit->get($record->getId())->model);

        return $record;
    }
    
    /**
     * testNextScheduledImport
     */
    public function testNextScheduledImport()
    {
        $cc = Calendar_Controller_Event::getInstance();
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        ));

        $record = $this->createScheduledImport();
        $this->_uit->runNextScheduledImport();

        $all = $cc->search($filter);
        self::assertGreaterThan(0, count($all));
        $seq = $all->getFirstRecord()->seq;

        // assert all events have been imported
        $this->assertEquals(1, $all->count());
        
        // this must not be run, the interval is not exceed
        $this->_uit->runNextScheduledImport();
        $all = $cc->search($filter);
        $this->assertEquals($seq, $all->getFirstRecord()->seq);

        $record = $this->_uit->get($record->getId());
        $this->_uit->doScheduledImport($record);

        $all = $cc->search($filter);
        self::assertEquals(1, $all->count());
        self::assertGreaterThan($seq, $all->getFirstRecord()->seq);
    }

    /**
     * make import run again
     *
     * @param Tinebase_Model_Import|array $record
     */
    protected function _runAgain($record)
    {
        if (! $record instanceof Tinebase_Model_Import) {
            $record = new Tinebase_Model_Import($record);
        }

        // setting manual timestamp to force run again
        $record->timestamp = $record->timestamp->subHour(1)->subSecond(1);
        $this->_uit->update($record);
    }

    /**
     * @see 0011342: ics-scheduled import only imports 1 remote calendar
     */
    public function testMultipleScheduledImports()
    {
        // add two imports and check if they are both executed
        $import1 = $this->createScheduledImport();
        sleep(1); // make sure first one is found first
        $import2 = $this->createScheduledImport();

        $importRun1 = $this->_uit->runNextScheduledImport();
        $this->assertEquals($import1->getId(), $importRun1['id'], print_r($importRun1, true));
        $this->assertGreaterThanOrEqual($importRun1['timestamp'], Tinebase_DateTime::now()->toString());

        $importRun2 = $this->_uit->runNextScheduledImport();
        $this->assertEquals($import2->getId(), $importRun2['id'], 'second import not run: ' . print_r($importRun1, true));
        $this->assertGreaterThanOrEqual($importRun2['timestamp'], Tinebase_DateTime::now()->toString());
    }

    /**
     * @see 0011342: ics-scheduled import only imports 1 remote calendar
     */
    public function testNextScheduledImportFilter()
    {
        $record = $this->createScheduledImport();
        $record->timestamp = $record->timestamp->addHour(2);
        $this->_uit->update($record);

        $filter = $this->_uit->getScheduledImportFilter();
        $result = $this->_uit->search($filter);

        $this->assertEquals(0, count($result), 'no imports should be found: ' . print_r($result->toArray(), true));
    }

    /**
     * @see 0012082: deactivate failing scheduled imports
     */
    public function testDeactivatingImport()
    {
        // create invalid import
        $import1 = $this->createScheduledImport('/non/existing/file.ics');

        // run 5 (maxfailcount) times
        for ($i = 1; $i <= Tinebase_Controller_ScheduledImport::MAXFAILCOUNT; $i++) {
            $importRun = $this->_uit->runNextScheduledImport();
            $this->assertTrue(isset($importRun['failcount']), 'failcount should exist (import run ' . $i . ')');
            $this->assertEquals($i, $importRun['failcount'], 'failcount should increase: ' . print_r($importRun, true));
            $this->_runAgain($importRun);
        }

        // check if import is deactivated
        $importRun = $this->_uit->runNextScheduledImport();
        $this->assertTrue($importRun === true, 'import should not run: ' . print_r($importRun, true));
    }
}
