<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Expressive
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Interop\Http\Server\RequestHandlerInterface;
use \Interop\Http\Server\MiddlewareInterface;
use \Psr\Http\Message\ServerRequestInterface;

/**
 * examines the response object and the request headers. Decides how to envelop the response
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Middleware_ResponseEnvelop implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Interop\Http\Server\RequestHandlerInterface $delegate
     * @throws Tinebase_Exception_UnexpectedValue
     * @return \Psr\Http\Message\ResponseInterface
     *
     * TODO add more logging!
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate)
    {
        $response = $delegate->process($request);

        if ($response instanceof Tinebase_Expressive_Response) {
            // make body rewindable, writable, in doubt just create a new response or use withBody()
            $response->getBody()->rewind();
            $response->getBody()->write(json_encode([
                'results' => null === $response->resultObject ? [] : $response->resultObject->toArray(),
                //'resultsCount' => $response->resultCount !== null..
                'status' => $response->getStatusCode()
            ]));
        } // else {
        // maybe react to status !== 200
        // if client wants json envelop
        // if response->getStatusCode() !== 200
        // make body rewindable, writable, in doubt just create a new response or use withBody()
        // $response->getBody()->rewind();
        // $response->getBody()->write(json_encode(['results' => [], 'status' => $response->getStatusCode()]));
        //}

        return $response;
    }
}