<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 *
 */

/**
 * Database Backend exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Backend_Database extends Tinebase_Exception_Backend
{
    /**
     * the constructor
     * 
     * @param string $_message
     * @param int $_code (default: 503 Service Unavailable)
     */
    public function __construct($_message, $_code = 503)
    {
        parent::__construct($_message, $_code);
    }
}
