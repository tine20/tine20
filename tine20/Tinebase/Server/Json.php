<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * 
 */

/**
 * JSON Server class with handle() function
 * 
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Json extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    /**
     * handled request methods
     * 
     * @var array
     */
    protected $_methods = array();
    
    /**
     * 
     * @var boolean
     */
    protected $_supportsSessions = true;
    
    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        $this->_request = $request instanceof \Zend\Http\Request ? $request : Tinebase_Core::get(Tinebase_Core::REQUEST);
        $this->_body    = $body !== null ? $body : fopen('php://input', 'r');

        // only for debugging
        //Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " raw request: " . $request->__toString());
        
        // handle CORS requests
        if ($this->_request->getHeaders()->has('ORIGIN') && !$this->_request->getHeaders()->has('X-FORWARDED-HOST')) {
            /**
             * First the client sends a preflight request
             * 
             * METHOD: OPTIONS
             * Access-Control-Request-Headers:x-requested-with, content-type
             * Access-Control-Request-Method:POST
             * Origin:http://other.site
             * Referer:http://other.site/example.html
             * User-Agent:Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36
             * 
             * We have to respond with
             * 
             * Access-Control-Allow-Credentials:true
             * Access-Control-Allow-Headers:x-requested-with, x-tine20-request-type, content-type, x-tine20-jsonkey
             * Access-Control-Allow-Methods:POST
             * Access-Control-Allow-Origin:http://other.site
             * 
             * Then the client sends the standard JSON request with two additional headers
             * 
             * METHOD: POST
             * Origin:http://other.site
             * Referer:http://other.site/example.html
             * Standard-JSON-Request-Headers...
             * 
             * We have to add two additional headers to our standard response
             * 
             * Access-Control-Allow-Credentials:true
             * Access-Control-Allow-Origin:http://other.site
             */
            $origin = $this->_request->getHeaders('ORIGIN')->getFieldValue();
            $uri    = \Zend\Uri\UriFactory::factory($origin);
            
            if (in_array($uri->getScheme(), array('http', 'https'))) {
                $allowedOrigins = array_merge(
                    (array) Tinebase_Core::getConfig()->get(Tinebase_Config::ALLOWEDJSONORIGINS, array()),
                    array($this->_request->getServer('SERVER_NAME'))
                );
                
                if (in_array($uri->getHost(), $allowedOrigins)) {
                    // this headers have to be sent, for any CORS'ed JSON request
                    header('Access-Control-Allow-Origin: ' . $origin);
                    header('Access-Control-Allow-Credentials: true');
                }
                
                // check for CORS preflight request
                if ($this->_request->getMethod() == \Zend\Http\Request::METHOD_OPTIONS &&
                    $this->_request->getHeaders()->has('ACCESS-CONTROL-REQUEST-METHOD')
                ) {
                    $this->_methods = array('handleCors');
                    
                    if (in_array($uri->getHost(), $allowedOrigins)) {
                        header('Access-Control-Allow-Methods: POST');
                        header('Access-Control-Allow-Headers: x-requested-with, x-tine20-request-type, content-type, x-tine20-jsonkey');
                        header('Access-Control-Max-Age: 3600'); // cache result of OPTIONS request for 1 hour
                        
                    } else {
                        Tinebase_Core::getLogger()->WARN (__METHOD__ . '::' . __LINE__ . " unhandled CORS preflight request from $origin");
                        Tinebase_Core::getLogger()->INFO (__METHOD__ . '::' . __LINE__ . " you may want to set \"'allowedJsonOrigins' => array('{$uri->getHost()}'),\" to config.inc.php");
                        Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ . " allowed origins: " . print_r($allowedOrigins, TRUE));
                    }
                    
                    // stop further processing => is OPTIONS request
                    return;
                }
            }
        }
        
        $exception = false;
        
        if (Tinebase_Session::sessionExists()) {
            try {
                Tinebase_Core::startCoreSession();
            } catch (Zend_Session_Exception $zse) {
                $exception = new Tinebase_Exception_AccessDenied('Not Authorised', 401);
                
                // expire session cookie for client
                Tinebase_Session::expireSessionCookie();
            }
        }
        
        if ($exception === false) {
            try {
                Tinebase_Core::initFramework();
            } catch (Throwable $e) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ .' initFramework exception: ' .
                    get_class($e) . ' ' . $e->getMessage());
                $exception = $e;
            }
        }
        
        $json = $this->_request->getContent();
        $json = Tinebase_Core::filterInputForDatabase($json);

        if (empty($json)) {
            // nginx cuts the JSON POST payload if out of disk space ...
            throw new Tinebase_Exception_SystemGeneric('Got empty JSON request - server out of disk space?');
        }

        if (substr($json, 0, 1) == '[') {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' batched request');
            $isBatchedRequest = true;
            $requests = Zend_Json::decode($json);
        } else {
            $isBatchedRequest = false;
            $requests = array(Zend_Json::decode($json));
        }

        $this->_logRequests($requests);
        $this->_addRequestsToSentryContext($requests);

        $response = array();
        foreach ($requests as $requestOptions) {
            if ($requestOptions !== NULL) {
                $jsonRequest = new Zend_Json_Server_Request();
                $jsonRequest->setOptions($requestOptions);
                
                $response[] = $exception ?
                   $this->_handleException($jsonRequest, $exception) :
                   $this->_handle($jsonRequest);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Got empty request options: skip request.');
                $response[] = NULL;
            }
        }

        if (! headers_sent()) {
            header('Content-type: application/json');
        }

        try {
            $output = $isBatchedRequest ? '['. implode(',', $response) .']' : $response[0];
            $output = (string) $output;
        } catch (Throwable $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Got non-json response, last json error: ' . json_last_error_msg() . ' ' . get_class($e) . ' ' .
                    $e->getMessage());
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
                    foreach ($response as $r) {
                        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                            . ' response: ' . print_r($r->getResult(), true));
                    }
                }
            }

            // trying to fix this:
            foreach ($response as $r) {
                $result = $r->getResult();
                $this->_jsonClean($result);
                $r->setResult($result);
            }

            try {
                $output = $isBatchedRequest ? '['. implode(',', $response) .']' : $response[0];
                $output = (string) $output;
            } catch (Throwable $e) {
                $exception = new Zend_Server_Exception('Got error during json encode: ' . json_last_error_msg() . ' ' .
                    get_class($e) . ' ' . $e->getMessage());
                $output = $this->_handleException($this->_request, $exception);
            }
        }

        echo $output;
    }

    /**
     * log request in log
     *
     * @param array $requestData
     */
    protected function _logRequests($requestData)
    {
        if (! Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) {
            return;
        }

        $requestData = $this->_stripPasswordsFromRequestData($requestData);

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' is JSON request. rawdata: ' . var_export($requestData, true));
    }

    /**
     * @param array $requestData
     * @return array
     */
    protected function _stripPasswordsFromRequestData($requestData)
    {
        foreach ($requestData as $i => $request) {
            foreach (array('password', 'oldPassword', 'newPassword') as $field) {
                if (isset($requestData[$i]["params"][$field])) {
                    $requestData[$i]["params"][$field] = "*******";
                }
            }
        }
        return $requestData;
    }

    /**
     * add json data to sentry client as extra context
     *
     * @param array $requestData
     */
    protected function _addRequestsToSentryContext($requestData)
    {
        // TODO allow to configure this?
        $sentryClient = Tinebase_Core::getSentry();
        if ($sentryClient) {
            $requestData = $this->_stripPasswordsFromRequestData($requestData);
            $sentryClient->extra_context($requestData);
        }
    }

    /**
     * @param $data
     */
    protected function _jsonClean(&$data)
    {
        if (is_null($data) || is_int($data)) {
            // just return
        } elseif (is_string($data)) {
            $data = @mb_convert_encoding($data, 'utf8', 'utf8');
        } elseif (is_array($data)) {
            foreach($data as &$val) {
                $this->_jsonClean($val);
            }
            unset($val);
        } /*elseif (is_object($data)) {
            // bad, lets just hope for the best
        } else {
            // bad, lets just hope for the best
        }*/
    }
    
    /**
     * get JSON from cache or new instance
     * 
     * @param array $classes for Zend_Cache_Frontend_File
     * @return Zend_Json_Server
     */
    protected static function _getServer($classes = null)
    {
        // setup cache if available and we are in production mode
        if (
            is_array($classes)
            && Tinebase_Core::getCache()
        ) {
            $masterFiles = array();
            
            $dirname = dirname(__FILE__) . '/../../';
            foreach ($classes as $class => $namespace) {
                $masterFiles[] = $dirname . str_replace('_', '/', $class) . '.php';
            }
            
            try {
                $cache = new Zend_Cache_Frontend_File(array(
                    'master_files'              => $masterFiles,
                    'lifetime'                  => null,
                    'automatic_serialization'   => true,  // turn that off for more speed
                    'automatic_cleaning_factor' => 0,     // no garbage collection as this is done by a scheduler task
                    'write_control'             => false, // don't read cache entry after it got written
                    'logging'                   => Tinebase_Core::getCache()->getOption('logging'),
                    'logger'                    => Tinebase_Core::getCache()->getOption('logger'),
                ));
                $cache->setBackend(Tinebase_Core::getCache()->getBackend());
                $cacheId = Tinebase_Helper::convertCacheId('_handle_' . sha1(Zend_Json_Encoder::encode($classes)) . '_' .
                    (self::userIsRegistered() ? Tinebase_Core::getUser()->getId() : 'anon'));

                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . " Get server from cache");

                $server = $cache->load($cacheId);
                if ($server instanceof Zend_Json_Server) {
                    return $server;
                }
                
            } catch (Zend_Cache_Exception $zce) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . " Failed to create cache. Exception: \n". $zce);
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' setting up json server ...');
        
        $server = new Zend_Json_Server();
        $server->setAutoEmitResponse(false);
        $server->setAutoHandleExceptions(false);
        
        if (is_array($classes)) {
            foreach ($classes as $class => $namespace) {
                try {
                    $server->setClass($class, $namespace);
                } catch (Exception $e) {
                    if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                        . " Failed to add JSON API for '$class' => '$namespace' Exception: \n". $e);
                }
            }
        }

        if (self::userIsRegistered()) {
            $definitions = self::_getModelConfigMethods('Tinebase_Server_Json');
            $server->loadFunctions($definitions);
        }
        
        if (isset($cache)) {
            $lifetime = defined('TINE20_BUILDTYPE') && TINE20_BUILDTYPE !== 'DEVELOPMENT' ? 30 : 3600;
            $cache->save($server, $cacheId, array(), $lifetime);
        }

        return $server;
    }

    /**
     * handler for JSON api requests
     * @todo session expire handling
     * 
     * @param $request
     * @return JSON
     */
    protected function _handle($request, $retries = 0)
    {
        try {
            $method = $request->getMethod();
            Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ .' is JSON request. method: ' . $method);

            if ($method != 'Tinebase.login' && isset($_SERVER['HTTP_X_TINE20_CLIENTASSETHASH']) && $_SERVER['HTTP_X_TINE20_CLIENTASSETHASH'] &&
                $_SERVER['HTTP_X_TINE20_CLIENTASSETHASH'] != Tinebase_Frontend_Http_SinglePageApplication::getAssetHash()) {
                throw new Tinebase_Exception_ClientOutdated();
            }


            $jsonKey = (isset($_SERVER['HTTP_X_TINE20_JSONKEY'])) ? $_SERVER['HTTP_X_TINE20_JSONKEY'] : '';
            $this->_checkJsonKey($method, $jsonKey);
            
            if (empty($method)) {
                // SMD request
                return self::getServiceMap();
            }
            
            $this->_methods[] = $method;

            $classes = self::_getServerClasses();
            $server = self::_getServer($classes);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ .' handle request ...');

            $response = $server->handle($request);
            if ($response->isError()) {
                Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' Got response error: '
                    . print_r($response->getError()->toArray(), true));
            }
            return $response;
            
        } catch (Throwable $exception) {
            if ($retries < 2 && $exception instanceof Zend_Db_Statement_Exception && strpos($exception->getMessage(),
                    'Deadlock found') !== false) {
                Tinebase_TransactionManager::getInstance()->rollBack();
                Tinebase_Exception::log($exception);
                Tinebase_Exception::log(new Tinebase_Exception_Backend('Deadlock found, retrying: ' . $retries));
                return $this->_handle($request, $retries + 1);
            }
            return $this->_handleException($request, $exception);
        }
    }
    
    /**
     * handle exceptions
     * 
     * @param Zend_Json_Server_Request_Http $request
     * @param Throwable $exception
     * @return Zend_Json_Server_Response
     */
    protected function _handleException($request, $exception)
    {
        $suppressTrace = Tinebase_Core::getConfig()->suppressExceptionTraces;
        if ($exception instanceof Tinebase_Exception_ProgramFlow) {
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . get_class($exception) . ' -> ' .
                $exception->getMessage());
        } else {
            Tinebase_Core::getLogger()->err(__METHOD__ . '::' . __LINE__ . ' ' . get_class($exception) . ' -> ' .
                $exception->getMessage());
            Tinebase_Exception::log($exception, $suppressTrace);
        }
        
        $exceptionData = method_exists($exception, 'toArray')? $exception->toArray() : array();
        $exceptionData['message'] = htmlentities($exception->getMessage(), ENT_COMPAT, 'UTF-8');
        $exceptionData['code']    = $exception->getCode();
        
        if ($exception instanceof Tinebase_Exception) {
            $exceptionData['appName'] = $exception->getAppName();
            $exceptionData['title'] = $exception->getTitle();
        }

        if ($suppressTrace !== TRUE) {
            $exceptionData['trace'] = Tinebase_Exception::getTraceAsArray($exception);
        }


        $server = self::_getServer();
        $server->fault($exceptionData['message'], $exceptionData['code'], $exceptionData);
        
        $response = $server->getResponse();
        if (null !== ($id = $request->getId())) {
            $response->setId($id);
        }
        if (null !== ($version = $request->getVersion())) {
            $response->setVersion($version);
        }
    
        return $response;
    }
    
    /**
     * return service map
     * 
     * @return Zend_Json_Server_Smd
     */
    public static function getServiceMap()
    {
        $classes = self::_getServerClasses();
        $server = self::_getServer($classes);
        
        $server->setTarget('index.php')
               ->setEnvelope(Zend_Json_Server_Smd::ENV_JSONRPC_2);
            
        $smd = $server->getServiceMap();

        return $smd;
    }

    /**
     * get frontend classes for json server
     *
     * @return array
     */
    protected static function _getServerClasses()
    {
        $classes = array();

        $classes['Tinebase_Frontend_Json'] = 'Tinebase';

        if (self::userIsRegistered()) {
            $classes['Tinebase_Frontend_Json_Container'] = 'Tinebase_Container';
            $classes['Tinebase_Frontend_Json_PersistentFilter'] = 'Tinebase_PersistentFilter';
            $classes['Tinebase_Frontend_Json_AreaLock'] = 'Tinebase_AreaLock';

            $userApplications = Tinebase_Core::getUser()->getApplications(TRUE);

            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(
                __METHOD__ . '::' . __LINE__ .' fetching app json classes');

            foreach ($userApplications as $application) {
                $jsonAppName = $application->name . '_Frontend_Json';
                if (class_exists($jsonAppName)) {
                    $classes[$jsonAppName] = $application->name;
                }
            }
        }

        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__
            . ' Got frontend classes: ' . print_r($classes, true));

        return $classes;
    }

    /**
     * check json key
     *
     * @param string $method
     * @param string $jsonKey
     */
    protected function _checkJsonKey($method, $jsonKey)
    {
        $anonymnousMethods = array(
            '', //empty method
            'Tinebase.getRegistryData',
            'Tinebase.getAllRegistryData',
            'Tinebase.authenticate',
            'Tinebase.login',
            'Tinebase.getAvailableTranslations',
            'Tinebase.getTranslations',
            'Tinebase.setLocale'
        );
        
        // check json key for all methods but some exceptions
        if ( !(in_array($method, $anonymnousMethods)) && ($jsonKey !== Tinebase_Core::get('jsonKey') || !self::userIsRegistered())) {

            $request = Tinebase_Core::getRequest();
            if (!self::userIsRegistered()) {
                Tinebase_Core::getLogger()->INFO(__METHOD__ . '::' . __LINE__ .
                    ' Attempt to request a privileged Json-API method (' . $method . ') without authorisation from "' .
                    $request->getRemoteAddress() . '". (session timeout?)');
                Tinebase_Core::getLogger()->DEBUG(__METHOD__ . '::' . __LINE__ .
                    ' unauthorised request details: ' .
                    print_r($request->getServer()->toArray(), true));
            } else {
                Tinebase_Core::getLogger()->WARN(__METHOD__ . '::' . __LINE__ . ' Fatal: got wrong json key! (' . $jsonKey . ') Possible CSRF attempt!' .
                    ' affected account: ' . print_r(Tinebase_Core::getUser()->toArray(), true) .
                    ' request: ' . print_r($request->getServer()->toArray(), true)
                );
            }
            
            throw new Tinebase_Exception_AccessDenied('Not Authorised', 401);
        }
    }
    
    /**
    * returns request method
    *
    * @return string|NULL
    */
    public function getRequestMethod()
    {
        return (! empty($this->_methods)) ? implode('|', $this->_methods) : NULL;
    }

    public static function userIsRegistered()
    {
        return Tinebase_Core::isRegistered(Tinebase_Core::USER)
            && is_object(Tinebase_Core::getUser());
    }

    public static function exposeApi($config)
    {
        return $config && $config->exposeJsonApi;
    }

    protected static function _getCommonApiMethods($simpleModelName)
    {
        $commonJsonApiMethods = array(
            'get' => array(
                'params' => array(
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'string',
                        'name' => 'id',
                    )),
                ),
                'help'   => 'get one ' . $simpleModelName . ' identified by $id',
                'plural' => false,
            ),
            'search' => array(
                'params' => array(
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'filter',
                    )),
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'paging',
                    )),
                ),
                'help'   => 'Search for ' . $simpleModelName . 's matching given arguments',
                'plural' => true,
            ),
            'save' => array(
                'params' => array(
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'recordData',
                    )),
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'boolean',
                        'name' => 'duplicateCheck',
                        'optional' => true,
                    )),
                ),
                'help'   => 'Save ' . $simpleModelName . '',
                'plural' => false,
            ),
            'delete' => array(
                'params' => array(
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'ids',
                    )),
                ),
                'help'   => 'Delete multiple ' . $simpleModelName . 's',
                'plural' => true,
            ),
            /**
             * @param string $tempFileId to import
             * @param string $importDefinitionId
             * @param array $options additional import options
             * @param array $clientRecordData
             */
            'import' => array(
                'params' => array(
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'string',
                        'name' => 'tempFileId',
                    )),
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'string',
                        'name' => 'definitionId',
                    )),
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'importOptions',
                        'optional' => true,
                    )),
                    new Zend_Server_Method_Parameter(array(
                        'type' => 'array',
                        'name' => 'clientRecordData',
                        'optional' => true,
                    )),
                ),
                'help'   => 'Import ' . $simpleModelName . 's',
                'plural' => true,
            ),
        );

        return $commonJsonApiMethods;
    }

    protected static function _getFrontend($application)
    {
        $appJsonFrontendClass = $application->name . '_Frontend_Json';
        if (class_exists($appJsonFrontendClass)) {
            $object = new $appJsonFrontendClass();
        } else {
            $object = new Tinebase_Frontend_Json_Generic($application->name);
        }

        return $object;
    }
}
