<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Unauthorized exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Unauthorized extends Tinebase_Exception_ProgramFlow
{
    /**
     * the constructor
     * 
     * @param string $_message
     * @param int $_code
     */
    public function __construct($_message, $_code = 401)
    {
        parent::__construct($_message, $_code);
    }
}
