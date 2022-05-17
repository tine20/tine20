<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander extends Tinebase_Record_Expander_Abstract
{
    protected $_dataToFetch = [];


    public function __construct($_model, $_expanderDefinition)
    {
        parent::__construct($_model, $_expanderDefinition, $this);
    }

    public function expand(Tinebase_Record_RecordSet $_records)
    {
        parent::expand($_records);

        while (!empty($this->_dataToFetch)) {
            $this->_fetchData();
        }
    }

    public static function expandRecord(Tinebase_Record_Interface $record): void
    {
        static::expandRecords(new Tinebase_Record_RecordSet(get_class($record), [$record]), $record::getConfiguration());
    }

    public static function expandRecords(Tinebase_Record_RecordSet $records, ?Tinebase_ModelConfiguration $mc = null): void
    {
        if (null === $mc) {
            if ($records->count() < 1) {
                return;
            }
            if (null === ($mc = $records->getFirstRecord()::getConfiguration())) {
                return;
            }
        }
        (new self($records->getRecordClassName(), $mc->jsonExpander))->expand($records);
    }

    protected function _fetchData()
    {
        $dataToFetch = $this->_dataToFetch;
        $this->_dataToFetch = [];
        ksort($dataToFetch);

        foreach ($dataToFetch as $controllerArray) {
            foreach ($controllerArray as $c => $dataRequestArray) {
                $currentDataRequest = null;
                /** @var Tinebase_Record_Expander_DataRequest $dataRequest */
                foreach ($dataRequestArray as $dataRequest) {
                    if (null === $currentDataRequest) {
                        $currentDataRequest = $dataRequest;
                    } else {
                        $currentDataRequest->merge($dataRequest);
                    }
                }

                $data = $currentDataRequest->getData();

                foreach ($dataRequestArray as $dataRequest) {
                    call_user_func($dataRequest->callback, $data);
                }
            }
        }
    }

    protected function _registerDataToFetch(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
        $key = $_dataRequest->getKey();
        if (!isset($this->_dataToFetch[$_dataRequest->prio])) {
            $this->_dataToFetch[$_dataRequest->prio] = [];
        }
        if (!isset($this->_dataToFetch[$_dataRequest->prio][$key])) {
            $this->_dataToFetch[$_dataRequest->prio][$key] = [];
        }
        $this->_dataToFetch[$_dataRequest->prio][$key][] = $_dataRequest;
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }
}
