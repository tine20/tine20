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

class Tinebase_Record_Expander_DataRequest
{
    public $prio;
    /**
     * @var Tinebase_Controller_Record_Interface
     */
    public $controller;
    public $ids;
    public $callback;
    protected $_merged = false;
    protected static $_dataCache = [];

    public function __construct($prio, $controller, $ids, $callback)
    {
        $this->prio = $prio;
        $this->controller = $controller;
        $this->ids = $ids;
        $this->callback = $callback;
    }

    public function merge(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
        $this->ids = array_merge($this->ids, $_dataRequest->ids);
        $this->_merged = true;
    }

    public function getData()
    {
        if ($this->_merged) {
            $this->ids = array_unique($this->ids);
            $this->_merged = false;
        }

        // get instances from datacache
        $data = static::_getInstancesFromCache($this->controller->getModel(), $this->ids);

        if (!empty($this->ids)) {
            /** TODO make sure getMultiple doesnt do any resolving, customfields etc */
            $newRecords = $this->controller->getMultiple($this->ids);
            static::_addInstancesToCache($this->controller->getModel(), $newRecords);
            $data->mergeById($newRecords);
        }

        return $data;
    }

    protected static function _addInstancesToCache($_model, Tinebase_Record_RecordSet $_data)
    {
        if (!isset(static::$_dataCache[$_model])) {
            static::$_dataCache[$_model] = [];
        }
        $array = &static::$_dataCache[$_model];

        /** @var Tinebase_Record_Abstract $record */
        foreach ($_data as $record) {
            $array[$record->getId()] = $record;
        }

    }
    /**
     * @param string $_model
     * @param $_ids
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected static function _getInstancesFromCache($_model, &$_ids)
    {
        $data = new Tinebase_Record_RecordSet($_model);
        if (isset(static::$_dataCache[$_model])) {
            foreach ($_ids as $key => $id) {
                if (isset(static::$_dataCache[$_model][$id])) {
                    $data->addRecord(static::$_dataCache[$_model][$id]);
                    unset($_ids[$key]);
                }
            }
        }

        return $data;
    }

    public static function clearCache()
    {
        static::$_dataCache = [];
    }
}