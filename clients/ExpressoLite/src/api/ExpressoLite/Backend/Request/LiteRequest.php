<?php
/**
 * Expresso Lite
 * Abstract superclass for all handlers of Lite requests.
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

use ExpressoLite\Exception\NoTineSessionException;
use ExpressoLite\Backend\AjaxProcessor;
use ExpressoLite\Exception\LiteException;
use ExpressoLite\TineTunnel\TineSession;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use ExpressoLite\Exception\UserMismatchException;

abstract class LiteRequest
{

    /**
     * @var array Will contain all input params to be processed by the request handler
     */
    protected $params;

    /**
     * @var LiteRequestProcessor The LiteRequestProcessor that created this LiteRequest
     */
    protected $processor;

    /**
     * @var TineSession The current TineSession available at TineSessionRepository
     */
    protected $tineSession;

    /**
     * This is the main method of this class, which will execute all
     * logic related to the request. Needless to say, all subclasses
     * must necessarily implement it.
     *
     * @return The request response.
     */
    public abstract function execute();

    /**
     * Inits the request with useful information (see params for description)
     *
     * @param LiteRequestProcessor $processsor The processor that created this request
     * @param TineSession $tineSession The current estabilished tineSession (if there is one)
     * @param array $params The params that were informed to the request
     *
     */
    public function init(LiteRequestProcessor $processsor, TineSession $tineSession = null, $params = array())
    {
        if (is_array($params)) {
            $params = (object) $params;
        }

        $this->params = $params;
        $this->processor = $processsor;
        $this->tineSession = $tineSession;

    }

    /**
     * Checks all constraints that can prevent this request from 
     * being executed are met.
     *
     */
    public function checkConstraints() 
    {
        $this->checkIfSessionIsLoggedIn();
        $this->checkIfSessionUserIsValid();
    }

    /**
     * This function throws an exception if user is not logged in when
     * the request is executed, unless this request explicitly allows so
     *
     */
    private function checkIfSessionIsLoggedIn()
    {
        if (!$this->allowAccessWithoutSession() && !$this->tineSession->isLoggedIn()) {
            throw new NoTineSessionException('This request cannot be processed without a previously estabilished tine session');
        }
    }

    /**
     * Checks if user defined in the back-end is the same in the front-end, throwing
     * exceptions and logging the errors.
     *
     */
    protected function checkIfSessionUserIsValid()
    {
        $clientUser = isset($_COOKIE['user']) ? $_COOKIE['user'] : null;
        $tineSessionUser = $this->tineSession->getAttribute('Expressomail.email');

        if ($tineSessionUser != null && $clientUser != $tineSessionUser) {
            $this->resetTineSession();
            error_log('POSSIBLE SESSION HIJACKING! Client user: ' . $clientUser . '; TineSession user: '. $tineSessionUser);
            throw new UserMismatchException();
        }
    }

    /**
     * This method indicates it the LiteRequest may be invoked even without
     * a previously estabilished TineSession. By default, it returns false,
     * so invoking the request with a LiteRequestProcessor will return a
     * NoTineSessionException. However, this mehod should be overriden in
     * situations in which calling a request without a session is allowed.
     *
     * @return true or false, indicating whether the request may be invoked
     * without a TineSession
     */
    public function allowAccessWithoutSession()
    {
        // this method is supposed to be overriden by calls that
        // may be executed even without a previously estabilished
        // tine session (i.e. login)
        return false;
    }

    /**
     * Utility method to throw a liteException corresponding to
     * some http code
     *
     * @param $code The HTTP code to be sent to the user (like 404, 401, etc...)
     * @param $message The message that will be shown in the log
     *
     */
    public function httpError($code, $message)
    {
        throw new LiteException($message, 0, $code);
    }

    /**
     * Returns true if the user has already logged in, false otherwise.
     *
     */
    public function isLoggedIn()
    {
        $this->tineSession !== null && $this->tineSession->isLoggedIn();
    }

    /**
     * Returns the value of one of the request parameters.
     *
     * @param string $paramName The name of the parameter we want to get the value of
     *
     * @return string the param value
     */
    public function param($paramName)
    {
        return $this->params->{$paramName};
    }

    /**
     * Returns true if the specified parameter was set for this request,
     * false otherwise.
     *
     * @param string $paramName The name of the parameter we want to check
     *
     * @return boolean true if the parameter was informed to this request,
     *                 false otherwise
     */
    public function isParamSet($paramName)
    {
        return isset($this->params->{$paramName});
    }

    /**
     * Utility method to use the current estabilished TineSession to
     * make a JSON RPC call to Tine. If Tine returns an error, this will lead to
     * an exception
     *
     * @param string $method Tine's method name (i.e. Tinebase.getAllRegistryData)
     * @param array $params The parameters to be sent to Tine
     * @param boolean $acceptErrors If set to true, this will suppress exception throwing
     *       when Tine returns an error
     *
     * @return object The response of the JSON RPC call made to Tine
     *
     */
    public function jsonRpc($method, $params = array(), $acceptErrors = false)
    {
        return $this->tineSession->jsonRpc($method, $params, $acceptErrors);
    }

    /**
     * Utility method to get an attribute associated with the current TineSession
     *
     * @param string $attrName The name of the attribute we want to get the value of
     *
     * @return The value of the session attribute
     */
    public function getSessionAttribute($attrName)
    {
        return $this->tineSession->getAttribute($attrName);
    }

    /**
     * Utility method that does the following: it tries to get the attribute of an object:
     * $object->{$field}. If this field is set, than the field value is returned. If the
     * field is NOT set, it returns $defaultValue.
     *
     * @param $object The objetct that has the desired field
     * @param string $field The name of the field we want to get the value of
     * @param $defaultValue The value to be returned when the field is not set
     *
     * @return The object attribute or the default value.
     */
    public function coalesce($object, $field, $defaultValue)
    {
        return isset($object->{$field}) ? $object->{$field} : $defaultValue;
    }

    /**
     * Dumps the current tineSession associated to this request
     * with a new one provided by TineSessionRepository
     *
     * @return TineSession The new TineSession
     *
     */
    public function resetTineSession() {
        $this->tineSession = TineSessionRepository::resetTineSession();
    }
}

