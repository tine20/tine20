<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Expressive
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Psr\Http\Server\RequestHandlerInterface;
use \Psr\Http\Server\MiddlewareInterface;
use \Psr\Http\Message\ServerRequestInterface;
use \Psr\Http\Message\ResponseInterface;
use \Laminas\Diactoros\Response;

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
     * @param \Psr\Http\Server\RequestHandlerInterface $delegate
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . ' processing...');

        try {
            $response = $delegate->handle($request);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' inspecting response...');
            }

            if ($response instanceof Tinebase_Expressive_Response) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' found ' .
                        Tinebase_Expressive_Response::class);
                }

                if (0 !== $response->getBody()->tell()) {
                    throw new Tinebase_Exception_UnexpectedValue('response stream not at possition 0');
                }

                // TODO implement stuff here ... really? Why here? Can't we do that in a data resolve middleware?
                // TODO maybe this nice slim toArray() is all we want to do here, don't take to much responsibility at once
                // TODO finish the envelop format
                $response->getBody()->write(json_encode([
                    'results' => null === $response->resultObject ? [] : $response->resultObject->toArray(),
                    //'resultsCount' => $response->resultCount !== null..
                    'status' => $response->getStatusCode()
                ]));
            }
        } catch (Tinebase_Exception_NotFound $tenf) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . $tenf->getMessage());
            $response = new Response($body = 'php://memory', Tinebase_Server_Expressive::HTTP_ERROR_CODE_NOT_FOUND);
        } catch (Tinebase_Exception_AccessDenied $tead) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(
                __METHOD__ . '::' . __LINE__ . ' ' . $tead->getMessage());
            $response = new Response($body = 'php://memory', Tinebase_Server_Expressive::HTTP_ERROR_CODE_FORBIDDEN);
        } catch (Tinebase_Exception_Expressive_HttpStatus $teeh) {
            // the exception can use logToSentry and logLevelMethod properties to achieve desired logging
            // default is false (no sentry) and info log level
            Tinebase_Exception::log($teeh);
            $response = new Response($body = 'php://memory', $teeh->getCode());
        } catch (Exception $e) {
            Tinebase_Exception::log($e, false);
            $response = new Response($body = 'php://memory', Tinebase_Server_Expressive::HTTP_ERROR_CODE_INTERNAL_SERVER_ERROR);
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            $body = $response->getBody();
            $body->rewind();
            $headerStr = '';
            foreach ($response->getHeaders() as $name => $values) {
                $headerStr .= "$name: {$values[0]}\n";
            }

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Response headers: " . $headerStr);
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . " Response body: " . $body->getContents());
            }
        }
        return $response;
    }
}
