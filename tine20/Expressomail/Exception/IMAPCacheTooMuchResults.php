<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 *
 */

/**
 * General IMAP Exception
 *
 * @package     Felamimail
 * @subpackage  Exception
 */
class Expressomail_Exception_IMAPCacheTooMuchResults extends Expressomail_Exception
{
    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'Too much results, please refine your filter!', $_code = 932) {
        parent::__construct($_message, $_code);
    }
}
