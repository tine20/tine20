<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase_Model_Converter_DynamicRecord
 *
 * Json DynamicRecord Converter
 *
 * @package     Tinebase
 * @subpackage  Converter
 */

class Tinebase_Model_Converter_DynamicRecord implements Tinebase_Model_Converter_Interface
{
    protected $_property;
    protected $_persistent;

    /**
     * Tinebase_Model_Converter_DynamicRecord constructor.
     * @param $_property
     */
    public function __construct($_property, $_persistent = false)
    {
        $this->_property = $_property;
        $this->_persistent = $_persistent;
    }

    /**
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        $model = $record->{$this->_property};
        if (true === $this->_persistent && !is_array($blob)) {
            $blob = json_decode($blob, true);
        }
        if (!empty($model) && is_array($blob) && strpos($model, '_Model_') && class_exists($model)) {
            $newRecord = new $model($blob);
            $newRecord->runConvertToRecord();
            return $newRecord;
        }
        return null;
    }

    /**
     * @param $fieldValue
     * @return mixed
     */
    public function convertToData($record, $key, $fieldValue)
    {
        $result = null;
        if ($fieldValue instanceof Tinebase_Record_Interface) {
            $fieldValue->runConvertToData();
            $result = $fieldValue->toArray();
        } elseif (is_array($fieldValue)) {
            $result = $fieldValue;
        }

        if ($this->_persistent) {
            $result = json_encode($result);
        }

        return $result;
    }
}
