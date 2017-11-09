<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 *
 */

/**
 * Routing Server class with handle() function
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Routing extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    const QUERY_PARAM_DO_ROUTING = 'doRouting';
    const PARAM_CLASS = '__class';
    const PARAM_METHOD = '__method';

    /**
     * the request method
     *
     * @var string
     */
    protected $_method = NULL;

    /**
     *
     * @var boolean
     */
    protected $_supportsSessions = true;

    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     * @param  \Zend\Http\Request  $request
     * @param  resource|string     $body
     * @return boolean
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        $this->_request = $request instanceof \Zend\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);

        try {
            if (Tinebase_Session::sessionExists()) {
                try {
                    Tinebase_Core::startCoreSession();
                } catch (Zend_Session_Exception $zse) {
                    // expire session cookie for client
                    Tinebase_Session::expireSessionCookie();
                }
            }

            Tinebase_Core::initFramework();

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' Is Routing request. uri: ' . $request->getUriString());
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ .' REQUEST: ' . print_r($_REQUEST, TRUE));
            
            $routes = $this->_getPublicRoutes();

            // register additional routes only available for authorised users
            if (Tinebase_Session::isStarted() && Zend_Auth::getInstance()->hasIdentity()) {
                $routes = array_merge_recursive($routes, $this->_getAuthRoutes());
            }

            $router = new \Zend\Mvc\Router\Http\TreeRouteStack();
            $router->addRoutes($routes);
            // TODO we could figure out who implements getBaseUrl and set it in case tine is installed
            // TODO in a subdirectory and not at root path... see \Zend\Router\Http\TreeRouteStack::match
            if (null === ($route = $router->match($request))) {
                if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ .' no route found');
                header('HTTP/1.0 404 Page Not Found');
                return false;
            }

            if (!$this->_dispatchRoute($route)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::WARN)) Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' _dispatchRoute returned false, very odd, shouldn\'t happen!');
                return false;
            }
        } catch (Exception $exception) {
            Tinebase_Exception::log($exception, false);
            header('HTTP/1.0 503 Service Unavailable');
            return false;
        }

        return true;
    }

    /**
     * get auth routes
     *
     * iterate over all enabled applications the user has run rights on, ask application controller for auth routes
     * method result is cached by hash over application id + version and user id
     *
     * @return array
     */
    protected function _getAuthRoutes()
    {
        $user = Tinebase_Core::getUser();
        // only applications with run right will get routes!
        $userApplications = $user->getApplications();
        $cacheKey = null;
        $persistentCache = null;

        if (defined('TINE20_BUILDTYPE') && TINE20_BUILDTYPE !== 'DEVELOPMENT') {
            // create a hash of installed applications and their versions
            $applicationVersions = array_combine(
                $userApplications->id,
                $userApplications->version
            );
            $cacheKey = __CLASS__ . __FUNCTION__ . Tinebase_Helper::arrayHash($applicationVersions, true)
                . $user->getId();

            $persistentCache = Tinebase_Core::getCache();
            if (false !== ($result = $persistentCache->load($cacheKey))) {
                return $result;
            }
        }

        $allRoutes = [];
        foreach($userApplications as $application) {
            /** @var Tinebase_Controller_Abstract $appController */
            $appController = $application->name . '_Controller';
            if (!class_exists($appController)) {
                continue;
            }
            $allRoutes = array_merge($allRoutes, $appController::getAuthRoutes());
        }

        if (null !== $persistentCache) {
            $persistentCache->save($allRoutes, $cacheKey);
        }
        return $allRoutes;
    }

    /**
     * get public routes
     *
     * iterate over all enabled applications, ask application controller for public routes
     *
     * @return array
     */
    protected function _getPublicRoutes()
    {
        $allApplications = Tinebase_Application::getInstance()->getApplications()
            ->filter('status', Tinebase_Application::ENABLED)->sort('order');

        $allRoutes = [];
        foreach($allApplications as $application) {
            /** @var Tinebase_Controller_Abstract $appController */
            $appController = $application->name . '_Controller';
            if (!class_exists($appController)) {
                continue;
            }
            $allRoutes = array_merge($allRoutes, $appController::getPublicRoutes());
        }

        return $allRoutes;
    }

    /**
     * @param \Zend\Mvc\Router\Http\RouteMatch $route
     * @return bool
     * @throws Tinebase_Exception_InvalidArgument
     * @throws ReflectionException
     */
    protected function _dispatchRoute(\Zend\Mvc\Router\Http\RouteMatch $route)
    {
        $params = $route->getParams();
        if (!isset($params[self::PARAM_CLASS])) {
            throw new Tinebase_Exception_UnexpectedValue('bad route, no class set: ' .
                $route->getMatchedRouteName());
        }
        if (!isset($params[self::PARAM_METHOD])) {
            throw new Tinebase_Exception_UnexpectedValue('bad route, no method set: ' .
                $route->getMatchedRouteName());
        }
        $class = $params[self::PARAM_CLASS];
        unset($params[self::PARAM_CLASS]);
        $method = $params[self::PARAM_METHOD];
        unset($params[self::PARAM_METHOD]);

        $reflection = new ReflectionMethod($class, $method);
        $orderedParams = array();
        foreach ($reflection->getParameters() as $refParam) {
            $refParamName = $refParam->getName();
            if (isset($params[$refParamName])) {
                // TODO: $refParam->getClass() -> create instance
                $orderedParams[$refParamName] = $params[$refParamName];
            } elseif ($refParam->isOptional()) {
                $orderedParams[$refParamName] = $refParam->getDefaultValue();
            } else {
                // TODO: $refParam->getClass() -> check for factory maybe?
                // TODO: dependency injection!
                throw new Tinebase_Exception_InvalidArgument('Missing required parameter: ' .
                    $refParam->getName());
            }
        }

        if ($reflection->isStatic()) {
            $callable = [$class, $method];
        } elseif (method_exists($class, 'getInstance')) {
            $callable = [call_user_func([$class, 'getInstance']), $method];
        } else {
            $callable = [new $class, $method];
        }

        if (false === call_user_func($callable, $orderedParams)) {
            return false;
        }
        return true;
    }

    /**
     * returns request method
     *
     * @return string|NULL
     */
    public function getRequestMethod()
    {
        return null;
    }
}
