<?php
/**
 * Tine 2.0
 *
 * Sieve Auth Fail / Credentials Exception
 * 
 * @package     Felamimail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 *
 */
class Felamimail_Exception_SieveInvalidCredentials extends Felamimail_Exception_Sieve
{
    /**
     * construct
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'Invalid Sieve Credentials.', $_code = 931)
    {
        parent::__construct($_message, $_code);
    }
}
