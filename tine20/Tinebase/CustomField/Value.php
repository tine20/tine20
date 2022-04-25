<?php
/**
 * Tine 2.0
 *
 * customfield value encapsulation, for export only!
 *
 * @package     Tinebase
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */
class Tinebase_CustomField_Value
{
    protected $_value;
    protected $_definition;
    protected $_callback;
    protected $_application_id;

    public function __construct($value, $_definition, $callback, $application_id)
    {
        $this->_value = $value;
        $this->_definition = $_definition;
        $this->_callback = $callback;
        $this->_application_id = $application_id;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     * @throws Tinebase_Exception_NotFound
     * @throws Zend_Json_Exception
     */
    public function __toString()
    {
        if (empty($this->_value)) {
            return ($this->_callback)($this->_value);
        }

        $type = strtolower($this->_definition['type']);
        try {
            switch ($type) {
                case 'record':
                    $model = Tinebase_CustomField::getModelNameFromDefinition($this->_definition);
                    if (is_array($this->_value)) {
                        $value = new $model($this->_value, true);
                    } else {
                        $controller = Tinebase_Core::getApplicationInstance($model);
                        $value = $controller->get($this->_value, null, false);
                    }
                    break;
                case 'recordlist':
                    $model = Tinebase_CustomField::getModelNameFromDefinition($this->_definition);
                    if (is_array($this->_value) && (empty($this->_value) || is_array(current($this->_value)))) {
                        $value = new Tinebase_Record_RecordSet($model, $this->_value, true);
                    } else {
                        $controller = Tinebase_Core::getApplicationInstance($model);
                        // TODO test this! does the FE send a json encoded list here?!? it got decoded earlier already?
                        // TODO probably the decode can be removed?
                        $value = $controller->getMultiple(Tinebase_Helper::jsonDecode($this->_value));
                    }
                    $value->sort(function(Tinebase_Record_Interface $a, Tinebase_Record_Interface $b) {
                        return strcmp($a->getTitle(), $b->getTitle());
                    }, null, 'function');
                    break;
                case 'keyfield':
                    $keyfield = Tinebase_Config_KeyField::create($this->_definition->keyFieldConfig->value
                        ->toArray(), ['appName' => Tinebase_Application::getInstance()
                        ->getApplicationById($this->_application_id)->name]);
                    $value = $keyfield->getTranslatedValue($this->_value);
                    break;
                default:
                    $value = $this->_value;
            }
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            }
            $value = $this->_value;
        }

        return ($this->_callback)($value);
    }
}
