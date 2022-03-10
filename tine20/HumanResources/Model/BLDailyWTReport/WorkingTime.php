<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * virtual DailyWTReport WorkingTime Model
 *
 * @package     Tinebase
 * @subpackage  Model
 *
 * @property string                                                         classname
 * @property Tinebase_Record_Interface|Tinebase_BL_ElementConfigInterface   configRecord
 */
class HumanResources_Model_BLDailyWTReport_WorkingTime extends Tinebase_Record_NewAbstract
{
    const MODEL_NAME_PART = 'BLDailyWTReport_WorkingTime';
    const FLDS_WAGE_TYPE = 'wage_type';
    const FLDS_DURATION = 'duration';
    const FLDS_START = 'start';
    const FLDS_END = 'end';

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
        self::APP_NAME      => HumanResources_Config::APP_NAME,
        self::MODEL_NAME    => self::MODEL_NAME_PART,
        self::RECORD_NAME   => 'Working time', // _('Working time')
        self::RECORDS_NAME  => 'Working times', // _('Working times')

        self::TITLE_PROPERTY=> "{# {{start - sorting! #}{{ duration |date('H:i', 'GMT')}}{% if start and end %} ({{ start |date('H:i')}} - {{ end |date('H:i')}}){% endif %} - {{wage_type.name}}",

        self::FIELDS        => [
            self::FLDS_WAGE_TYPE        => [
                self::TYPE                  => self::TYPE_RECORD,
                self::CONFIG                => [
                    self::APP_NAME              => HumanResources_Config::APP_NAME,
                    self::MODEL_NAME            => HumanResources_Model_WageType::MODEL_NAME_PART,
                ],
            ],
            self::FLDS_DURATION         => [
                self::TYPE                  => self::TYPE_INTEGER,
            ],
            self::FLDS_START            => [
                self::TYPE                  => self::TYPE_TIME,
            ],
            self::FLDS_END              => [
                self::TYPE                  => self::TYPE_TIME,
            ]
        ],
    ];

    /**
     * @param Tinebase_Record_RecordSet $_recordSetOne
     * @param Tinebase_Record_RecordSet $_recordSetTwo
     * @param ?Tinebase_Record_DiffContext $context
     * @return null|Tinebase_Record_RecordSetDiff
     */
    public static function recordSetDiff(Tinebase_Record_RecordSet $_recordSetOne, Tinebase_Record_RecordSet $_recordSetTwo, ?Tinebase_Record_DiffContext $context = null)
    {
        $_recordSetOne->sort('start');
        $_recordSetTwo->sort('start');

        $iteratorOne = $_recordSetOne->getIterator();
        $iteratorTwo = $_recordSetTwo->getIterator();

        $removed = new Tinebase_Record_RecordSet(static::class);
        $added = new Tinebase_Record_RecordSet(static::class);

        do {
            /** @var Tinebase_Record_Abstract $currentOne */
            $currentOne = $iteratorOne->current();
            $currentTwo = $iteratorTwo->current();

            if ($currentOne && $currentTwo) {
                if ($currentOne->diff($currentTwo, ['id'])->isEmpty()) {
                    // equal
                    $iteratorOne->next();
                    $iteratorTwo->next();
                } else {
                    if ($currentTwo->end <= $currentOne->start) {
                        $added->addRecord(clone $currentTwo);
                        $iteratorTwo->next();
                    } elseif ($currentTwo->start >= $currentOne->end) {
                        $removed->addRecord(clone $currentOne);
                        $iteratorOne->next();
                    } else {
                        $removed->addRecord(clone $currentOne);
                        $iteratorOne->next();
                        $added->addRecord(clone $currentTwo);
                        $iteratorTwo->next();
                    }
                }
            } elseif ($currentOne) {
                $removed->addRecord(clone $currentOne);
                $iteratorOne->next();
            } elseif ($currentTwo) {
                $added->addRecord(clone $currentTwo);
                $iteratorTwo->next();
            } else {
                break;
            }
        } while (true);

        return new Tinebase_Record_RecordSetDiff([
            'model'    => static::class,
            'added'    => $added,
            'removed'  => $removed,
            'modified' => new Tinebase_Record_RecordSet('Tinebase_Record_Diff'),
        ]);
    }
}
