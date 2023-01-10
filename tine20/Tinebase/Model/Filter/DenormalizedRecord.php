<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2023 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use Tinebase_ModelConfiguration_Const as TMC;
use Tinebase_Model_Filter_Abstract as TMFA;

/**
 * Tinebase_Model_Filter_DenormalizedRecord
 *
 * @package     Tinebase
 * @subpackage  Filter
 */
class Tinebase_Model_Filter_DenormalizedRecord extends Tinebase_Model_Filter_ForeignRecords
{
    protected $_refIdField;
    protected $_originalOperator;
    protected $_originalValue;

    /**
     * set options
     *
     * @param array $_options
     * @throws Tinebase_Exception_InvalidArgument
     */
    protected function _setOptions(array $_options)
    {
        $_options['refIdField'] = Tinebase_ModelConfiguration_Const::FLD_ORIGINAL_ID;

        $model = $_options['model'];
        $property = $_options['property'];
        /** @var Tinebase_ModelConfiguration $mc */
        $mc = $model::getConfiguration();
        if (!isset($mc->getFields()[$property])) {
            throw new Tinebase_Exception_Record_DefinitionFailure($model . ' has no property ' . $property);
        }
        $propCfg = $mc->getFields()[$property];
        if (TMC::TYPE_RECORD !== $propCfg[TMC::TYPE]) {
            throw new Tinebase_Exception_Record_DefinitionFailure($model . '.' . $property . ' is not of type record');
        }
        if (!isset($propCfg[TMC::CONFIG]) || !isset($propCfg[TMC::CONFIG][TMC::REF_ID_FIELD]) ||
                !isset($propCfg[TMC::CONFIG][TMC::RECORD_CLASS_NAME]) ||
                !isset($propCfg[TMC::CONFIG][TMC::CONTROLLER_CLASS_NAME])) {
            throw new Tinebase_Exception_Record_DefinitionFailure($model . '.' . $property . ' config not compatible with denoramlized record filter');
        }
        $_options['filtergroup'] = $propCfg[TMC::CONFIG][TMC::RECORD_CLASS_NAME];
        $_options['controller'] = $propCfg[TMC::CONFIG][TMC::CONTROLLER_CLASS_NAME];
        $this->_refIdField = $propCfg[TMC::CONFIG][TMC::REF_ID_FIELD];

        parent::_setOptions($_options);

    }

    public function setOperator($_operator)
    {
        $this->_originalOperator = $_operator;
        parent::setOperator('definedBy?condition=and&setOperator=oneOf');
    }

    public function setValue($_value)
    {
        $this->_originalValue = $_value;
        $value = [
            [TMFA::FIELD => $this->_refIdField, TMFA::OPERATOR => $this->_originalOperator, TMFA::VALUE => $_value]
        ];
        parent::setValue($value);
    }

    public function toArray($_valueToJson = false)
    {
        $this->_orgOperator = $this->_originalOperator;
        return parent::toArray($_valueToJson);
    }
}