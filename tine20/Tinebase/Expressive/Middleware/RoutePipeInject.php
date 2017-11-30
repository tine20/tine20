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
 * expressive route pipe injection middleware, reads matched route for additional middleware to pipe
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Middleware_RoutePipeInject implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Interop\Http\Server\RequestHandlerInterface $delegate
     * @throws Tinebase_Exception_UnexpectedValue
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . ' processing...');

        /** @var Tinebase_Expressive_RouteHandler $routeHandler */
        if (null === ($routeHandler = $request->getAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER, null))) {
            throw new Tinebase_Exception_UnexpectedValue('no matched route found');
        }

        if ($routeHandler->hasPipeInject()) {
            $pipeInjectData = $routeHandler->getPipeInject();

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' injecting: ' . print_r($pipeInjectData, true));

            $responsePrototype = new \Zend\Diactoros\Response();
            $middleWarePipe = new \Zend\Stratigility\MiddlewarePipe();
            $middleWarePipe->setResponsePrototype($responsePrototype);

            // add dynamic middleware here
            foreach ($pipeInjectData as $pIData) {
                if (isset($pIData[Tinebase_Expressive_RouteHandler::PIPE_INJECT_CLASS])) {
                    $middleWarePipe->pipe(new $pIData[Tinebase_Expressive_RouteHandler::PIPE_INJECT_CLASS]);
                } else {
                    throw new Tinebase_Exception_UnexpectedValue('pipe inject data corrupt');
                }
            }

            return $middleWarePipe($request, $responsePrototype, function($request) use ($delegate) {
                return $delegate->handle($request);
            });
        } else {
            return $delegate->handle($request);
        }

    }
}