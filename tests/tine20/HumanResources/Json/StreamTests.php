<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use HumanResources_Model_Stream as Stream;
use HumanResources_Model_StreamModality as StreamModality;

/**
 * Test class for HumanResources Json FE
 *
 * @property HumanResources_Frontend_Json $_uit
 */
class HumanResources_Json_StreamTests extends HumanResources_TestCase
{
    protected function setUp()
    {
        $this->_uit = new HumanResources_Frontend_Json();

        parent::setUp();
    }

    public function testVirtualProperties()
    {
        $ta1 = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => Tinebase_Record_Abstract::generateUID()
        ]));
        $ta2 = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => Tinebase_Record_Abstract::generateUID()
        ]));

        $stream = $this->_uit->saveStream([
            Stream::FLD_TYPE => 'velocity stream',
            Stream::FLD_TITLE => 'my unittest stream',
            Stream::FLD_STREAM_MODALITIES => [
                [
                    StreamModality::FLD_START => Tinebase_DateTime::now()->toString('Y-m-d'),
                    StreamModality::FLD_INTERVAL => StreamModality::INT_WEEKLY,
                    StreamModality::FLD_NUM_INTERVAL => 10,
                    StreamModality::FLD_HOURS_INTERVAL => 16,
                ], [
                    StreamModality::FLD_START => Tinebase_DateTime::now()->subYear(1)->toString('Y-m-d'),
                    StreamModality::FLD_INTERVAL => StreamModality::INT_WEEKLY,
                    StreamModality::FLD_NUM_INTERVAL => 10,
                    StreamModality::FLD_HOURS_INTERVAL => 16,
                ]
            ],
            Stream::FLD_RESPONSIBLES => [
                Tinebase_Core::getUser()->contact_id,
                Addressbook_Controller_Contact::getInstance()->get($this->_personas['sclever']->contact_id)->toArray(false)
            ],
            Stream::FLD_TIME_ACCOUNTS => [
                $ta1->getId(),
                $ta2->getId()
            ]
        ]);

        static::assertArrayHasKey(Stream::FLD_STREAM_MODALITIES, $stream);
        $streamModalities = $stream[Stream::FLD_STREAM_MODALITIES];
        static::assertTrue(is_array($streamModalities));
        static::assertCount(2, $streamModalities);

        static::assertArrayHasKey(Stream::FLD_RESPONSIBLES, $stream);
        $responsibles = $stream[Stream::FLD_RESPONSIBLES];
        static::assertTrue(is_array($responsibles));
        static::assertCount(2, $responsibles);

        static::assertArrayHasKey(Stream::FLD_TIME_ACCOUNTS, $stream);
        $timeaccounts = $stream[Stream::FLD_TIME_ACCOUNTS];
        static::assertTrue(is_array($timeaccounts));
        static::assertCount(2, $timeaccounts);

        $ta1Arr = (new Timetracker_Frontend_Json())->getTimeaccount($ta1->getId());
        static::assertArrayHasKey('stream', $ta1Arr);
        static::assertArrayHasKey('id', $ta1Arr['stream']);
        static::assertSame($stream['id'], $ta1Arr['stream']['id']);
    }

    public function testReportGeneration()
    {
        $ta1 = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => Tinebase_Record_Abstract::generateUID()
        ]));
        $ta2 = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => Tinebase_Record_Abstract::generateUID()
        ]));

        $ts1 = Timetracker_Controller_Timesheet::getInstance()->create(new Timetracker_Model_Timesheet([
            'timeaccount_id' => $ta1->getId(),
            'account_id' => Tinebase_Core::getUser()->getId(),
            'start_date' => Tinebase_DateTime::now()->subMonth(1)->addDay(6)->toString('Y-m-d'),
            'duration' => 30 * 60,
            'description' => 'unittest'
        ]));

        $ts2 = Timetracker_Controller_Timesheet::getInstance()->create(new Timetracker_Model_Timesheet([
            'timeaccount_id' => $ta2->getId(),
            'account_id' => Tinebase_Core::getUser()->getId(),
            'start_date' => Tinebase_DateTime::now()->subMonth(1)->addDay(7)->toString('Y-m-d'),
            'duration' => 30 * 60,
            'description' => 'unittest'
        ]));

        $stream = $this->_uit->saveStream([
            Stream::FLD_TYPE => 'velocity stream',
            Stream::FLD_TITLE => 'my unittest stream',
            Stream::FLD_STREAM_MODALITIES => [
                [
                    StreamModality::FLD_START => Tinebase_DateTime::now()->subMonth(1)->toString('Y-m-d'),
                    StreamModality::FLD_INTERVAL => StreamModality::INT_WEEKLY,
                    StreamModality::FLD_NUM_INTERVAL => 10,
                    StreamModality::FLD_HOURS_INTERVAL => 16 * 60 * 60,
                ]
            ],
            Stream::FLD_TIME_ACCOUNTS => [
                $ta1->getId(),
                $ta2->getId()
            ]
        ]);

        $stream = $this->_uit->generateStreamReport($stream['id']);
/*
        $stream->{Stream::FLD_STREAM_MODALITIES} = null;
        $expander->expand(new Tinebase_Record_RecordSet(Stream::class, [$stream]));

        $streamModality = $stream->{Stream::FLD_STREAM_MODALITIES}->getFirstRecord();
        $reports = $streamModality->{StreamModality::FLD_REPORTS};
        $ts1Duration = $ts1->duration;
        $ts2Duration = $ts2->duration;
        $should = $streamModality->{StreamModality::FLD_HOURS_INTERVAL};

        static::assertSame('0', $reports->getFirstRecord()->{StreamModalReport::FLD_OVERFLOW_IN});
        static::assertEquals($ts1Duration, $reports->getFirstRecord()->{StreamModalReport::FLD_IS});
        static::assertEquals($ts1Duration - $should, $reports->getFirstRecord()->{StreamModalReport::FLD_OVERFLOW_OUT});

        static::assertEquals($ts1Duration - $should, $reports->getByIndex(1)->{StreamModalReport::FLD_OVERFLOW_IN});
        static::assertEquals($ts2Duration, $reports->getByIndex(1)->{StreamModalReport::FLD_IS});
        static::assertEquals($ts1Duration + $ts2Duration - (2*$should), $reports->getByIndex(1)->{StreamModalReport::FLD_OVERFLOW_OUT});

        static::assertEquals($ts1Duration + $ts2Duration - (2*$should), $reports->getByIndex(2)->{StreamModalReport::FLD_OVERFLOW_IN});
        static::assertEquals(0, $reports->getByIndex(2)->{StreamModalReport::FLD_IS});
        static::assertEquals($ts1Duration + $ts2Duration - (3*$should), $reports->getByIndex(2)->{StreamModalReport::FLD_OVERFLOW_OUT});
*/    }
}
