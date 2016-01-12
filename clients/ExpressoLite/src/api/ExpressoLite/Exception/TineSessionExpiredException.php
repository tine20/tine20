<?php
/**
 * Tine Tunnel
 * This exception is thrown when a TineJsonRpc call is made while
 * Tine is not auhtenticated.
 *
 * @package   ExpressoLite\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Exception;

class TineSessionExpiredException extends LiteException
{
    /**
     * Creates a new <tt>TineSessionExpiredException</tt>.
     * Uses HTTP code 401 - UNAUTHORIZED
     */
    public function __construct()
    {
        parent::__construct('User session on Tine is likely to be expired', self::HTTP_401_UNAUTHORIZED);
    }
}
