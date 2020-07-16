<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     HumanResources
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Test class for HumanResources Controller
 *
 * @property HumanResources_Controller_Stream $_uit
 */
class HumanResources_Controller_StreamTests extends HumanResources_TestCase
{
    protected function setUp()
    {
        $this->_uit = HumanResources_Controller_Stream::getInstance();

        parent::setUp();
    }

    public function testStreamModalityOverlap()
    {
        static::setExpectedExceptionRegExp(Tinebase_Exception_UnexpectedValue::class, '/^modalities may not overlap$/');

        $this->_uit->create(new HumanResources_Model_Stream([
            HumanResources_Model_Stream::FLD_TYPE => 'velocity stream',
            HumanResources_Model_Stream::FLD_TITLE => 'my unittest stream',
            HumanResources_Model_Stream::FLD_STREAM_MODALITIES => [
                [
                    HumanResources_Model_StreamModality::FLD_START => Tinebase_DateTime::now()->subMonth(6)->toString('Y-m-d'),
                    HumanResources_Model_StreamModality::FLD_INTERVAL => HumanResources_Model_StreamModality::INT_WEEKLY,
                    HumanResources_Model_StreamModality::FLD_NUM_INTERVAL => 10,
                    HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL => 16,
                ], [
                    HumanResources_Model_StreamModality::FLD_START => Tinebase_DateTime::now()->subYear(1)->toString('Y-m-d'),
                    HumanResources_Model_StreamModality::FLD_INTERVAL => HumanResources_Model_StreamModality::INT_WEEKLY,
                    HumanResources_Model_StreamModality::FLD_NUM_INTERVAL => 30,
                    HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL => 16,
                ]
            ]
        ]));
    }

