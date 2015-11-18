<?php
/**
 * Expresso Lite Accessible
 * Dispatches all page requests.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible;

use \ReflectionClass;
use \Exception;
use ExpressoLite\Backend\TineSessionRepository;
use ExpressoLite\Exception\NoTineSessionException;

class Dispatcher
{
    /**
     * @var REQUEST_NAMESPACE Constant that indicates in
     *      wich request handlers will be searched for.
     */
    const REQUEST_NAMESPACE = 'Accessible\\';

    /**
     * @var LOGIN_REQUEST Request that directs to login screen
     */
    const LOGIN_REQUEST = 'Login.Main';

    /**
     * @var MAIL_REQUEST Request that directs to main mail screen
     */
    const MAIL_REQUEST = 'Mail.Main';

    /**
     * Processes raw HTTP requests. Used only in ../index.php page.
     *
     * @param array $httpRequest Should always be $_REQUEST object.
     */
    public static function processRawHttpRequest(array $httpRequestParams)
    {
        $request = isset($httpRequestParams['r']) ? $httpRequestParams['r'] : null;
        $params = self::getParamsFromHttpRequest($httpRequestParams);
        $isLoggedIn = TineSessionRepository::getTineSession()->isLoggedIn();

        if ($isLoggedIn && $request === null) {
            $request = self::MAIL_REQUEST;
        }

        if ($request === null) {
            $request = self::LOGIN_REQUEST;
        }

        self::processRequest($request, (object) $params);
    }

    /**
     * Calls another page, forwarding the given parameters.
     *
     * @param string   $request Name of the page to be called.
     * @param stdClass $params  stdClass with parameters to be forwarded to page.
     */
    public static function processRequest($request, $params = null)
    {
        try {
            $handlerClassName = self::REQUEST_NAMESPACE . str_replace('.', '\\', $request);
            $handlerClass = new ReflectionClass($handlerClassName);
            $requestHandler = $handlerClass->newInstance();
            header('Pragma: no cache');
            header('Cache-Control: no-cache');
            $requestHandler->execute($params);
        } catch (NoTineSessionException $ex) {
            self::processRequest(self::LOGIN_REQUEST);
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            die('<pre>' . $e->getMessage() . '<br />' . $e->getTraceAsString());
        }
    }

    /**
     * Filters the request name ($httpRequest['r']) to isolate the request parameters.
     *
     * @param  array $httpRequest Should always be $_REQUEST object.
     * @return array              An indexed array with all request parameters but request name.
     */
    private static function getParamsFromHttpRequest(array $httpRequest)
    {
        $params = array();
        foreach ($httpRequest as $key => $val) {
            if ($key !== 'r') {
                $params[$key] = $val;
            }
        }
        return (object) $params;
    }
}
