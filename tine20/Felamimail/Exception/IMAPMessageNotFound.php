<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: IMAPServiceUnavailable.php 14959 2010-06-16 09:00:56Z c.weiss@metaways.de $
 *
 */

/**
 * Message Not Found Exception
 * 
 * @package     Felamimail
 * @subpackage  Exception
 */
class Felamimail_Exception_IMAPMessageNotFound extends Felamimail_Exception_IMAP
{
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     * @todo for Cornelius: switch back to error code 914
     */
    #public function __construct($_message = 'Message not found on IMAP server.', $_code = 914) {
    public function __construct($_message = 'Message not found on IMAP server.', $_code = 404) {
        parent::__construct($_message, $_code);
    }
}