    public function testVirtualProperties()
    {
        $ta1 = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => Tinebase_Record_Abstract::generateUID()
        ]));
        $ta2 = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => Tinebase_Record_Abstract::generateUID()
        ]));

        $stream = $this->_uit->create(new HumanResources_Model_Stream([
            HumanResources_Model_Stream::FLD_TYPE => 'velocity stream',
            HumanResources_Model_Stream::FLD_TITLE => 'my unittest stream',
            HumanResources_Model_Stream::FLD_STREAM_MODALITIES => [
                [
                    HumanResources_Model_StreamModality::FLD_START => Tinebase_DateTime::now()->toString('Y-m-d'),
                    HumanResources_Model_StreamModality::FLD_INTERVAL => HumanResources_Model_StreamModality::INT_WEEKLY,
                    HumanResources_Model_StreamModality::FLD_NUM_INTERVAL => 10,
                    HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL => 16,
                ], [
                    HumanResources_Model_StreamModality::FLD_START => Tinebase_DateTime::now()->subYear(1)->toString('Y-m-d'),
                    HumanResources_Model_StreamModality::FLD_INTERVAL => HumanResources_Model_StreamModality::INT_WEEKLY,
                    HumanResources_Model_StreamModality::FLD_NUM_INTERVAL => 10,
                    HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL => 16,
                ]
            ],
            HumanResources_Model_Stream::FLD_RESPONSIBLES => [
                Tinebase_Core::getUser()->contact_id,
                Addressbook_Controller_Contact::getInstance()->get($this->_personas['sclever']->contact_id)->toArray(false)
            ],
            HumanResources_Model_Stream::FLD_TIME_ACCOUNTS => [
                $ta1->getId(),
                $ta2->getId()
            ]
        ]));

        $streamModalities = $stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES};
        static::assertTrue($streamModalities instanceof Tinebase_Record_RecordSet);
        static::assertSame(2, $streamModalities->count());

        $responsibles = $stream->{HumanResources_Model_Stream::FLD_RESPONSIBLES};
        static::assertTrue($responsibles instanceof Tinebase_Record_RecordSet);
        static::assertSame(2, $responsibles->count());

        $timeaccounts = $stream->{HumanResources_Model_Stream::FLD_TIME_ACCOUNTS};
        static::assertTrue($timeaccounts instanceof Tinebase_Record_RecordSet);
        static::assertSame(2, $timeaccounts->count());

        $ta1Arr = (new Timetracker_Frontend_Json())->getTimeaccount($ta1->getId());
        static::assertArrayHasKey('stream', $ta1Arr);
        static::assertArrayHasKey('id', $ta1Arr['stream']);
        static::assertSame($stream->getId(), $ta1Arr['stream']['id']);
    }

    public function testTARelationConstraint()
    {
        $ta = Timetracker_Controller_Timeaccount::getInstance()->create(new Timetracker_Model_Timeaccount([
            'title' => Tinebase_Record_Abstract::generateUID()
        ]));

        $this->_uit->create(new HumanResources_Model_Stream([
            HumanResources_Model_Stream::FLD_TYPE => 'velocity stream',
            HumanResources_Model_Stream::FLD_TITLE => 'my unittest stream1',
            HumanResources_Model_Stream::FLD_STREAM_MODALITIES => [
                [
                    HumanResources_Model_StreamModality::FLD_START => Tinebase_DateTime::now()->toString('Y-m-d'),
                    HumanResources_Model_StreamModality::FLD_INTERVAL => HumanResources_Model_StreamModality::INT_WEEKLY,
                    HumanResources_Model_StreamModality::FLD_NUM_INTERVAL => 10,
                    HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL => 16,
                ]
            ],
            HumanResources_Model_Stream::FLD_TIME_ACCOUNTS => [
                $ta->getId()
            ]
        ]));

        static::setExpectedExceptionRegExp(Tinebase_Exception_InvalidRelationConstraints::class,
            '/^You tried to create a relation which is forbidden by the constraints config of one of the models\.$/');

        $this->_uit->create(new HumanResources_Model_Stream([
            HumanResources_Model_Stream::FLD_TYPE => 'velocity stream',
            HumanResources_Model_Stream::FLD_TITLE => 'my unittest stream2',
            HumanResources_Model_Stream::FLD_STREAM_MODALITIES => [
                [
                    HumanResources_Model_StreamModality::FLD_START => Tinebase_DateTime::now()->toString('Y-m-d'),
                    HumanResources_Model_StreamModality::FLD_INTERVAL => HumanResources_Model_StreamModality::INT_WEEKLY,
                    HumanResources_Model_StreamModality::FLD_NUM_INTERVAL => 10,
                    HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL => 16,
                ]
            ],
            HumanResources_Model_Stream::FLD_TIME_ACCOUNTS => [
                $ta->getId()
            ]
        ]));
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

        $stream = $this->_uit->create(new HumanResources_Model_Stream([
            HumanResources_Model_Stream::FLD_TYPE => 'velocity stream',
            HumanResources_Model_Stream::FLD_TITLE => 'my unittest stream',
            HumanResources_Model_Stream::FLD_STREAM_MODALITIES => [
                [
                    HumanResources_Model_StreamModality::FLD_START => Tinebase_DateTime::now()->subMonth(1)->toString('Y-m-d'),
                    HumanResources_Model_StreamModality::FLD_INTERVAL => HumanResources_Model_StreamModality::INT_WEEKLY,
                    HumanResources_Model_StreamModality::FLD_NUM_INTERVAL => 10,
                    HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL => 16 * 60 * 60,
                ]
            ],
            HumanResources_Model_Stream::FLD_TIME_ACCOUNTS => [
                $ta1->getId(),
                $ta2->getId()
            ]
        ]));

        $expander = new Tinebase_Record_Expander(HumanResources_Model_Stream::class, [
            Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                HumanResources_Model_Stream::FLD_STREAM_MODALITIES  => [
                    Tinebase_Record_Expander::EXPANDER_PROPERTIES => [
                        HumanResources_Model_StreamModality::FLD_REPORTS => []
                    ],
                ],
            ],
        ]);
        $expander->expand(new Tinebase_Record_RecordSet(HumanResources_Model_Stream::class, [$stream]));
        $this->_uit->createReports($stream);

        $stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES} = null;
        $expander->expand(new Tinebase_Record_RecordSet(HumanResources_Model_Stream::class, [$stream]));

        $streamModality = $stream->{HumanResources_Model_Stream::FLD_STREAM_MODALITIES}->getFirstRecord();
        $reports = $streamModality->{HumanResources_Model_StreamModality::FLD_REPORTS};
        $ts1Duration = $ts1->duration;
        $ts2Duration = $ts2->duration;
        $should = $streamModality->{HumanResources_Model_StreamModality::FLD_HOURS_INTERVAL};

        static::assertSame('0', $reports->getFirstRecord()->{HumanResources_Model_StreamModalReport::FLD_OVERFLOW_IN});
        static::assertEquals($ts1Duration, $reports->getFirstRecord()->{HumanResources_Model_StreamModalReport::FLD_IS});
        static::assertEquals($ts1Duration - $should, $reports->getFirstRecord()->{HumanResources_Model_StreamModalReport::FLD_OVERFLOW_OUT});

        static::assertEquals($ts1Duration - $should, $reports->getByIndex(1)->{HumanResources_Model_StreamModalReport::FLD_OVERFLOW_IN});
        static::assertEquals($ts2Duration, $reports->getByIndex(1)->{HumanResources_Model_StreamModalReport::FLD_IS});
        static::assertEquals($ts1Duration + $ts2Duration - (2*$should), $reports->getByIndex(1)->{HumanResources_Model_StreamModalReport::FLD_OVERFLOW_OUT});

        static::assertEquals($ts1Duration + $ts2Duration - (2*$should), $reports->getByIndex(2)->{HumanResources_Model_StreamModalReport::FLD_OVERFLOW_IN});
        static::assertEquals(0, $reports->getByIndex(2)->{HumanResources_Model_StreamModalReport::FLD_IS});
        static::assertEquals($ts1Duration + $ts2Duration - (3*$should), $reports->getByIndex(2)->{HumanResources_Model_StreamModalReport::FLD_OVERFLOW_OUT});
    }
}
