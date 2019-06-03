<?php
/**
 * expands records based on provided definition
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

abstract class Tinebase_Record_Expander_Property extends Tinebase_Record_Expander_Sub
{
    protected $_prio;
    protected $_property;
    protected $_getDeleted = false;

    public function __construct($_model, $_property, $_expanderDefinition, $_rootExpander,
            $_prio = self::DATA_FETCH_PRIO_DEPENDENTRECORD)
    {
        $this->_prio = $_prio;
        $this->_property = $_property;

        if (isset($_expanderDefinition[self::GET_DELETED]) && true === $_expanderDefinition[self::GET_DELETED]) {
            $this->_getDeleted = true;
        }
        parent::__construct($_model, $_expanderDefinition, $_rootExpander);
    }
}