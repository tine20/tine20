<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      SERPRO
 *
 */

/**
 * IMAP Folder Duplicated
 *
 * @package     Expressomail
 * @subpackage  Exception
 */
class Expressomail_Exception_IMAPFolderDuplicated extends Expressomail_Exception_IMAP
{
    const CODE = 933;
    const MSG = 'Perhaps, the IMAP Folder that you want to create already exists.';
    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = self::MSG, $_code = self::CODE) {
        parent::__construct($_message, $_code);
    }
}
