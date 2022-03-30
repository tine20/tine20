<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

use Tinebase_ModelConfiguration_Const as MCC;

class Tinebase_Record_Expander_JsonRefIdStorageProperty extends Tinebase_Record_Expander_Property
{
    protected $fieldDef;

    public function __construct($_fieldDef, $_model, $_property, $_expanderDefinition, $_rootExpander,
                                $_prio = self::DATA_FETCH_PRIO_DEPENDENTRECORD)
    {
        $this->fieldDef = $_fieldDef;
        parent::__construct($_model, $_property, $_expanderDefinition, $_rootExpander, $_prio);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        foreach ($_records as $record) {
            $data = $record->{$this->_property};
            if (is_array($data) && count($data) > 0) {
                /** @var Tinebase_Controller_Record_Abstract $ctrl */
                $ctrl = $this->fieldDef[MCC::CONFIG]['controllerClassName'];
                $record->{$this->_property} = $ctrl::getInstance()->getMultiple($data);
                // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
                // ?? really, this too? ... probably not
                $this->expand($record->{$this->_property});
            }
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        throw new Tinebase_Exception_NotImplemented(__METHOD__ . ' must never be called');
    }
}
