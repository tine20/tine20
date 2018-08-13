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

abstract class Tinebase_Record_Expander_Property extends Tinebase_Record_Expander_Sub
{
    protected $_prio;
    protected $_property;

    public function __construct($_model, $_property, $_expanderDefinition, $_rootExpander,
            $_prio = Tinebase_Record_Expander_Abstract::DATA_FETCH_PRIO_DEPENDENTRECORD)
    {
        $this->_prio = $_prio;
        $this->_property = $_property;

        parent::__construct($_model, $_expanderDefinition, $_rootExpander);
    }
}