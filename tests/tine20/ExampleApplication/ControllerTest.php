<?php
/**
* Tine 2.0 - http://www.tine20.org
*
* @package     ExampleApplication
* @subpackage  Test
* @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
* @copyright   Copyright (c) 2012-2019 Metaways Infosystems GmbH (http://www.metaways.de)
* @author      Stefanie Stamer <s.stamer@metaways.de>
*/

/**
* Test helper
*/
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
* Test class for ExampleApplication_JsonTest
*/
class ExampleApplication_ControllerTest extends ExampleApplication_TestCase
{
    public function testEventDispatching()
    {
        $observable = new ExampleApplication_Model_ExampleRecord(array('id' => 'testIdentifier'), true);

        $observer = new Tinebase_Model_PersistentObserver(array(
            'observable_model'      => 'ExampleApplication_Model_ExampleRecord',
            'observable_identifier' => 'testIdentifier',
            'observer_model'        => 'ExampleApplication_Model_ExampleRecord',
            'observer_identifier'   => 'exampleIdentifier',
            'observed_event'        => 'Tinebase_Event_Record_Update'
        ));

        $observerController = Tinebase_Record_PersistentObserver::getInstance();

        $observerController->addObserver($observer);

        ob_start();
        ob_clean();
        $event = new Tinebase_Event_Record_Update();
        $event->observable = $observable;
        $observerController->fireEvent($event);
        $result = ob_get_clean();

        $this->assertEquals('catched record update for observing id: exampleIdentifier', $result);
    }

    public function testDateFilter()
    {
        $exampleRecord = $this->_getExampleRecord();
        $exampleRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->create($exampleRecord);
        static::assertNull($exampleRecord->datetime);

        $result = ExampleApplication_Controller_ExampleRecord::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(ExampleApplication_Model_ExampleRecord::class,
            [['field' => 'datetime', 'operator' => 'before', 'value' => Tinebase_DateTime::now()]])
        );
        static::assertSame(1, $result->count());

        $result = ExampleApplication_Controller_ExampleRecord::getInstance()->search(
            Tinebase_Model_Filter_FilterGroup::getFilterForModel(ExampleApplication_Model_ExampleRecord::class,
                [['field' => 'datetime', 'operator' => 'after', 'value' => Tinebase_DateTime::now()]])
        );
        static::assertSame(1, $result->count());
    }

    public function testOneToOne()
    {
        $record = $this->_getExampleRecord();
        $record->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE} = [
            ExampleApplication_Model_OneToOne::FLD_NAME => 'unittest'
        ];

        $createdRecord = ExampleApplication_Controller_ExampleRecord::getInstance()->create($record);
        $expander = new Tinebase_Record_Expander(ExampleApplication_Model_ExampleRecord::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE => []
            ]
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(ExampleApplication_Model_ExampleRecord::class, [$createdRecord]));

        static::assertTrue($createdRecord->{ExampleApplication_Model_ExampleRecord::FLD_ONE_TO_ONE} instanceof
            ExampleApplication_Model_OneToOne, 'onetoone not instance of ' . ExampleApplication_Model_OneToOne::class);
    }
}
