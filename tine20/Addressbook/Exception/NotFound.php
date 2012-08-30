<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */

/**
 * NotFound exception
 * 
 * @package     Addressbook
 * @subpackage  Exception
 */
class Addressbook_Exception_NotFound extends Addressbook_Exception
{
    /**
     * constructor
     * 
     * @param string $_message
     * @param integer $_code
     */
    public function __construct($_message, $_code = 404)
    {
        parent::__construct($_message, $_code);
    }
}
