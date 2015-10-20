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
        $this->_testCalendar = $this->_getTestContainer('Calendar');
        $this->_uit = Tinebase_Controller_ScheduledImport::getInstance();
    }
    
    /**
     * Test create a scheduled import
     */
    public function createScheduledImport($source = 'http://localhost/test.ics')
    {
        $id = Tinebase_Record_Abstract::generateUID();
        $import = new Tinebase_Model_Import(
            array(
                'id'                => $id,
                'user_id'           => $this->_originalTestUser->getId(),
                'interval'          => Tinebase_Model_Import::INTERVAL_HOURLY,
                'model'             => Calendar_Controller::getInstance()->getDefaultModel(),
                'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Calendar')->getId(),
                'container_id'      => $this->_testCalendar->getId(),
                'sourcetype'        => Tinebase_Model_Import::SOURCETYPE_REMOTE,
                'source'            => $source,
                'options'           => json_encode(array(
                    'forceUpdateExisting' => TRUE,
                    'import_defintion' => NULL,
                    'plugin' => 'Calendar_Import_Ical'
                ))
            )
        );
        
        $record = $this->_uit->create($import);

        $this->assertEquals(Calendar_Controller::getInstance()->getDefaultModel(), $this->_uit->get($id)->model);

        return $record;
    }
    
    /**
     * testNextScheduledImport
     *
     * TODO this should run without the need for internet access to http://www.schulferien.org
     *      maybe we should put the file into the local filesystem
     */
    public function testNextScheduledImport()
    {
        $this->markTestSkipped('FIXME: use local ics file for this test / see TODO in doc block');

        $icsUri = 'http://www.schulferien.org/iCal/Ferien/icals/Ferien_Hamburg_2014.ics';
        $client = new Zend_Http_Client($icsUri);
        try {
            $client->request()->getBody();
        } catch (Exception $e) {
            $this->markTestSkipped('no access to ' . $icsUri);
        }

        $cc = Calendar_Controller_Event::getInstance();
        $filter = new Calendar_Model_EventFilter(array(
            array('field' => 'container_id', 'operator' => 'equals', 'value' => $this->_testCalendar->getId()),
        ));
        
        $all = $cc->search($filter);
        
        $this->assertEquals(0, $all->count());
        
        $now = Tinebase_DateTime::now()->subHour(1);
        
        $record = $this->createScheduledImport($icsUri);
        
        // assert setting timestamp to start value
        $this->assertEquals($now->format('YMDHi'), $record->timestamp->format('YMDHi'));
        
        $record = $this->_uit->runNextScheduledImport();

        $this->assertTrue($record !== null, 'import did not run');
        
        // assert updating timestamp after successful run
        $now->addHour(1);
        $this->assertEquals($now->format('YMDHi'), $record->timestamp->format('YMDHi'));
        
        $all = $cc->search($filter);
        $seq = $all->getFirstRecord()->seq;
        // assert all events have been imported
        $this->assertEquals(7, $all->count());
        
        // this must not be run, the interval is not exceed
        $this->_uit->runNextScheduledImport();
        $all = $cc->search($filter);
        $this->assertEquals($seq, $all->getFirstRecord()->seq);
        
        // setting manual timestamp to force run again
        $record->timestamp = $record->timestamp->subHour(1)->subSecond(1);
        
        $this->_uit->update($record);
        
        $this->_uit->runNextScheduledImport();
        $all = $cc->search($filter);
        $this->assertEquals(7, $all->count());
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
}
