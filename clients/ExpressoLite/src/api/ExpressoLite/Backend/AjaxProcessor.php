<?php

/**
 * Expresso Lite
 * AjaxProcessor parses incoming AJAX calls, redirects them to a
 * LiteRequestProcessor and formats the response back to the client.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend;

use \Exception;
use ExpressoLite\Exception\LiteException;

class AjaxProcessor {

    /**
     * Processes an AJAX request and outputs its response.
     * $httpRequest['r'] will
     * be considered the request name, and other $httpRequest entries the request
     * parameters.
     *
     * @param $httpRequest Should
     *            always be $_REQUEST
     *
     */
    public function processHttpRequest($httpRequest) {
        if (!isset($httpRequest ['r'])) {
            $this->echoResult($this->createHttpError(400, 'request function [\'r\'] is not defined'));
        } else {
            $requestName = $httpRequest['r'];
            $params = $this->getParamsFromHttpRequest($httpRequest);

            try {
                $liteRequestProcessor = new LiteRequestProcessor();
                $result = $liteRequestProcessor->executeRequest($requestName,$params);
            } catch(LiteException $le) {
                $result = $this->createHttpError($le->getHttpCode(), $le->getMessage());
            } catch(Exception $e) {
                $msg = "Error executing $requestName. Message: "  . $e->getMessage();
                $result = $this->createHttpError(500, $msg);
                error_log($msg); // TODO: improve exception logging
                // Important: DO NOT PRINT THE STACK TRACE HERE, as it may include
                // sensible user information (password, for instance)
            }

            $this->echoResult($result);
        }
    }

    /**
     * Filters the request name ($httpRequest['r']) to isolate the request parameters.
     *
     * @param $httpRequest Should
     *            always be $_REQUEST
     *
     * @return An indexed array with all request parameters (but not the request name).
     *
     */
    private function getParamsFromHttpRequest($httpRequest) {
        $params = array ();

        foreach ( $httpRequest as $key => $val ) {
            if ($key !== 'r') {
                $params [$key] = $val;
            }
        }

        return ( object ) $params;
    }

    /**
     * Creates on object that represents an HTTP error.
     *
     * @param $code the
     *            HTTP code (404, 401, etc...)
     * @param $message The
     *            message to be outputed
     *
     * @return on object that represents an HTTP error.
     */
    public function createHttpError($code, $message) {
        return ( object ) array (
                'httpError' => ( object ) array (
                        'code' => $code,
                        'message' => $message
                )
        );
    }

    /**
     * Outputs the AJAX response.
     * If it is an HTTP error, it will set
     * HTTP code acordingly.
     *
     * @param $result The
     *            AJAX request result as an object (or null).
     *
     */
    private function echoResult($result) {
        if ($result == null) {
            // this may happen when the request results in
            // in a binary output. In such cases, the output
            // is performed directly by the LiteRequest object
            return;
        } else if (isset ( $result->httpError )) {
            $this->echoHttpError ( $result->httpError );
        } else {
            header ( 'Content-Type: application/json' );
            echo json_encode ( $result );
        }
    }

    /**
     * Outputs an HTTP error, setting the HTTP code acordingly.
     *
     * @param $httpError The
     *            object that represents the httpError.
     *
     */
    private function echoHttpError($httpError) {
        $description = array (
                400 => 'Bad Request',
                401 => 'Unauthorized',
                500 => 'Internal Server Error'
        );
        header ( 'Content-type:text/html; charset=UTF-8' );
        header ( sprintf ( 'HTTP/1.1 %d %s', $httpError->code, $description [$httpError->code] ) );
        die ( $httpError->message ); // note: a standard HTML object will be sent, see Firebug output
    }
}
