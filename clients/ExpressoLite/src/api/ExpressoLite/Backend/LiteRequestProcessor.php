<?php

/**
 * Expresso Lite
 * LiteRequestProcessor acts as a hub to all Lite backend functions.
 * When LiteRequestProcessor receives a request, it executes the
 * following steps:
 *   1. Creates it creates a 'request handler' object of the
 *      appropriate class (a subclass of LiteRequest)
 *   2. Initializes it with the currently available TineSession and
 *      parameters
 *   3. Tells the handler to process the request.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend;

use \ReflectionClass;
use \ReflectionException;
use ExpressoLite\Exception\LiteException;

class LiteRequestProcessor {

    /**
     * This is the default name space in which request handlers will be
     * searched for
     */
    const LITE_REQUEST_NAMESPACE = 'ExpressoLite\\Backend\\Request\\';

    /**
     * Executes a request identified by a name, applying the informed parameters.
     *
     * @param $requestName The
     *            request name, as defined by Lite.
     * @param $params The
     *            params to be associated to the request.
     *
     */
    public function executeRequest($requestName, $params = array()) {
        $requestHandler = $this->prepareLiteRequestHandler($requestName, $params );
        $requestHandler->checkConstraints();

        $result = $requestHandler->execute();

        if ($result != null && is_string($result)) {
            return json_decode ($result);
        } else {
            return $result;
        }
    }

    /**
     * Instantiates the appropriate request handler (an object of a subclass of LiteRequest)
     * and initializes it with the current Tine Session and the informed params.
     *
     *
     * @param $requestName The
     *            request name, as defined by Lite. It must be the same name
     *            of the class that is responsible to execute it.
     * @param $params The
     *            params to be associated to the request.
     *
     */
    public function prepareLiteRequestHandler($requestName, $params = array()) {
        $handlerClassName = self::LITE_REQUEST_NAMESPACE . ucfirst ( $requestName ); // uppercase first letter

        $tineSession = TineSessionRepository::getTineSession ();
        try {
            $handlerClass = @new ReflectionClass ( $handlerClassName );
        } catch ( ReflectionException $re ) {
            throw new LiteException ( 'Invalid Lite Request: ' . $requestName, 0, 400 );
        }

        $functionHandler = $handlerClass->newInstance ();
        $functionHandler->init ( $this, $tineSession, $params );

        TineSessionRepository::storeTineSession ( $tineSession );
        // store tineSession back in case any of its attributes changed.

        // TODO: this is probably not necessary for most calls, as session attributes
        // usually only change during login.

        return $functionHandler;
    }
}
