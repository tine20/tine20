<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Generic BL exception
 *
 * @package     Tinebase
 * @subpackage  Exception
 */
class Tinebase_Exception_BL extends Tinebase_Exception
{
    /**
     * the constructor
     *
     * @param string $_message
     * @param int $_code
     */
    public function __construct($_message, $_code = 500)
    {
        parent::__construct($_message, $_code);
    }
}
