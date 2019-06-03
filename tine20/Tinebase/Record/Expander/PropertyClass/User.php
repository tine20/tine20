<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

class Tinebase_Record_Expander_PropertyClass_User extends Tinebase_Record_Expander_Sub
{
    protected $_propertiesToProcess;

    public function __construct($_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        if (null === ($mc = $_model::getConfiguration())) {
            throw new Tinebase_Exception_InvalidArgument($_model . ' doesn\'t have a modelconfig');
        }

        $model = null;
        $this->_propertiesToProcess = [];
        foreach ($mc->recordFields as $property => $recordField) {
            if (isset($recordField['type']) && 'user' === $recordField['type']) {
                $this->_propertiesToProcess[] = $property;
                if (null === $model) {
                    $model = $mc->getFieldModel($property);
                }
            }
        }

        parent::__construct($model, $_expanderDefinition, $_rootExpander);
    }
    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_addRecordsToProcess($_records);
        $ids = [];
        foreach ($this->_propertiesToProcess as $property) {
            // ??? TODO think about this? getIdFromProperty? we never know which state the records are in?
            // TODO if there is already an object, we don't want it from the database again!
            // FIXME guess we have to add an optional parameter to getIdFromProperty
            $ids = array_merge($ids, array_filter($_records->getIdFromProperty($property)));
        }
        $ids = array_unique($ids);
        if (!empty($ids)) {
            $self = $this;
            $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest(
                self::DATA_FETCH_PRIO_USER, Tinebase_Core::getApplicationInstance($this->_model), $ids,
                // workaround: [$this, '_setData'] doesn't work, even so it should!
                function($_data) use($self) {$self->_setData($_data);}));
        }
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        $expandData = new Tinebase_Record_RecordSet($_data->getRecordClassName());

        foreach ($this->_propertiesToProcess as $property) {
            /** @var Tinebase_Record_Abstract $record */
            foreach ($this->_recordsToProcess as $record) {
                // ??? TODO think about this? getIdFromProperty? we never know which state the records are in?
                // TODO if there is already an object, we don't want it from the database again!
                // FIXME guess we have to add an optional parameter to getIdFromProperty
                if (null !== ($id = $record->getIdFromProperty($property)) && false !== ($subRecord =
                        $_data->getById($id))) {
                    $record->{$property} = $subRecord;
                    $expandData->addRecord($subRecord);
                }
            }
        }

        // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
        $this->expand($expandData);
    }
}