<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 *
 * @package     HumanResources
 * @subpackage  BL
 */
class HumanResources_BL_AttendanceRecorder_Data implements Tinebase_BL_DataInterface
{
    /** @var Tinebase_Record_RecordSet $data */
    public $data;
    protected $processedSequence;

    public function __construct(Tinebase_Record_RecordSet $data)
    {
        $this->data = $data;
        $this->processedSequence = 0;
    }

    public function getProcessedSequence(): int
    {
        return $this->processedSequence;
    }

    public function setProcessedSequence(int $seq): void
    {
        $this->processedSequence = $seq;
    }
}
