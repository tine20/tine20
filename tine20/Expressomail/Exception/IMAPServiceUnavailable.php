<?php
/**
 * Tine 2.0
 * 
 * @package     Expressomail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 */

/**
 * Service Unavailable Exception
 * 
 * @package     Expressomail
 * @subpackage  Exception
 */
class Expressomail_Exception_IMAPServiceUnavailable extends Expressomail_Exception_IMAP
{
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'IMAP service unavailable.', $_code = 911) {
        parent::__construct($_message, $_code);
    }
}
