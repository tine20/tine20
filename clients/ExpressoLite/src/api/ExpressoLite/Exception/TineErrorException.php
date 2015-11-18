<?php
/**
 * Tine Tunnel
 * Exception thrown when Tine returns a response with an error
 *
 * @package   ExpressoLite\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\Exception;

class TineErrorException extends LiteException
{
    /**
     * @var \stdClass An object that represents the error returned by tine. It two fields: code and message
     */
    private $tineError;

    /**
     * Creates a new <tt>TineErrorException</tt>.
     * Uses HTTP code 500 - INTERNAL_ERROR
     *
     * @param \stdClass An object that represents the error returned by tine. It two fields: code and message
     */
    public function __construct($tineError)
    {
        $code = isset($tineError->code) ? $tineError->code : '<undefined>';
        $message = isset($tineError->message) ? $tineError->message: '<undefined>';

        parent::__construct("Tine returned an error. Code: $code; Message: $message");
        $this->tineError = $tineError;
    }

    /**
     * @return \stdClass An object that represents the error returned by tine. It two fields: code and message
     */
    public function getTineError()
    {
        return $this->tineError;
    }
}
