<?php
/**
 * holds information about the requested data
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander_DataRequest_Relation extends Tinebase_Record_Expander_DataRequest
{
    protected $_model;
    protected $_backend;

    public function __construct($_model, $_backend, $_ids, $_callback)
    {
        parent::__construct(Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_RELATION,
            Tinebase_Relations::getInstance(), [], $_callback);
        $this->_model = $_model;
        $this->_backend = $_backend;
        $this->_ids[$_model][$_backend] = $_ids;
    }

    public function merge(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
        $ids = $_dataRequest->ids[$_dataRequest->_model][$_dataRequest->_backend];
        if (!isset($this->_ids[$_dataRequest->_model])) {
            $this->_ids[$_dataRequest->_model] = [$_dataRequest->_backend => $ids];
        } elseif (!isset($this->_ids[$_dataRequest->_model][$_dataRequest->_backend])) {
            $this->_ids[$_dataRequest->_model][$_dataRequest->_backend] = $ids;
        } else {
            $this->_ids[$_dataRequest->_model][$_dataRequest->_backend] = array_merge(
                $this->_ids[$_dataRequest->_model][$_dataRequest->_backend], $ids);
            $this->_merged = true;
        }
    }

    public function getData()
    {
        $data = [];
        $relationController = Tinebase_Relations::getInstance();

        foreach ($this->_ids as $model => $backendArray) {
            foreach ($backendArray as $backend => $ids) {
                if ($this->_merged) {
                    $ids = array_unique($ids);
                }
                $modelBackend = $model . '#' . $backend;
                $data[$modelBackend] = [];

                if (isset(static::$_dataCache[Tinebase_Model_Relation::class]) && isset(
                        static::$_dataCache[Tinebase_Model_Relation::class][$modelBackend])) {
                    $cache = static::$_dataCache[Tinebase_Model_Relation::class][$modelBackend];
                    foreach ($ids as $offset => $id) {
                        if (isset($cache[$id])) {
                            $data[$modelBackend][$id] = $cache[$id];
                            unset($ids[$offset]);
                        }
                    }
                }

                if (!empty($ids)) {
                    $newRecords = $relationController->getMultipleRelations($model, $backend, $ids);
                    if (!empty($newRecords)) {
                        if (!isset(static::$_dataCache[Tinebase_Model_Relation::class])) {
                            static::$_dataCache[Tinebase_Model_Relation::class] = [$modelBackend => []];
                        }
                        if (!isset(static::$_dataCache[Tinebase_Model_Relation::class][$modelBackend])) {
                            static::$_dataCache[Tinebase_Model_Relation::class][$modelBackend] = [];
                        }
                        foreach ($newRecords as $offset => $newData) {
                            $data[$modelBackend][$ids[$offset]] = $newData;
                            static::$_dataCache[Tinebase_Model_Relation::class][$modelBackend][$ids[$offset]] = $newData;
                        }
                    }
                }
            }
        }
        $this->_merged = false;

        return $data;
    }
}