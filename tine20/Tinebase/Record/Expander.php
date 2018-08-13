<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
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
        $cClass = get_class($_dataRequest->controller);
        if (!isset($this->_dataToFetch[$_dataRequest->prio])) {
            $this->_dataToFetch[$_dataRequest->prio] = [];
        }
        if (!isset($this->_dataToFetch[$_dataRequest->prio][$cClass])) {
            $this->_dataToFetch[$_dataRequest->prio][$cClass] = [];
        }
        $this->_dataToFetch[$_dataRequest->prio][$cClass][] = $_dataRequest;
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