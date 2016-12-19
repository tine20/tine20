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
 * General SMTP Exception
 * 
 * @package     Expressomail
 * @subpackage  Exception
 */
class Expressomail_Exception_SMTP extends Expressomail_Exception
{
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'General SMTP error.', $_code = 920) {
        parent::__construct($_message, $_code);
    }
}
