<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * @package     Tinebase
 * @subpackage  Record
 */
trait Tinebase_Record_AbstractTrait
{
    /**
     * should data be validated on the fly(false) or only on demand(true)
     *
     * TODO it must not be public!
     *
     * @var bool
     */
    public $bypassFilters = false;

    /**
     * stores if values got modified after loaded via constructor
     *
     * @var bool
     */
    protected $_isDirty = false;

    public function byPassFilters(): bool
    {
        return $this->bypassFilters;
    }

    /**
     * check if data got modified
     *
     * @return boolean
     */
    public function isDirty()
    {
        return $this->_isDirty;
    }

    /**
     * returns true if this record should be replicated
     *
     * @return boolean
     */
    public function isReplicable()
    {
        return false;
    }

    /**
     * @param Tinebase_Record_RecordSet $_recordSet
     * @param Tinebase_Record_RecordSetDiff $_recordSetDiff
     * @return bool
     */
    public static function applyRecordSetDiff(Tinebase_Record_RecordSet $_recordSet, Tinebase_Record_RecordSetDiff $_recordSetDiff)
    {
        return false;
    }

    public static function resolveRelationId(string $id, $record = null)
    {
        return $id;
    }

    public static function touchOnRelated(Tinebase_Model_Relation $relation): bool
    {
        return false;
    }
}