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
 * class to hold a list of records
 *
 * records are held as a unsorted set with a autoasigned numeric index.
 * NOTE: the index of an record is _not_ related to the record and/or its identifier!
 *
 * @package     Tinebase
 * @subpackage  Record
 *
 */
class Tinebase_Record_RecordSetFast extends Tinebase_Record_RecordSet
{
    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct($_className, array &$_data)
    {
        if (! class_exists($_className)) {
            throw new Tinebase_Exception_InvalidArgument('Class ' . $_className . ' does not exist');
        }
        $this->_recordClass = $_className;

        foreach ($_data as &$data) {
            /** @var Tinebase_Record_Interface $toAdd */
            $toAdd = new $this->_recordClass(null, true);
            $toAdd->hydrateFromBackend($data);
            $this->addRecord($toAdd);
        }
    }
}