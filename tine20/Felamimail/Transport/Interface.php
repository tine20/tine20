<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Transport
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * mail transport interface for Felamimail
 * - added getBody/getHeaders and use these for appendMessage / sendMessage
 *
 * @package     Felamimail
 * @subpackage  Transport
 */
interface Felamimail_Transport_Interface
{
    /**
     * Constructor.
     *
     * @param  string $host OPTIONAL (Default: 127.0.0.1)
     * @param  array|null $config OPTIONAL (Default: null)
     * @return void
     */
    public function __construct($host = '127.0.0.1', Array $config = array());

    /**
     * get mail body as string
     *
     * @param Zend_Mail $_mail
     * @return string
     */
    public function getBody(Zend_Mail $_mail = NULL);

    /**
     * get mail headers as string
     *
     * @param array $_additionalHeaders
     * @return string
     */
    public function getHeaders($_additionalHeaders = array());

    /**
     * get raw message as string
     *
     * @param Zend_Mail $mail
     * @param array $_additionalHeaders
     * @return string
     */
    public function getRawMessage(Zend_Mail $mail = NULL, $_additionalHeaders = array());
}
