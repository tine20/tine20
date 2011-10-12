<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * Tinebase duplicate exception
 * 
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_Duplicate extends Tinebase_Exception_Data
{
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'data exception', $_code = 520)
    {
        parent::__construct($_message, $_code);
    }
}
