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

class Tinebase_Record_Expander_VirtualRelation extends Tinebase_Record_Expander_Property
{
    protected $_cfg;

    public function __construct($_cfg, $_model, $_property, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        $this->_cfg = $_cfg;
        parent::__construct($_model, $_property, $_expanderDefinition, $_rootExpander);
    }

    protected function _lookForDataToFetch(Tinebase_Record_RecordSet $_records)
    {
        $this->_addRecordsToProcess($_records);
        $self = $this;
        $this->_rootExpander->_registerDataToFetch(new Tinebase_Record_Expander_DataRequest_VirtualRelation(
            // workaround: [$this, '_setRelationData'] doesn't work and in this case it shouldn't anyway
            function($_data) use($self, $_records) {$self->_setData($_records);}));
    }

    protected function _setData(Tinebase_Record_RecordSet $_data)
    {
        foreach ($_data as $record) {
            $record->{$this->_property} = new Tinebase_Record_RecordSet($this->_model, $record->relations
                ->filter('related_model', $this->_cfg[MCC::RECORD_CLASS_NAME])->filter('type', $this->_cfg[MCC::TYPE])
                ->related_record);
        }
    }
}
