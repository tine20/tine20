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
 * FastRoute middleware, continues if a route matched, puts the matched Tinebase_Expressive_RouteHandler in the request
 * returns a Response 404/405 if no route matched
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_Middleware_FastRoute implements MiddlewareInterface
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
        $dispatcher = $this->_getDispatcher();

        // TODO allow routing on query parameters too? This is only routing on the path!
        $routeInfo = $dispatcher->dispatch($request->getMethod(), $request->getUri()->getPath());
        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                // 404 not found
                return new Response('php://memory', 404);
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                //$allowedMethods = $routeInfo[1];
                // 405 method not allowed
                return new Response('php://memory', 405);
            case FastRoute\Dispatcher::FOUND:
                $handler = Tinebase_Expressive_RouteHandler::fromArray($routeInfo[1]);
                $handler->setVars($routeInfo[2]);
                return $delegate->process($request->withAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER, $handler));
                break;
            default:
                throw new Tinebase_Exception_UnexpectedValue('fast route dispatcher returned unexpected route info');
        }

        // in case you ever want to call $delegate->process without add the Tinebase_Expressive_Const::ROUTE_HANDLER
        // then do it like this: $delegate->process($request->withoutAttribute(Tinebase_Expressive_Const::ROUTE_HANDLER)
    }

    /**
     * @return \FastRoute\Dispatcher
     */
    protected function _getDispatcher()
    {
        $enabledApplications = Tinebase_Application::getInstance()->getApplications();
        // example app wird in den tests enabled
            //TODO what about this? why is example app not enabled? ->filter('status', Tinebase_Application::ENABLED);
        $apps = array_combine(
            $enabledApplications->id,
            $enabledApplications->version
        );
        ksort($apps);
        $appsHash = Tinebase_Helper::arrayHash($apps, true);

        // TODO think about the caching, do caching in tempDir? Or Cache Dir? Implement Redis Plugin?
        // TODO add base path in case tine20 was not installed in /
        return \FastRoute\cachedDispatcher(function (\FastRoute\RouteCollector $r) use ($enabledApplications) {
            /** @var Tinebase_Model_Application $application */
            foreach ($enabledApplications as $application) {
                /** @var Tinebase_Controller_Abstract $className */
                $className = $application->name . '_Controller';
                if (class_exists($className)) {
                    $className::addFastRoutes($r);
                }
            }
        }, [
            // TODO implement getCacheDir (falls back to getTempDir)
            'cacheFile' => Tinebase_Core::getTempDir() . '/route.cache.'
                . $appsHash,
            // TODO add development mode check! 'cacheDisabled' => ,
        ]);
    }
}