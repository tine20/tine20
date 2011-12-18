<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * interface Tinebase_Record_IteratableInterface
 *
 * @package     Tinebase
 * @subpackage  Record
 */
interface Tinebase_Record_IteratableInterface
{
    /**
    * process iteration of records
    *
    * @param Tinebase_Record_RecordSet $_records
    */
    public function processIteration($_records);
}
