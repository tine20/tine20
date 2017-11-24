<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Zend\Diactoros\Response;
use \Zend\Diactoros\Response\SapiEmitter;
use \Zend\Diactoros\ServerRequestFactory;
use \Zend\Stratigility\MiddlewarePipe;

/**
 * Expressive Server class with handle() function
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Expressive extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    const QUERY_PARAM_DO_EXPRESSIVE = 'doRouting';
    const PARAM_CLASS = '__class';
    const PARAM_METHOD = '__method';

    /**
     * the request
     *
     * @var \Zend\Diactoros\Request
     */
    protected $_request = NULL;

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
        // TODO replace the unittest switch on the $body === null condition ... also make emitter configurable
        // TODO for unittesting a test emitter should be injected
        if (null === $body) {
            $this->_request = ServerRequestFactory::fromGlobals();
        } else {
            // unit testing only!
            // TODO maybe assert development mode here!
            $request->setContent($body);
            $this->_request = \Zend\Psr7Bridge\Psr7ServerRequest::fromZend($request);
        }

        // TODO session handling in middle ware? this is a question!
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

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                .' Is Routing request. uri: ' . $this->_request->getUri()->getPath() . '?'
                . $this->_request->getUri()->getQuery());
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::'
                . __LINE__ .' REQUEST: ' . print_r($this->_request, true));

            $responsePrototype = new Response();

            $middleWarePipe = new MiddlewarePipe();
            $middleWarePipe->setResponsePrototype($responsePrototype);
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_ResponseEnvelop());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_FastRoute());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_CheckRouteAuth());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_RoutePipeInject());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_Dispatch());


            $response = $middleWarePipe($this->_request, $responsePrototype, function() {
                throw new Tinebase_Exception('reached end of pipe stack, should never happen');
            });

            if (null === $body) {
                $emitter = new SapiEmitter();
                $emitter->emit($response);
            } else {
                // unittesting
                echo $response->getBody();
            }

        } catch (Exception $exception) {
            Tinebase_Exception::log($exception, false);
            header('HTTP/1.0 500 Service Unavailable');
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
