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
    public const REFID = 'refId';

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
        if (true === $this->_persistent) {
            $blob = json_decode($blob, true);
        }
        if (!empty($model) && is_array($blob) && strpos($model, '_Model_') && class_exists($model)) {
            $newRecord = new $model($blob);
            $newRecord->runConvertToRecord();
            return $newRecord;
        }
        return $blob;
    }

    /**
     * @param $fieldValue
     * @return mixed
     */
    public function convertToData($record, $key, $fieldValue)
    {
        if ($fieldValue instanceof Tinebase_Record_Interface) {
            if (true === $this->_persistent) {
                $fieldValue->runConvertToData();
                $fieldValue = $fieldValue->toArray();
            } elseif (self::REFID === $this->_persistent) {
                $fieldValue = $fieldValue->getId();
            }
        }
        if (true === $this->_persistent) {
            $fieldValue = json_encode($fieldValue);
        } elseif (self::REFID === $this->_persistent && is_array($fieldValue) && isset($fieldValue['id'])) {
            $fieldValue = $fieldValue['id'];
        }

        return $fieldValue;
    }
}
