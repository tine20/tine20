<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * customfield value capsulation, for export only!
 *
 * @package     Tinebase
 * @subpackage  Backend
 */
class Tinebase_CustomField_Value
{
    protected $_value;
    protected $_definition;
    protected $_callback;

    public function __construct($value, $_definition, $callback)
    {
        $this->_value = $value;
        $this->_definition = $_definition;
        $this->_callback = $callback;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function __toString()
    {
        $type = strtolower($this->_definition['type']);
        switch ($type) {
            case 'record':
                $model = Tinebase_CustomField::getModelNameFromDefinition($this->_definition);
                $value = new $model($this->_value, true);
                break;
            case 'recordlist':
                $model = Tinebase_CustomField::getModelNameFromDefinition($this->_definition);
                $value = new Tinebase_Record_RecordSet($model, $this->_value, true);
                $value->sort(function(Tinebase_Record_Abstract $a, Tinebase_Record_Abstract $b) {
                    return strcmp($a->getTitle(), $b->getTitle());
                }, null, 'function');
                break;
            case 'keyfield':
                $keyfield = Tinebase_Config_KeyField::create($this->_definition->keyFieldConfig->value
                    ->toArray());
                $value = $keyfield->getTranslatedValue($this->_value);
                break;
            default:
                $value = $this->_value;
        }
        return ($this->_callback)($value);
    }
}