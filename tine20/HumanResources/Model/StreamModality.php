<?php
/**
 * class to hold Stream Modality data
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * class to hold Stream Modality data
 *
 * @package     HumanResources
 * @subpackage  Model
 */
class HumanResources_Model_StreamModality extends Tinebase_Record_NewAbstract
{
    const FLD_STREAM_ID         = 'stream_id';
    const FLD_START             = 'start';
    const FLD_END               = 'end';
    const FLD_TRACKING_START    = 'tracking_start';
    const FLD_TRACKING_END      = 'tracking_end';
    const FLD_INTERVAL          = 'interval';
    const FLD_NUM_INTERVAL      = 'num_interval';
    const FLD_HOURS_INTERVAL    = 'hours_interval';
    const FLD_CLOSED            = 'closed';
    const FLD_REPORTS           = 'reports';

    const INT_WEEKLY            = 'weekly';
    const INT_MONTHLY           = 'monthly';
    const INT_QUARTERLY         = 'quarterly';
    const INT_YEARLY            = 'yearly';

    const MODEL_NAME_PART       = 'StreamModality';
    const TABLE_NAME            = 'humanresources_streammodality';

    /**
     * holds the configuration object (must be declared in the concrete class)
     *
     * @var Tinebase_ModelConfiguration
     */
    protected static $_configurationObject = NULL;

