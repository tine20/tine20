<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Record
 */
class Tinebase_Record_RecordTest extends Tinebase_Record_AbstractTest
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
        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Record_RecordTest');
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
        // initial object
        $this->objects['TestRecord'] = new Tinebase_Record_DummyRecord(array(), true);
        $this->objects['TestRecord']->setFromArray(array('id'=>'2', 'test_2'=>NULL, ), NULL);
        
        // date management
        $date = new Tinebase_DateTime();
        $this->objects['TestRecord']->date_single = clone($date);
        $this->objects['TestRecord']->date_multiple = array($date);
        // bypass filters
        $this->objects['TestRecordBypassFilters'] = new Tinebase_Record_DummyRecord(array('id'=>'7', 'test_2'=>'STRING'), true) ;
    
        $this->expectFailure['TestRecord']['testSetId'][] = array('2','3');
        $this->expectFailure['TestRecord']['testSetId'][] = array('30000000','3000000000000000000000000000');
        $this->expectSuccess['TestRecord']['testSetId'][] = array('2','2');
        
        $this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','3');
        $this->expectFailure['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('30000000','3000000000000000000000000000');
        $this->expectSuccess['TestRecordBypassFilters']['testSetIdBypassFilters'][] = array('2','2');
        
        $this->expectSuccess['TestRecord']['testSetFromArray'][] = array(array('test_1'=>'2', 'test_2'=>NULL), 'test_1');
        $this->expectFailure['TestRecord']['testSetFromArrayException'][] = array('Tinebase_Exception_Record_Validation', array('test_2' => 'string'), );
        $this->expectFailure['TestRecord']['testSetTimezoneException'][] = array('Tinebase_Exception_Record_NotAllowed', 'UTC', );
        
        $dummy = array(
                    'id'=>2, 
                    'test_2'=>'',
                    'date_single' => $date->get(Tinebase_Record_Abstract::ISO8601LONG), 
                    'date_multiple'=> array($date->get(Tinebase_Record_Abstract::ISO8601LONG)));
        
        $this->expectSuccess['TestRecord']['testToArray'][] = array($dummy);
        
        
        $this->expectSuccess['TestRecord']['__set'][] = array('test_3', 4 );
        
        $this->expectSuccess['TestRecord']['__get'][] = array('test_3', 4 );
        
        $this->expectSuccess['TestRecord']['test__isset'][] = array('id');
        
        $this->expectFailure['TestRecord']['test__isset'][] = array('string');
        
        
        $this->expectFailure['TestRecord']['test__setException'][] = array( 'Tinebase_Exception_UnexpectedValue', 'test_100',);
        $this->expectFailure['TestRecord']['test__getException'][] = array( 'Tinebase_Exception_UnexpectedValue', 'test_100',);
        
        
        $this->expectFailure['TestRecord']['testOffsetUnset'][] = array( 'Tinebase_Exception_Record_NotAllowed', 'test_2',);
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
     * testDiff
     */
    public function testDiff()
    {
        $record1 = new Tinebase_Record_DummyRecord(array(
            'string' => 'test',
            'test_1' => 25,
            'test_2' => 99,
            'date_single' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
        ), true);
        
        $record2 = clone $record1;
        $record2->string = 'anders';
        $record2->test_1 = 26;
        $diff = $record1->diff($record2)->diff;
        $this->assertEquals(2, count($diff), 'expected difference in string & test_1: ' . print_r($diff, TRUE));
        $this->assertEquals('anders', $diff['string']);
        $this->assertEquals(26, $diff['test_1']);

        $record2 = clone $record1;
        $record2->date_single = clone $record1->date_single;
        $record2->date_single = $record2->date_single->addDay(1);
        $diff = $record1->diff($record2)->diff;
        $this->assertEquals(1, count($diff));
        $this->assertTrue(array_key_exists('date_single', $diff));
    }

    /**
     * test clone
     */
    public function testClone()
    {
        $record = new Tinebase_Record_DummyRecord(array(
            'string' => 'test',
            'date_single' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
        ), true);
        
        $clone = clone $record;
        $clone->date_single->addDay(1);
        
        $this->assertFalse($record->date_single == $clone->date_single, 'date in record and clone is equal');
    }
    
    /**
     * test if equal
     */
    public function testIsEqual()
    {
        $record1 = new Tinebase_Record_DummyRecord(array(
            'string' => 'test',
            'test_1' => 25,
            'test_2' => 99,
            'date_single' => Tinebase_DateTime::now()->get(Tinebase_Record_Abstract::ISO8601LONG)
        ), true);
        $record2 = clone $record1;
        $this->assertTrue($record1->isEqual($record2), 'records are equal');
        
        $record2->string = 'anders';
        $this->assertFalse($record1->isEqual($record2), 'records are differnet');
        
        $this->assertTrue($record1->isEqual($record2, array('string')), 'records are different, but omited');
        
    }
    
    /**
     * test record translation
     */
    public function testTranslate()
    {
        $oldLocale = Tinebase_Core::get(Tinebase_Core::LOCALE);
        Tinebase_Core::set(Tinebase_Core::LOCALE, new Zend_Locale('de'));

        $record = new Tinebase_Record_DummyRecord(array(
            'string' => 'test',
            'leadstate' => 'waiting for feedback',
        ), true);
        
        $record->translate();
        
        $this->assertEquals('Wartet auf Feedback', $record->leadstate);
        Tinebase_Core::set(Tinebase_Core::LOCALE, $oldLocale);
    }
    
    /**
     * Test standard record
     */
    public function testRecord()
    {
        $record = new Tinebase_Record_DummyRecord();
        $this->assertEquals(true, $record->isValid());
    }
    
    /**
     * Test invalid record bypassing filters
     */
    public function testInvalidRecord()
    {
        $record = new Tinebase_Record_DummyRecord(array('string' => '123'), true);
        $this->assertEquals(false, $record->isValid());
    }
    
    /**
     * Test invalid record provoking exception
     */
    public function testRecordException()
    {
        $this->setExpectedException('Tinebase_Exception_Record_Validation');
        $record = new Tinebase_Record_DummyRecord(array('string' => '123'));
    }
    
    /**
     * Test date conversion
     */
    public function testDateConversion()
    {
        $record = new Tinebase_Record_DummyRecord(array('date_single' => '2008-12-12 00:00:00'));
        $this->assertEquals('2008-12-12 00:00:00', $record->date_single->get(Tinebase_Record_Abstract::ISO8601LONG));
    }
    
    /**
     * Test date to string conversion
     */
    public function testDateStringConversion()
    {
        $record = new Tinebase_Record_DummyRecord(array('date_stringtrim' => '2008-12-12 00:00:00'));
        $this->assertEquals('string', gettype($record->date_stringtrim), 'implicit conversion of Tinebase_DateTime to string failed');
    }
    
    /**
     * Test string trim
     */
    public function testStringTrim()
    {
        $record = new Tinebase_Record_DummyRecord(array('stringtrim' => '   teststring   '));
        $this->assertEquals('teststring', $record->stringtrim, 'string trim filter failed');
    }
    
    /**
     * Test inserting data into en empty record
     *
     */
    public function testInsertData()
    {
        $record = new Tinebase_Record_DummyRecord(array(), true);
        $record->string = '123';
        $record->bypassFilters = false;
        $record->isValid();
    }
    
    /**
     * Test set ID
     */
    public function testSetId()
    {
        $record = new Tinebase_Record_DummyRecord(array('string' => 'test'));
        $test_id = '1';
        $record->setId($test_id);
        $this->assertEquals($test_id, $record['id']);
    }
    
    /**
     * Test get ID
     */
    public function testGetId()
    {
        $test_id = '1';
        $record = new Tinebase_Record_DummyRecord(array('id' => $test_id, 'string' => 'test'));
        $this->assertEquals($test_id, $record->getId());
    }
    
    /**
     * Test getApplication
     */
    public function testGetApplication()
    {
        $record = new Tinebase_Record_DummyRecord();
        $this->assertEquals($record->getApplication(), 'Crm');
    }
    
    /**
     * Test has
     */
    public function testHas()
    {
        $record = new Tinebase_Record_DummyRecord(array(
            'test_4' => 'test'
        ), true);
        $this->assertEquals((bool)1, (bool)$record->has('test_4'));
    }
    
    /**
    * test is valid / test InArray validator
    */
    public function testIsValid()
    {
        // should throw an exception
        try {
            $recordToTest = new Tinebase_Record_DummyRecord(array(
                'id'      => 256,
                'string'  => '',
            ));
            $this->fail('should throw validation exeption');
        } catch (Tinebase_Exception_Record_Validation $terv) {
            $this->assertTrue(TRUE);
        }
        
        $recordToTest = new Tinebase_Record_DummyRecord(array(
            'id'      => 256,
            'inarray' => 'value3',
        ), TRUE);
        $this->assertFalse($recordToTest->isValid(), 'InArray validator should detect invalid value!');
        
        $recordToTest->inarray = 'value1';
        $this->assertTrue($recordToTest->isValid());
    }
    
    /**
     * test auto modeling of record
     */
    public function testAutoRecord()
    {
        $addressbook = Tinebase_Application::getInstance()->getApplicationByName('Addressbook');
        $pref = new Tinebase_Model_Preference(array(
            'account_type' => Tinebase_Acl_Rights::ACCOUNT_TYPE_ANYONE,
            'type' => Tinebase_Model_Preference::TYPE_USER,
            'name' => 'autotest',
            'application_id' => $addressbook->getId(),
            'value' => 'BLABLUB'
        ));
        // must be null
        $this->assertNull($pref::getResolveForeignIdFields());
        
        $date = new Tinebase_DateTime();
        $date->setDate(2010, 11, 12);
        $date->setTime(0,0);
        
        $auto = new Tinebase_Record_AutoRecord();
        $auto->text = 'Hello World';
        $auto->date = $date;
        $crtime = clone $date;
        $crtime->addYear(2);
        $auto->creation_time = $crtime; 
        
        $this->assertEquals(15, count($auto->getFields()));
        
        $autoArray = $auto->toArray();
        
        $this->assertEquals('2010-11-12 00:00:00', $autoArray['date']);
        $this->assertEquals('2012-11-12 00:00:00', $autoArray['creation_time']);
        
        // must still be null
        $this->assertNull($pref::getResolveForeignIdFields());
    }
}
