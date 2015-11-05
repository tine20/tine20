<?php
/**
 * Expresso Lite
 * Represents a generic exception thrown by a ExpressoLite feature
 *
 * @package   ExpressoLite\Exception
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Exception;

use \Exception;

class LiteException extends Exception
{
   /**
     * @var int HTTP_401_UNAUTHORIZED Http error code
     */
    const HTTP_401_UNAUTHORIZED = 401;

    /**
     * @var int HTTP_500_INTERNAL_ERROR Http error code
     */
    const HTTP_500_INTERNAL_ERROR = 500;

    /**
     * @var int $httpCode Http code returned to the frontend when this exception is
     * thrown during a call done by AjaxProcessor
     */
    private $httpCode;

    /**
     * Creates a new <tt>LiteException</tt>
     *
     * @param string $message The exception message.
     * @param int $httCode If the exception occurs during a call done by AjaxProcessor,
     *    this is the http code returned to the frontend
     * @param int $code The exception code used for logging.
     */
    public function __construct($message, $httpCode= self::HTTP_500_INTERNAL_ERROR, $code = 0)
    {
        parent::__construct($message, $code);
        $this->setHttpCode($httpCode);
    }

    /**
     * Creates a string representation of the exception
     *
     */
    public function __toString()
    {
        return get_called_class() . ": [{$this->code}]: {$this->message} (HTTP $this->httpCode)\n";
    }

    /**
     * Gets httpCode
     *
     */
    public function getHttpCode() {
        return $this->httpCode;
    }

    /**
     * Sets httpCode
     *
     * @param int $httCode The HTTP code to be set for this exception
     */
    public function setHttpCode($httpCode) {
        $this->httpCode = $httpCode;
    }

}
