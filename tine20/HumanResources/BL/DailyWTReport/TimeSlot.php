<?php
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_BL_DailyWTReport_TimeSlot
{
    /**
     * @var Tinebase_DateTime
     */
    public $start;

    /**
     * @var Tinebase_DateTime
     */
    public $end;

    /**
     * @var int
     */
    public $forcedBreakAtEnd = 0;

    /**
     * @var int
     */
    public $forcedBreakAtStart = 0;

    /**
     * @var string
     */
    public $timeAccountId;

    /**
     * @var string
     */
    public $timeSheetId;

    /**
     * @return int
     */
    public function durationInSec()
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    /**
     * we assume we work on the end always, so clone get "end" properties, $this keeps "start" properties
     *
     * @return HumanResources_BL_DailyWTReport_TimeSlot
     */
    public function getClone()
    {
        $clone = clone $this;
        $clone->forcedBreakAtStart = 0;
        $this->forcedBreakAtEnd = 0;
        return $clone;
    }
}