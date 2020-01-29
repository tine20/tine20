<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Http Status exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Expressive_HttpStatus extends Tinebase_Exception_ProgramFlow
{
    /**
     * the constructor
     *
     * @param string $_message
     * @param int $_code
     */
    public function __construct($_message, $_code)
    {
        parent::__construct($_message, $_code);

        $this->_logToSentry = false;
    }
}
