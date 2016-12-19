<?php
/**
 * Tine 2.0
 *
 * @package     Expressomail
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2015, SERPRO - Serviço Federal de Processamento de Dados
 * @author      Mário César Kolling <mario.kolling@serpro.gov.br>
 *
 */

/**
 * Imap Command Failed Exception
 *
 * @package     Expressomail
 * @subpackage  Exception
 */
class Expressomail_Exception_IMAPCommandFailed extends Expressomail_Exception_IMAP
{
    /**
     * construct
     *
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'IMAP Command Failed.', $_code = 915) {
        parent::__construct($_message, $_code);
    }
}
