<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

class Tinebase_Record_Expander_VirtualFunction extends Tinebase_Record_Expander_Property
{
    protected $_cfg;

    public function __construct($_cfg, $_model, $_property, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        $this->_cfg = $_cfg;
        parent::__construct($_model, $_property, $_expanderDefinition, $_rootExpander);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        // code copied from \Tinebase_Convert_Json::_resolveVirtualFields ... legacy...
        if (is_array($this->_cfg[MCC::FUNCTION])) {
            if (count($this->_cfg[MCC::FUNCTION]) > 1) { // static method call
                $class  = $this->_cfg[MCC::FUNCTION][0];
                $method = $this->_cfg[MCC::FUNCTION][1];
                $class::$method($_records);

            } else { // use key as classname and value as method name
                $ks = array_keys($this->_cfg[MCC::FUNCTION]);
                $class  = array_pop($ks);
                $vs = array_values($this->_cfg[MCC::FUNCTION]);
                $method = array_pop($vs);
                $class = $class::getInstance();
                $class->$method($_records);
            }
            // if no array has been given, this should be a function name
        } else {
            $resolveFunction = $this->_cfg[MCC::FUNCTION];
            $resolveFunction($_records);
        }

    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    { /* nothing to do here*/ }
}
