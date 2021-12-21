<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2016-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * record still in use, are you sure you want to delete it?
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Record_StillInUse extends Tinebase_Exception_ProgramFlow
{
    /**
     * the constructor
     *
     * @param string $_message
     * @param int $_code (default: 703)
     */
    public function __construct($_message, $_code = 703)
    {
        parent::__construct($_message, $_code);
    }
}
