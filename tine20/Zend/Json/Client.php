<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_XmlRpc
 * @subpackage Client
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * For handling the HTTP connection to the JSON-RPC service
 * @see Zend_Http_Client
 */
require_once 'Zend/Http/Client.php';

/**
 * Enables object chaining for calling namespaced JSON-RPC methods.
 * @see Zend_Json_Client_ServerProxy
 */
require_once 'Zend/Json/Client/ServerProxy.php';

/**
 * Introspects remote servers using the JSON-RPC de facto system.* methods
 * @see Zend_Json_Client_ServerIntrospection
 */
require_once 'Zend/Json/Client/ServerIntrospection.php';

/**
 * Json-RPC Request
 * @see Zend_Json_Server_Request
 */
require_once 'Zend/Json/Server/Request.php';

/**
 * Json-RPC Response
 * @see Zend_Json_Client_Response
 */
require_once 'Zend/Json/Client/Response.php';

/**
 * Zend Version
 * @see Zend_Version
 */
require_once 'Zend/Json/Decoder.php';

/**
 * Zend Version
 * @see Zend_Version
 */
require_once 'Zend/Json/Encoder.php';

/**
 * Zend Version
 * @see Zend_Version
 */
require_once 'Zend/Version.php';

/**
 * An JSON-RPC client implementation
 *
 * @category   Zend
 * @package    Zend_Json
 * @subpackage Client
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Json_Client
{
    /**
     * Full address of the JSON-RPC service
     * @var string
     * @example http://json.example.com/index.php
     */
    protected $_serverAddress;

    /**
     * HTTP Client to use for requests
     * @var Zend_Http_Client
     */
    protected $_httpClient = null;

    /**
     * Introspection object
     * @var Zend_Http_Client_Introspector
     */
    protected $_introspector = null;

    /**
     * Request of the last method call
     * @var Zend_XmlRpc_Request
     */
    protected $_lastRequest = null;

    /**
     * Response received from the last method call
     * @var Zend_XmlRpc_Response
     */
    protected $_lastResponse = null;

    /**
     * Proxy object for more convenient method calls
     * @var array of Zend_XmlRpc_Client_ServerProxy
     */
    protected $_proxyCache = array();

    /**
     * Flag for skipping system lookup
     * @var bool
     */
    protected $_skipSystemLookup = false;
    
    /**
     * Create a new XML-RPC client to a remote server
     *
     * @param  string $server      Full address of the XML-RPC service
     *                             (e.g. http://json.example.com/index.php)
     * @param  Zend_Http_Client $httpClient HTTP Client to use for requests
     * @return void
     */
    public function __construct($server, Zend_Http_Client $httpClient = null)
    {
        if ($httpClient === null) {
            $this->_httpClient = new Zend_Http_Client();
        } else {
            $this->_httpClient = $httpClient;
        }

        $this->_introspector  = new Zend_Json_Client_ServerIntrospection($this);
        $this->_serverAddress = $server;
    }


    /**
     * Sets the HTTP client object to use for connecting the XML-RPC server.
     *
     * @param  Zend_Http_Client $httpClient
     * @return Zend_Http_Client
     */
    public function setHttpClient(Zend_Http_Client $httpClient)
    {
        return $this->_httpClient = $httpClient;
    }


    /**
     * Gets the HTTP client object.
     *
     * @return Zend_Http_Client
     */
    public function getHttpClient()
    {
        return $this->_httpClient;
    }


    /**
     * Sets the object used to introspect remote servers
     *
     * @param  Zend_Json_Client_ServerIntrospection
     * @return Zend_Json_Client_ServerIntrospection
     */
    public function setIntrospector(Zend_Json_Client_ServerIntrospection $introspector)
    {
        return $this->_introspector = $introspector;
    }


    /**
     * Gets the introspection object.
     *
     * @return Zend_Json_Client_ServerIntrospection
     */
    public function getIntrospector()
    {
        return $this->_introspector;
    }


   /**
     * The request of the last method call
     *
     * @return Zend_Json_Server_Request
     */
    public function getLastRequest()
    {
        return $this->_lastRequest;
    }


    /**
     * The response received from the last method call
     *
     * @return Zend_Json_Client_Response
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }


    /**
     * Returns a proxy object for more convenient method calls
     *
     * @param $namespace  Namespace to proxy or empty string for none
     * @return Zend_Json_Client_ServerProxy
     */
    public function getProxy($namespace = '')
    {
        if (empty($this->_proxyCache[$namespace])) {
            $proxy = new Zend_Json_Client_ServerProxy($this, $namespace);
            $this->_proxyCache[$namespace] = $proxy;
        }
        return $this->_proxyCache[$namespace];
    }

    /**
     * Set skip system lookup flag
     *
     * @param  bool $flag
     * @return Zend_XmlRpc_Client
     */
    public function setSkipSystemLookup($flag = true)
    {
        $this->_skipSystemLookup = (bool) $flag;
        return $this;
    }

    /**
     * Skip system lookup when determining if parameter should be array or struct?
     *
     * @return bool
     */
    public function skipSystemLookup()
    {
        return $this->_skipSystemLookup;
    }

    /**
     * Perform an JSON-RPC request and return a response.
     *
     * @param Zend_Json_Server_Request $request
     * @param null|Zend_Json_Client_Response $response
     * @return void
     * @throws Zend_Json_Client_HttpException
     */
    public function doRequest($request, $response = null)
    {
        $this->_lastRequest = $request;

        #iconv_set_encoding('input_encoding', 'UTF-8');
        #iconv_set_encoding('output_encoding', 'UTF-8');
        #iconv_set_encoding('internal_encoding', 'UTF-8');

        $http = $this->getHttpClient();

        $http->setUri($this->_serverAddress);
        $http->setHeaders(array(
            'Content-Type: application/json-rpc; charset=utf-8',
            'User-Agent: Zend_Json_Client/' . Zend_Version::VERSION,
            'Accept: application/json-rpc',
        ));

        $json = $this->_lastRequest->__toString();
        
        $http->setRawData($json);
        $httpResponse = $http->request(Zend_Http_Client::POST);

        if (! $httpResponse->isSuccessful()) {
            /**
             * Exception thrown when an HTTP error occurs
             * @see Zend_XmlRpc_Client_HttpException
             */
            require_once 'Zend/Json/Client/HttpException.php';
            throw new Zend_Json_Client_HttpException($httpResponse->getMessage(), $httpResponse->getStatus());
        }

        if ($response === null) {
            $response = new Zend_Json_Client_Response();
        }
        $this->_lastResponse = $response;
        
        $this->_lastResponse->loadJson($httpResponse->getBody());
    }

    /**
     * Send an JSON-RPC request to the service (for a specific method)
     *
     * @param  string $method Name of the method we want to call
     * @param  array $params Array of parameters for the method
     * @return mixed
     * @throws Zend_Json_Client_FaultException
     */
    public function call($method, $params=array())
    {
        if (!$this->skipSystemLookup() && !empty($method)) {
            $signature = $this->getIntrospector()->getMethodSignature($method);
            
            foreach ($params as $key => $param) {
                if(is_int($key)) {
                    // positional parameters
                    continue;
                }
                
                $keyFound = false;

                foreach($signature["parameters"] as $parameter) {
                    if($parameter['name'] == "$key") {
                        $keyFound = true;
                    }
                }
                
                if($keyFound !== true) {
                    throw new Zend_Json_Client_FaultException("named parameter $key not found in SMD");
                }
            }
        }

        $request = new Zend_Json_Server_Request();
        $request->setVersion('2.0');
        $request->setId(1);
        $request->setMethod($method);
        $request->setParams($params);

        $this->doRequest($request);

        if ($this->_lastResponse->isError()) {
            $fault = $this->_lastResponse->getError();
            /**
             * Exception thrown when an JSON-RPC fault is returned
             * @see Zend_Json_Client_FaultException
             */
            require_once 'Zend/Json/Client/FaultException.php';
            throw new Zend_Json_Client_FaultException($fault->getMessage(), $fault->getCode());
        }

        return $this->_lastResponse->getResult();
    }
}
