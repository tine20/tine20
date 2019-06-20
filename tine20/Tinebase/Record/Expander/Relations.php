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

class Tinebase_Record_Expander_Relations extends Tinebase_Record_Expander_Property
{
    protected $_parentModel;

    public function __construct($_parentModel, $_model, $_property, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        $this->_parentModel = $_parentModel;
        parent::__construct($_model, $_property, $_expanderDefinition, $_rootExpander);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_addRecordsToProcess($_records);
        $ids = $_records->getArrayOfIds();
        if (!empty($ids)) {
            $self = $this;
            $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest_Relation(
                $this->_parentModel, Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND, $ids,
                // workaround: [$this, '_setRelationData'] doesn't work and in this case it shouldn't anyway
                function($_data) use($self) {$self->_setRelationData($_data);}));
        }
    }

    protected function _setRelationData($_data)
    {
        $expandData = new Tinebase_Record_RecordSet(Tinebase_Model_Relation::class);

        $modelBackend = $this->_parentModel . '#' . Tinebase_Model_Relation::DEFAULT_RECORD_BACKEND;
        if (isset($_data[$modelBackend]) && !empty($_data[$modelBackend])) {
            $data = $_data[$modelBackend];
            /** @var Tinebase_Record_Abstract $record */
            foreach ($this->_recordsToProcess as $record) {
                if (isset($data[$record->getId()])) {
                    $record->relations = $data[$record->getId()];
                    $expandData->mergeById($record->relations);
                }
            }
        }

        // TODO we should delay this expanding until the current run of \Tinebase_Record_Expander::_fetchData finished!
        $this->expand($expandData);
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }
}