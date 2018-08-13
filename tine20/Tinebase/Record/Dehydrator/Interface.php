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
 * Record Dehydrator interface
 *
 * @package     Tinebase
 * @subpackage  Record
 */
interface Tinebase_Record_Dehydrator_Interface
{
    /**
     * @param mixed $_data
     * @return Tinebase_Record_RecordSet|Tinebase_Record_Abstract
     *
    public function hydrate($_data);*/

    /**
     * @param Tinebase_Record_RecordSet|Tinebase_Record_Abstract $_data
     * @return string
     */
    public function dehydrate($_data);
}