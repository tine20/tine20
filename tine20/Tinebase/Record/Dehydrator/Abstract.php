<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Abstract Record Dehydrator providing facilities for dehydration
 *
 * only use methods exposed by the Tinebase_Record_Dehydrator_Interface interface
 * only use the Tinebase_Record_Hydration_Factory::createDehydrator method to create instances
 *
 * @package     Tinebase
 * @subpackage  Record
 */
abstract class Tinebase_Record_Dehydrator_Abstract implements Tinebase_Record_Dehydrator_Interface
{
    /** @var Tinebase_Record_Dehydrator_Strategy */
    protected $_strategy;

    protected $_model;

    /**
     * Tinebase_Record_Dehydrator_Abstract constructor
     *
     * @param string $_model
     * @param Tinebase_Record_Dehydrator_Strategy|null $_strategy
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function __construct($_model, Tinebase_Record_Dehydrator_Strategy $_strategy = null)
    {
        if (null === $_strategy) {
            $_strategy = new Tinebase_Record_Dehydrator_Strategy(static::$_type);
        }
        $_strategy->loadFromModel($_model);
        $this->_strategy = $_strategy;
        $this->_model = $_model;
    }

    /**
     * @param Tinebase_Record_RecordSet|Tinebase_Record_Interface $_data
     * @return string
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function dehydrate($_data)
    {
        if ($_data instanceof Tinebase_Record_RecordSet) {
            $result = [];
            /** @var Tinebase_Record_Interface $record */
            foreach ($_data as $record) {
                $result[] = $this->_processRecord($record);
            }
            $result = $this->_addMetaData($result);

        } elseif ($_data instanceof Tinebase_Record_Interface) {
            $result = $this->_processRecord($_data);
        } else {
            throw new Tinebase_Exception_InvalidArgument('_data is neither record nor recordset');
        }

        return $this->_dataToString($result);
    }

    /**
     * @param array $_data
     * @return array
     */
    protected function _addMetaData(array $_data)
    {
        return $_data;
    }

    /**
     * @param Tinebase_Record_Interface $_record
     * @return array
     */
    protected function _processRecord(Tinebase_Record_Interface $_record)
    {
        $data = $_record->getData();

        /** @var callable $task */
        foreach ($this->_strategy->getPreSubHTasks() as $task) {
            $task($data);
        }

        /** @var Tinebase_Record_Dehydrator_Abstract $hydrator */
        foreach ($this->_strategy->getSubHydrators() as $key => $hydrator) {
            if (isset($data[$key])) {
                if ($data[$key] instanceof Tinebase_Record_Interface) {
                    $data[$key] = $hydrator->_processRecord($data[$key]);
                } elseif ($data[$key] instanceof Tinebase_Record_RecordSet) {
                    $subData = [];
                    /** @var Tinebase_Record_Interface $subRecord */
                    foreach ($data[$key] as $subRecord) {
                        $subData[] = $hydrator->_processRecord($subRecord);
                    }
                    $data[$key] = $subData;
                } elseif (is_string($data[$key])) {
                    if ('' === $data[$key]) {
                        if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(
                            __METHOD__ . '::' . __LINE__ . ' property ' . $key . ' of ' . $this->_model .
                            ' is a empty string');
                        $data[$key] = null;
                    }
                } else {
                    if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' .
                        __LINE__ . ' property ' . $key . ' of ' . $this->_model . ' is neither Record[Set] nor string '
                        . print_r($data[$key], true));
                    $data[$key] = null;
                }
            }
            /** TODO maybe we want to unset the property if its null? probably not */
        }

        /** @var callable $task */
        foreach ($this->_strategy->getPreSubHTasks() as $task) {
            $task($data);
        }

        return $data;
    }

    /**
     * @param array $_data
     * @return string
     */
    abstract protected function _dataToString(array $_data);
}