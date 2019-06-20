<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

abstract class Tinebase_Record_Expander_Sub extends Tinebase_Record_Expander_Abstract
{
    /**
     * @var SplObjectStorage
     */
    protected $_recordsToProcess;

    public function __construct($_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        $this->_recordsToProcess = new SplObjectStorage();

        parent::__construct($_model, $_expanderDefinition, $_rootExpander);
    }

    protected function _registerDataToFetch(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }

    protected function _addRecordsToProcess(Tinebase_Record_RecordSet $_records)
    {
        foreach ($_records as $record) {
            $this->_recordsToProcess->attach($record);
        }
    }
}