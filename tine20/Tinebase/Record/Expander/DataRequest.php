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
    protected $_getDeleted = false;
    protected $_merged = false;
    protected static $_dataCache = [];
    protected static $_deletedDataCache = [];

    public function __construct($prio, $controller, $ids, $callback, $getDeleted = false)
    {
        $this->prio = $prio;
        $this->controller = $controller;
        $this->ids = $ids;
        $this->callback = $callback;
        $this->_getDeleted = $getDeleted;
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
        $data = static::_getInstancesFromCache($this->controller->getModel(), $this->ids, $this->_getDeleted);

        if (!empty($this->ids)) {
            /** TODO make sure getMultiple doesnt do any resolving, customfields etc */
            /** TODO Tinebase_Container / Tinebase_User_Sql etc. do not have the propery mehtod signature! */
            if ($this->controller instanceof Tinebase_Controller_Record_Abstract) {
                $newRecords = $this->controller->getMultiple($this->ids, false, null, $this->_getDeleted);
            } else {
                $newRecords = $this->controller->getMultiple($this->ids);
            }
            static::_addInstancesToCache($this->controller->getModel(), $newRecords, $this->_getDeleted);
            $data->mergeById($newRecords);
        }

        return $data;
    }

    /**
     * @param string $_model
     * @param Tinebase_Record_RecordSet $_data
     * @param bool $_getDeleted
     */
    protected static function _addInstancesToCache($_model, Tinebase_Record_RecordSet $_data, $_getDeleted = false)
    {
        // always set both! we only check one below in \Tinebase_Record_Expander_DataRequest::_getInstancesFromCache
        if (!isset(static::$_dataCache[$_model])) {
            static::$_dataCache[$_model] = [];
        }
        if (!isset(static::$_deletedDataCache[$_model])) {
            static::$_deletedDataCache[$_model] = [];
        }
        $array = &static::$_dataCache[$_model];

        /** @var Tinebase_Record_Abstract $record */
        foreach ($_data as $record) {
            if ($_getDeleted && $record->is_deleted) {
                static::$_deletedDataCache[$_model][$record->getId()] = $record;
            } else {
                $array[$record->getId()] = $record;
            }
        }

    }
    /**
     * @param string $_model
     * @param array $_ids
     * @param bool $_getDeleted
     * @return Tinebase_Record_RecordSet
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_Record_NotAllowed
     */
    protected static function _getInstancesFromCache($_model, &$_ids, $_getDeleted = false)
    {
        $data = new Tinebase_Record_RecordSet($_model);
        // only one isset check, as we always set both arrays
        if (isset(static::$_dataCache[$_model])) {
            foreach ($_ids as $key => $id) {
                if (isset(static::$_dataCache[$_model][$id])) {
                    $data->addRecord(static::$_dataCache[$_model][$id]);
                    unset($_ids[$key]);
                } elseif ($_getDeleted && isset(static::$_deletedDataCache[$_model][$id])) {
                    $data->addRecord(static::$_deletedDataCache[$_model][$id]);
                    unset($_ids[$key]);
                }
            }
        }

        return $data;
    }

    public static function clearCache()
    {
        static::$_dataCache = [];
        static::$_deletedDataCache = [];
    }
}