<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * interface for getPathPart() decorator
 *
 * @package     Tinebase
 * @subpackage  Record
 */
interface Tinebase_Record_Abstract_GetPathPartDelegatorInterface
{
    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface|null $_parent
     * @param Tinebase_Record_Interface|null $_child
     * @return string
     */
    public function getPathPart(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_parent = null, Tinebase_Record_Interface $_child = null);
}