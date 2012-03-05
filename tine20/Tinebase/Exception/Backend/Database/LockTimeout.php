<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Database Backend exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Backend_Database_LockTimeout extends Tinebase_Exception_Backend_Database
{
    /**
     * the constructor
     * 
     * @param string $_message
     * @param int $_code (default: 409 concurrency conflict)
     */
    public function __construct($_message, $_code = 409)
    {
        parent::__construct($_message, $_code);
    }
}
