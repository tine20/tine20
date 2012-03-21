<?php
/**
 * @package     Calendar
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        extend new Data exception and add iMIP record to exception if available
 */

/**
 * iMIP (RFC 6047) exception
 *
 * @package Calendar
 * @subpackage  Exception
 */
class Calendar_Exception_iMIP extends Exception
{
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'generic iMIP exception', $_code = 920)
    {
        parent::__construct($_message, $_code);
    }
}
