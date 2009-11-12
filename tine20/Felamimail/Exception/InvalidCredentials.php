<?php
/**
 * Tine 2.0
 * 
 * @package     Felamimail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id: Exception.php 7990 2009-05-08 17:25:05Z p.schuele@metaways.de $
 *
 */

/**
 * Felamimail_Exception_InvalidCredentials
 * 
 * @package     Felamimail
 * @subpackage  Exception
 */
class Felamimail_Exception_InvalidCredentials extends Felamimail_Exception
{
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'Invalid Credentials.', $_code = 902) {
        parent::__construct($_message, $_code);
    }
}
