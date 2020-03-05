<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2013-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * Server Abstract with handle function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
abstract class Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    const HTTP_ERROR_CODE_FORBIDDEN = 403;
    const HTTP_ERROR_CODE_NOT_FOUND = 404;
    const HTTP_ERROR_CODE_SERVICE_UNAVAILABLE = 503;
    const HTTP_ERROR_CODE_INTERNAL_SERVER_ERROR = 500;

    /**
     * the request
     *
     * @var \Zend\Http\PhpEnvironment\Request
     */
    protected $_request = NULL;
    
    /**
     * the request body
     * 
     * @var resource|string
     */
    protected $_body;
    
    /**
     * set to true if server supports sessions
     * 
     * @var boolean
     */
    protected $_supportsSessions = false;

    /**
     * cache for modelconfig methods by frontend
     *
     * @var array
     */
    protected static $_modelConfigMethods = array();

    public function __construct()
    {
        if ($this->_supportsSessions) {
            Tinebase_Session_Abstract::setSessionEnabled('TINE20SESSID');
        }
    }
    
    /**
     * read auth data from all available sources
     * 
     * @param \Zend\Http\PhpEnvironment\Request $request
     * @throws Tinebase_Exception_NotFound
     * @return array
     */
    protected function _getAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($authData = $this->_getPHPAuthData($request)) {
            return $authData;
        }
        
        if ($authData = $this->_getBasicAuthData($request)) {
            return $authData;
        }
        
        throw new Tinebase_Exception_NotFound('No auth data found');
    }
    
    /**
     * fetch auch from PHP_AUTH*
     * 
     * @param  \Zend\Http\PhpEnvironment\Request  $request
     * @return array
     */
    protected function _getPHPAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($request->getServer('PHP_AUTH_USER')) {
            return array(
                $request->getServer('PHP_AUTH_USER'),
                $request->getServer('PHP_AUTH_PW')
            );
        }
    }
    
    /**
     * fetch basic auth credentials
     * 
     * @param  \Zend\Http\PhpEnvironment\Request  $request
     * @return array
     */
    protected function _getBasicAuthData(\Zend\Http\PhpEnvironment\Request $request)
    {
        if ($header = $request->getHeaders('Authorization')) {
            return explode(
                ":",
                base64_decode(substr($header->getFieldValue(), 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                2
            );
            
        } elseif ($header = $request->getServer('HTTP_AUTHORIZATION')) {
            return explode(
                ":",
                base64_decode(substr($header, 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                2
            );
            
        } else {
            // check if (REDIRECT_)*REMOTE_USER is found in SERVER vars
            $name = 'REMOTE_USER';
            
            for ($i=0; $i<5; $i++) {
                if ($header = $request->getServer($name)) {
                    return explode(
                        ":",
                        base64_decode(substr($header, 6)),  // "Basic didhfiefdhfu4fjfjdsa34drsdfterrde..."
                        2
                    );
                }
                
                $name = 'REDIRECT_' . $name;
            }
        }
    }

    /**
     * get default modelconfig methods
     *
     * @param string $frontend
     * @return array of Zend_Server_Method_Definition
     */
    protected static function _getModelConfigMethods($frontend)
    {
        if (array_key_exists($frontend, Tinebase_Server_Abstract::$_modelConfigMethods)) {
            return Tinebase_Server_Abstract::$_modelConfigMethods[$frontend];
        }

        // get all apps user has RUN right for
        try {
            $userApplications = Tinebase_Core::getUser() ? Tinebase_Core::getUser()->getApplications() : array();
        } catch (Tinebase_Exception_NotFound $tenf) {
            // session might be invalid, destroy it
            Tinebase_Session::destroyAndRemoveCookie();
            $userApplications = array();
        }

        $definitions = array();
        foreach ($userApplications as $application) {
            try {
                $controller = Tinebase_Core::getApplicationInstance($application->name);
                $models = $controller->getModels();
                if (!$models) {
                    continue;
                }
            } catch (Exception $e) {
                Tinebase_Exception::log($e);
                continue;
            }

            foreach ($models as $model) {
                $config = $model::getConfiguration();
                if ($frontend::exposeApi($config)) {
                    $simpleModelName = Tinebase_Record_Abstract::getSimpleModelName($application, $model);
                    $commonApiMethods = $frontend::_getCommonApiMethods($application, $simpleModelName);

                    foreach ($commonApiMethods as $name => $method) {
                        $key = $application->name . '.' . $name . $simpleModelName . ($method['plural'] ? 's' : '');
                        $object = $frontend::_getFrontend($application);

                        $definitions[$key] = new Zend_Server_Method_Definition(array(
                            'name'            => $key,
                            'prototypes'      => array(array(
                                'returnType' => 'array',
                                'parameters' => $method['params']
                            )),
                            'methodHelp'      => $method['help'],
                            'invokeArguments' => array(),
                            'object'          => $object,
                            'callback'        => array(
                                'type'   => 'instance',
                                'class'  => get_class($object),
                                'method' => $name . $simpleModelName . ($method['plural'] ? 's' : '')
                            ),
                        ));
                    }
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Got MC definitions: ' . print_r(array_keys($definitions), true));

        Tinebase_Server_Abstract::$_modelConfigMethods[$frontend] = $definitions;

        return $definitions;
    }

    /**
     * @param int $code
     */
    public static function setHttpHeader($code)
    {
        if (! headers_sent()) {
            switch ($code) {
                case self::HTTP_ERROR_CODE_FORBIDDEN:
                    header('HTTP/1.1 403 Forbidden');
                    break;
                case self::HTTP_ERROR_CODE_NOT_FOUND:
                    header('HTTP/1.1 404 Not Found');
                    break;
                case self::HTTP_ERROR_CODE_SERVICE_UNAVAILABLE:
                    header('HTTP/1.1 503 Service Unavailable');
                    break;
                default:
                    header("HTTP/1.1 500 Internal Server Error");
            }
        }
    }
}
