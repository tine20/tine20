<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * get path part delegator test class
 */
class Tinebase_Record_GetPathPartDelegator implements Tinebase_Record_Abstract_GetPathPartDelegatorInterface
{
    /**
     * @param Tinebase_Record_Interface $_record
     * @param Tinebase_Record_Interface|null $_parent
     * @param Tinebase_Record_Interface|null $_child
     * @return string
     */
    public function getPathPart(Tinebase_Record_Interface $_record, Tinebase_Record_Interface $_parent = null, Tinebase_Record_Interface $_child = null)
    {
        return 'shooShoo';
    }
}