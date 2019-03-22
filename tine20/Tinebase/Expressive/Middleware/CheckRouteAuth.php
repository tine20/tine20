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
use \Zend\Diactoros\Response;

/**
 * expressive route auth middleware, reads matched route for auth requirements and checks them
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Middleware_CheckRouteAuth implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Interop\Http\Server\RequestHandlerInterface $delegate
     *
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

        if (! $routeHandler->isPublic()) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' in an auth route');

            if (null === ($user = Tinebase_Core::getUser())) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' returning with HTTP 401 unauthorized');

                // unauthorized
                return new Response('php://memory', 401);
            }
            if (! $user->hasRight($routeHandler->getApplicationName(), Tinebase_Acl_Rights_Abstract::RUN)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::'
                    . __LINE__ . ' returning with HTTP 403 forbidden');

                // forbidden
                return new Response('php://memory', 403);
            }

            // TODO add more sophisticated stuff
            // if ( $routeHandler->requiresRights() ) {
            // foreach ($routeHandler->getRequiredRights() as $right) {
            // if (! $user->hasRight($routeHandler->getApplicationName(), $right)) {

            return $delegate->handle($request);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::'
                . __LINE__ . ' in a public route');

            $routeHandler->setPublicRoles();

            try {
                return $delegate->handle($request);
            } finally {
                // TODO eventually we want this to happen in the ResponseEnvelop actually! if expanding would happen
                // TODO there... if expanding happens inside the delegate above we are fine
                $routeHandler->unsetPublicRoles();
            }
        }
    }
}