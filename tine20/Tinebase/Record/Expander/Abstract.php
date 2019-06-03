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

abstract class Tinebase_Record_Expander_Abstract
{
    const EXPANDER_PROPERTIES = 'properties';
    const EXPANDER_PROPERTY_CLASSES = 'propertyClasses';
    const EXPANDER_REPLACE_GET_TITLE = 'replaceGetTitle';
    const GET_DELETED = 'getDeleted';

    const PROPERTY_CLASS_USER = 'user';

    const DATA_FETCH_PRIO_DEPENDENTRECORD = 100;
    const DATA_FETCH_PRIO_CONTAINER = 950;
    const DATA_FETCH_PRIO_USER = 1000;
    const DATA_FETCH_PRIO_RELATION = 800;
    const DATA_FETCH_PRIO_NOTES = 900;

    protected $_model;
    protected $_subExpanders = [];

    /**
     * @var Tinebase_Record_Expander
     */
    protected $_rootExpander;

    public function __construct($_model, $_expanderDefinition, Tinebase_Record_Expander $_rootExpander)
    {
        /** @var Tinebase_Record_Abstract $_model */
        $this->_model = $_model;
        $this->_rootExpander = $_rootExpander;
        if (isset($_expanderDefinition[self::EXPANDER_PROPERTIES])) {
            foreach ($_expanderDefinition[self::EXPANDER_PROPERTIES] as $prop => $definition) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::create($_model, $definition, $prop,
                    $this->_rootExpander);
            }
        }
        if (isset($_expanderDefinition[self::EXPANDER_PROPERTY_CLASSES])) {
            foreach ($_expanderDefinition[self::EXPANDER_PROPERTY_CLASSES] as $propClass => $definition) {
                $this->_subExpanders[] = Tinebase_Record_Expander_Factory::createPropClass($_model, $definition,
                    $propClass, $this->_rootExpander);
            }
        }
    }

    public function expand(Tinebase_Record_RecordSet $_records)
    {
        /** @var Tinebase_Record_Expander_Sub $expander */
        foreach ($this->_subExpanders as $expander) {
            /** @noinspection Annotator */
            $expander->_lookForDataToFetch($_records);
        }
    }

    protected abstract function _lookForDataToFetch(Tinebase_Record_RecordSet $_records);
    protected abstract function _setData(Tinebase_Record_RecordSet $_data);
    protected abstract function _registerDataToFetch(Tinebase_Record_Expander_DataRequest $_dataRequest);
}