    /**
     * Holds the model configuration (must be assigned in the concrete class)
     *
     * @var array
     */
    protected static $_modelConfiguration = [
        self::VERSION               => 1,
        self::HAS_RELATIONS         => true,
        self::COPY_RELATIONS        => false,
        self::HAS_NOTES             => true,
        self::MODLOG_ACTIVE         => true,
        self::HAS_ATTACHMENTS       => true,
        self::IS_DEPENDENT          => true,

        self::APP_NAME              => HumanResources_Config::APP_NAME,
        self::MODEL_NAME            => self::MODEL_NAME_PART,

        self::ASSOCIATIONS          => [
            ClassMetadataInfo::MANY_TO_ONE => [
                self::FLD_STREAM_ID     => [
                    'targetEntity'          => HumanResources_Model_Stream::class,
                    'fieldName'             => self::FLD_STREAM_ID,
                    'joinColumns'           => [[
                        'name' => self::FLD_STREAM_ID,
                        'referencedColumnName'  => 'id'
                    ]],
                ]
            ],
        ],

        self::TABLE                 => [
            self::NAME                  => self::TABLE_NAME,
            self::INDEXES               => [
                self::FLD_STREAM_ID         => [
                    self::COLUMNS               => [self::FLD_STREAM_ID]
                ],
            ],
        ],

        self::FIELDS                => [
            self::FLD_STREAM_ID         => [
                self::TYPE                  => self::TYPE_RECORD,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_Stream::MODEL_NAME_PART,
                ]
            ],
            self::FLD_START             => [
                self::LABEL                 => 'Start', // _('Start')
                self::TYPE                  => self::TYPE_DATE,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::CONVERTERS            => [
                    Tinebase_Model_Converter_Date::class,
                    HumanResources_Model_Converter_VirtualCalcPropEnd::class,
                ]
            ],
            self::FLD_END               => [
                self::LABEL                 => 'End', // _('End')
                self::TYPE                  => self::TYPE_VIRTUAL,
                self::CONFIG                => [
                    self::TYPE                  => self::TYPE_DATE,
                ]
            ],
            self::FLD_TRACKING_START    => [
                self::LABEL                 => 'Tracking Start', // _('Tracking Start')
                self::TYPE                  => self::TYPE_DATE,
                self::NULLABLE              => true,
            ],
            self::FLD_TRACKING_END      => [
                self::LABEL                 => 'Tracking End', // _('Tracking End')
                self::TYPE                  => self::TYPE_DATE,
                self::NULLABLE              => true,
            ],
            self::FLD_INTERVAL          => [
                self::LABEL                 => 'Interval', // _('Interval')
                self::TYPE                  => self::TYPE_KEY_FIELD,
                self::NAME                  => HumanResources_Config::STREAM_INTERVAL,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::CONVERTERS            => [
                    HumanResources_Model_Converter_VirtualCalcPropEnd::class,
                ]
            ],
            self::FLD_NUM_INTERVAL      => [
                self::LABEL                 => 'Number of Intervals', // _('Number of Intervals')
                self::TYPE                  => self::TYPE_INTEGER,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
                self::CONVERTERS            => [
                    HumanResources_Model_Converter_VirtualCalcPropEnd::class,
                ]
            ],
            self::FLD_HOURS_INTERVAL    => [
                self::LABEL                 => 'Hours per Interval', // _('Hours per Interval')
                self::TYPE                  => self::TYPE_INTEGER,
                self::VALIDATORS            => [Zend_Filter_Input::ALLOW_EMPTY => false, 'presence'=>'required'],
            ],
            self::FLD_CLOSED            => [
                self::LABEL                 => 'Closed', // _('Closed')
                self::TYPE                  => self::TYPE_BOOLEAN,
                self::DEFAULT_VAL           => 0,
            ],
            self::FLD_REPORTS           => [
                self::LABEL                 => 'Stream Modality Reports', // _('Stream Modality Reports')
                self::TYPE                  => self::TYPE_RECORDS,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_StreamModalReport::MODEL_NAME_PART,
                    self::RECORD_CLASS_NAME     => HumanResources_Model_StreamModalReport::class,
                    self::DEPENDENT_RECORDS     => true,
                    self::REF_ID_FIELD          => HumanResources_Model_StreamModalReport::FLD_STREAM_MODALITY_ID,
                    self::PAGING                => ['sort' => HumanResources_Model_StreamModalReport::FLD_INTERVAL]
                ]
            ],
        ],
    ];

    public function __set($_name, $_value)
    {
        parent::__set($_name, $_value);

        switch ($_name) {
            case self::FLD_INTERVAL:
            case self::FLD_NUM_INTERVAL:
            case self::FLD_START:
                $this->_data[self::FLD_END] = $this->_getEndByStart();
                break;
            case self::FLD_END:
                throw new Tinebase_Exception_InvalidArgument(self::FLD_END . ' is read only');
        }
    }

    protected function _getEndByStart()
    {
        if (null === ($start = $this->{self::FLD_START}) || !$this->{self::FLD_INTERVAL} || !$this
                ->{self::FLD_NUM_INTERVAL}) {
            return null;
        }
        if (! $start instanceof Tinebase_DateTime) {
            try {
                $start = new Tinebase_DateTime($start);
            } catch (Exception $e) {
                return null;
            }
        } else {
            $start = clone $start;
        }
        return $this->advanceByInterval($start, $this->{self::FLD_NUM_INTERVAL})->subDay(1);
    }

    public function generateMissingReportsEmpty()
    {
        if (null === ($lastReport = $this->{self::FLD_REPORTS}->getLastRecord())) {
            /** @var Tinebase_DateTime $start */
            $start = clone $this->{self::FLD_START};
            $interval = 0;
        } else {
            $start = clone $lastReport->{HumanResources_Model_StreamModalReport::FLD_END};
            $interval = (int)$lastReport->{HumanResources_Model_StreamModalReport::FLD_INTERVAL} + 1;
        }
        $now = Tinebase_DateTime::now();
        $end = $this->{self::FLD_END} ?: $this->advanceByInterval(clone $now)->subDay(1);
        while ($interval < $this->{self::FLD_NUM_INTERVAL} && $start->isEarlier($now) && $start->isEarlier($end)) {
            $newReport = new HumanResources_Model_StreamModalReport([
                HumanResources_Model_StreamModalReport::FLD_START    => clone $start,
                HumanResources_Model_StreamModalReport::FLD_END      => (clone ($this->advanceByInterval($start)))->subDay(1),
                HumanResources_Model_StreamModalReport::FLD_INTERVAL => $interval++
            ], true);
            if (!$newReport->{HumanResources_Model_StreamModalReport::FLD_END}->isEarlierOrEquals($end)) {
                $newReport->{HumanResources_Model_StreamModalReport::FLD_END} = clone $end;
            }
            $this->{self::FLD_REPORTS}->addRecord($newReport);
            $this->_isDirty = true;
        }
    }

    public function advanceByInterval(Tinebase_DateTime $date, $factor = 1)
    {
        switch ($this->{self::FLD_INTERVAL}) {
            case self::INT_WEEKLY:
                return $date->addWeek(1 * $factor);
            case self::INT_MONTHLY:
                return $date->addMonth(1 * $factor);
            case self::INT_QUARTERLY:
                return $date->addMonth(3 * $factor);
            case self::INT_YEARLY:
                return $date->addYear(1 * $factor);
            default:
                throw new Tinebase_Exception_UnexpectedValue('"' . $this->{self::FLD_INTERVAL} . '" is a unknown interval');
        }
    }
}
