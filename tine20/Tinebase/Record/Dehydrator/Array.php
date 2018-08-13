<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Array Record Dehydrator, converting records into a plain array
 *
 * only use methods exposed by the Tinebase_Record_Dehydrator_Interface interface
 * only use the Tinebase_Record_Hydration_Factory::createDehydrator method to create instances
 *
 * @package     Tinebase
 * @subpackage  Record
 */
class Tinebase_Record_Dehydrator_Array extends Tinebase_Record_Dehydrator_Abstract
{
    protected static $_type = Tinebase_Record_Hydration_Factory::TYPE_ARRAY;

    /**
     * @param array $_data
     * @return array
     */
    protected function _dataToString(array $_data)
    {
        return $_data;
    }
}