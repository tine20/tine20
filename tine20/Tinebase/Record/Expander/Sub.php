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

abstract class Tinebase_Record_Expander_Sub extends Tinebase_Record_Expander_Abstract
{
    protected $_recordsToProcess;

    protected function _registerDataToFetch(Tinebase_Record_Expander_DataRequest $_dataRequest)
    {
        throw new Tinebase_Exception_NotImplemented('do not call this method on ' . self::class);
    }
}