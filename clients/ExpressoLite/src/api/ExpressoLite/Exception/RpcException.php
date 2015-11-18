<?php
/**
 * Tine Tunnel
 * Tine Tunnel exceptions related to RPC
 *
 * @package   ExpressoLite\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Exception;

class RpcException extends LiteException
{
    /**
     * Creates a new <tt>RpcException</tt>.
     * Uses HTTP code 500 - INTERNAL_ERROR
     *
     * @param string $message The exception message.
     */
    public function __construct($message)
    {
        parent::__construct($message, self::HTTP_500_INTERNAL_ERROR);
    }
}
