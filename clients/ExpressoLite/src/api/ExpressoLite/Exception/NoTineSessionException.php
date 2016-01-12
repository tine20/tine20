<?php
/**
 * Expresso Lite
 * Exception thrown when a LiteRequest is invoked without estabilishing
 * a TineSession first. However, this exception is not thrown if
 * LiteRequest->allowAccessWithoutSession() returns true.
 *
 *
 * @package   ExpressoLite\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Exception;

class NoTineSessionException extends LiteException
{
    /**
     * Creates a new <tt>NoTineSessionException</tt>.
     * It defaults parent class httpCode to 401.
     *
     * @param string $message The exception message.
     */
    public function __construct($message)
    {
        parent::__construct($message, self::HTTP_401_UNAUTHORIZED);
    }
}
