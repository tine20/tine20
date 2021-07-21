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

use \Psr\Http\Server\RequestHandlerInterface;
use \Psr\Http\Server\MiddlewareInterface;
use \Psr\Http\Message\ResponseInterface;
use \Psr\Http\Message\ServerRequestInterface;

/**
 * expressive dispatch middleware, takes the route and dispatches request there
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Middleware_Dispatch implements MiddlewareInterface
{

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Server\RequestHandlerInterface $delegate
     * @throws Tinebase_Exception_UnexpectedValue
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate): ResponseInterface
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
            . __LINE__ . ' processing...');

        /** @var Tinebase_Expressive_RouteHandler $routeHandler */
        if (null === ($routeHandler = $request->getAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER, null))) {
            throw new Tinebase_Exception_UnexpectedValue('no matched route found');
        }

        $result = $routeHandler->dispatch();

        if (! is_object($result)) {
            throw new Tinebase_Exception_UnexpectedValue('route dispatching did not return an object');
        }
        if ($result instanceof ResponseInterface) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' dispatcher result is a ResponseInterface');
            return $result;
        }
        if ($result instanceof Tinebase_Record_Interface || $result instanceof  Tinebase_Record_RecordSet) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' dispatcher result is a Tinebase_Record_*');

            $response = new Tinebase_Expressive_Response();
            $response->resultObject = $result;
            
            return $response;
        }

        throw new Tinebase_Exception_UnexpectedValue('route dispatching returned unknown object of type: '
            . get_class($result));
    }
}
