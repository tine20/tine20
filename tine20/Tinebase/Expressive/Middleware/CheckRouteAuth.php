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
     *
     * TODO add logging
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate)
    {
        /** @var Tinebase_Expressive_RouteHandler $routeHandler */
        if (null === ($routeHandler = $request->getAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER, null))) {
            throw new Tinebase_Exception_UnexpectedValue('no matched route found');
        }

        if (! $routeHandler->isPublic()) {
            if (null === ($user = Tinebase_Core::getUser())) {
                // unauthorized
                return new Response('php://memory', 401);
            }
            if (! $user->hasRight($routeHandler->getApplicationName(), Tinebase_Acl_Rights_Abstract::RUN)) {
                // forbidden
                return new Response('php://memory', 403);
            }

            // TODO add more sophisticated stuff
            // if ( $routeHandler->requiresRights() ) {
            // foreach ($routeHandler->getRequiredRights() as $right) {
            // if (! $user->hasRight($routeHandler->getApplicationName(), $right)) {
        }

        return $delegate->process($request);
    }
}