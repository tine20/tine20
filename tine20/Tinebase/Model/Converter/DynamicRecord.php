<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Converter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019-2023 Metaways Infosystems GmbH (http://www.metaways.de)
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
    protected $_staticModel;

    public function __construct(?string $_property, $_persistent = false, ?string $_staticModel = null)
    {
        $this->_property = $_property;
        $this->_persistent = $_persistent;
        $this->_staticModel = $_staticModel;
    }

    /**
     * @param Tinebase_Record_Interface $record
     * @param $blob
     * @return mixed
     */
    public function convertToRecord($record, $key, $blob)
    {
        if ($blob instanceof Tinebase_Record_Interface) {
            $blob->runConvertToRecord();
            return $blob;
        }
        if ($this->_staticModel) {
            $model = $this->_staticModel;
        } else {
            $model = $record->{$this->_property};
        }
        if (true === $this->_persistent && is_string($blob)) {
            $blob = json_decode($blob, true);
        }
        if (!empty($model) && is_array($blob) && strpos($model, '_Model_') && class_exists($model)) {
            $newRecord = new $model($blob, $record->byPassFilters());
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
