<?php
/**
 * Tine Tunnel
 * Low-level JSON-RPC abstraction.
 * Handles JSON RPC methods params, and decodes JSON responses as objects.
 *
 * @package   ExpressoLite\TineTunnel
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */

namespace ExpressoLite\TineTunnel;

use ExpressoLite\Exception\RpcException;

class JsonRpc extends Request
{

    /**
     * Default jsonrpc version to be used in JsonRpc calls
     */
    const JSON_RPC_VERSION = '2.0';

    /**
     * @var string $rpcMethod The name of the method that is being invoked
     */
    private $rpcMethod = null;

    /**
     * @var string $rpcParams An indexed array with all the input parameters
     * to be sent in this JsonRpc call
     */
    private $rpcParams = array();

    /**
     * Sets the name of the RPC method to be executed
     *
     * @param $rpcMethod The new RPC method name
     *
     */
    public function setRpcMethod($rpcMethod)
    {
        $this->rpcMethod = $rpcMethod;
    }

    /**
     * Sets the params of the RPC method to be executed
     *
     * @param $rpcParams The new RPC method params
     *
     */
    public function setRpcParams($rpcParams)
    {
        $this->rpcParams = $rpcParams;
    }

    /**
     * Sends the RPC and returns it results
     *
     * @return The RPC result
     *
     */
    public function send($method = self::POST)
    {
        if ($this->rpcMethod == null) {
            throw new RpcException('rpcMethod is not defined');
        }

        $this->setPostFields(json_encode(array(
            'id' => 1,
            'jsonrpc' => self::JSON_RPC_VERSION,
            'method' => $this->rpcMethod,
            'params' => $this->rpcParams
        )));

        $rawResponse = parent::send($method);

        return json_decode($rawResponse);
    }
}
