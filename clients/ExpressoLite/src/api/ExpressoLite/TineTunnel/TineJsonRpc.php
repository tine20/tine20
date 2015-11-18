<?php
/**
* Tine Tunnel
* Low-level Tine specific JSON-RPC abstraction.
* Handles Tine specific headers and JSON keys.
*
* @package   ExpressoLite\TineTunnel
* @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
* @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
* @author    Charles Wust <charles.wust@serpro.gov.br>
* @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
*/

namespace ExpressoLite\TineTunnel;

use ExpressoLite\Exception\RpcException;
use ExpressoLite\Exception\TineErrorException;

class TineJsonRpc extends JsonRpc
{

    /**
     * Default user agent to be sent as a header in all TineJsonRpc calls
     */
    const DEFAULT_USERAGENT = 'Mozilla/5.0 (X11; Linux i686; rv:28.0) Gecko/20100101 Firefox/28.0';

    /**
     * @var int $id A randomly generated call id
     */
    private $id;

    /**
     * @var string $tineUrl Tine URL address
     */
    private $tineUrl = null;

    /**
     * @var string $jsonKey Tine's jsonKey estabilished during login
     */
    private $jsonKey = 'undefined';

    /**
     * @var boolean $acceptErrors if this is true, calls that return
     * errors will not throw exceptions
     */
    private $acceptErrors = false;

    /**
     * @var boolean $activateTineXDebug if this is true, it will append
     * GET parameters to the call that will activate XDebug in the Tine server.
     * Should be used only for debug purposes
     */
    private $activateTineXDebug = false;

    /**
     * Creates a new TineJsonRpc, initializing it with a random id
     *
     */
    public function __construct()
    {
        $this->id = sha1(mt_rand() . microtime());
    }

    /**
     * Sets the address in which Tine is located
     *
     * @param $tineUrl The address in which Tine is located
     */
    public function setTineUrl($tineUrl)
    {
        $this->tineUrl = $tineUrl;
    }

    /**
     * Sets the call jsonKey, so Tine will identify it as an
     * call done by an authenticated user
     *
     * @param $jsonKey Tine's jsonKey provided during login
     */
    public function setJsonKey($jsonKey)
    {
        $this->jsonKey = $jsonKey;
    }

    /**
     * If this is set to true, it will suppress exception throwing
     * when the response given by Tine is an error
     *
     * @param $acceptErrors
     */
    public function setAcceptErrors($acceptErrors)
    {
        $this->acceptErrors = $acceptErrors;
    }

    /**
     * When the request is made to tine, an extra GET parameter is added
     * to the URL. This parameter activates XDebug in the Tine server (if
     * it is configured to do so).
     * This should NEVER be set to true in production!
     *
     * @param $activateTineXDebug Indicates if XDebug in Tine server should
     * be activated
     */
    public function setActivateTineXDebug($activateTineXDebug)
    {
        $this->activateTineXDebug = $activateTineXDebug;
    }

    /**
     * Sends the request to Tine. If Tine returns a response with an error field
     * it will throw an acception (unless $acceptErrors is set to true).
     *
     * @return Response given by Tine
     */
    public function send($method = self::POST)
    {
        if ($this->tineUrl == null) {
            throw new RpcException('tineUrl is not defined');
        }

        $url = $this->tineUrl . '?transactionid=' . $this->id;
        if ($this->activateTineXDebug) {
            $url = $url . '&XDEBUG_SESSION_START=ECLIPSE_DBGP';
        }

        $this->setUrl($url);

        $this->setHeaders(array(
            'Content-Type: application/json; charset=UTF-8',
            'Connection: Keep-Alive',
            'User-Agent: ' . self::DEFAULT_USERAGENT,
            'DNT: 1',
            'X-Requested-With: XMLHttpRequest',
            'X-Tine20-JsonKey: ' . $this->jsonKey,
            'X-Tine20-Request-Type: JSON',
            'X-Tine20-TransactionId: ' . $this->id
        ));

        $response = parent::send();

        if (! $this->acceptErrors && isset($response->error)) {
            throw new TineErrorException($response->error);
        }

        return $response;
    }
}
