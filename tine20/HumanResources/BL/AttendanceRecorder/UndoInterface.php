<?php declare(strict_types=1);
/**
 *
 * @package     HumanResources
 * @subpackage  BL
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

interface HumanResources_BL_AttendanceRecorder_UndoInterface
{
    public function undo(Tinebase_Record_RecordSet $data): void;
}
