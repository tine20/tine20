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

    /**
     * Tinebase_Model_Converter_DynamicRecord constructor.
     * @param $_property
     */
    public function __construct($_property)
    {
        $this->_property = $_property;
    }

    /**
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        $model = $record->{$this->_property};
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
        if ($fieldValue instanceof Tinebase_Record_Interface) {
            $fieldValue->runConvertToData();
            return $fieldValue->toArray();
        } elseif (is_array($fieldValue)) {
            return $fieldValue;
        }
        return null;
    }
}