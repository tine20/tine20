<?php declare(strict_types=1);
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

class Tinebase_Record_Expander_DynamicRecordProperty extends Tinebase_Record_Expander_Property
{
    protected $_mccCfg;
    protected $_expanderDefinition;

    public function __construct($_model, $_property, $_expanderDefinition, $_rootExpander,
                                $_prio = self::DATA_FETCH_PRIO_DEPENDENTRECORD)
    {
        $this->_mccCfg = $_expanderDefinition['fieldDefConfig'];
        $this->_expanderDefinition = $_expanderDefinition;

        parent::__construct($_model, $_property, [] /*$_expanderDefinition*/, $_rootExpander, $_prio);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_addRecordsToProcess($_records);
        $ids = [];
        /** @var Tinebase_Record_NewAbstract $record */
        foreach ($_records as $record) {
            $model = $record->{$this->_mccCfg[MCC::REF_MODEL_FIELD]};
            if (!isset($ids[$model])) {
                $ids[$model] = [];
            }
            $ids[$model][] = $record->getIdFromProperty($this->_property, false);
        }
        foreach ($ids as $model => $modelIds) {
            $modelIds = array_filter($modelIds);
            if (!empty($modelIds)) {
                $self = $this;
                $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest(
                    $this->_prio, Tinebase_Core::getApplicationInstance($model, '', true), $ids,
                    // workaround: [$this, '_setData'] doesn't work, even so it should!
                    function($_data) use($self) {$self->_setData($_data);}, $this->_getDeleted));
            } else {
                $this->_setData(new Tinebase_Record_RecordSet($model));
            }
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        $expandData = new Tinebase_Record_RecordSet($_data->getRecordClassName());
        // we will get called multiple times, for each model once, so we need to clean up recordsToProcess
        $removeRecords = [];

        /** @var Tinebase_Record_Abstract $record */
        foreach ($this->_recordsToProcess as $record) {
            if (null !== ($id = $record->getIdFromProperty($this->_property, false)) && false !== ($subRecord =
                    $_data->getById($id))) {
                $record->{$this->_property} = $subRecord;
                $expandData->addRecord($subRecord);
                $removeRecords[] = $record;
            } elseif ($record->{$this->_property} instanceof Tinebase_Record_Interface) {
                $expandData->addRecord($record->{$this->_property});
                $removeRecords[] = $record;
            }
        }

        foreach ($removeRecords as $record) {
            $this->_recordsToProcess->detach($record);
        }
        if ($expandData->count() === 0) {
            return;
        }

        $this->_subExpanders = [];
        $_model = $_data->getRecordClassName();
        if (isset($this->_expanderDefinition[self::EXPANDER_PROPERTIES])) {
            foreach ($this->_expanderDefinition[self::EXPANDER_PROPERTIES] as $prop => $definition) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, $definition, $prop,
                    $this->_rootExpander);
            }
        }
        if (isset($this->_expanderDefinition[self::EXPANDER_PROPERTY_CLASSES])) {
            foreach ($this->_expanderDefinition[self::EXPANDER_PROPERTY_CLASSES] as $propClass => $definition) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::createPropClass($_model, $definition,
                    $propClass, $this->_rootExpander);
            }
        }

        // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
        $this->expand($expandData);
    }
}
