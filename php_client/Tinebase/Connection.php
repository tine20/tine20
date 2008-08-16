<?php
/**
 * Tine 2.0 PHP HTTP Client
 * 
 * @package     Tinebase
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Class all Connections / Request to remote Tine 2.0 installation are handled via
 * 
 * @todo    $this->_user: array -> model
 * @todo    resetParameters for each request
 * @package Tinebase
 */
class Tinebase_Connection
{
    /**
     * @var bool
     */
    public $debugEnabled = false;
    
    /**
     * @var array config of this connection
     */
    protected $_config = array();
    
    /**
     * Json key of the current session
     *
     * @var string
     */
    protected  $_jsonKey = NULL;
    
    /**
     * Account data for the current users session
     *
     * @var array
     */
    protected $_user = array();
    
    /**
     * @var Zend_Http_Client
     */
    protected $_httpClient = NULL;
    
    /**
     * @var Tinebase_Connection
     */
    protected static $_defaultConnection = NULL;
    
    /**
     * creates a new connection
     */
    public function __construct($_url, $_username, $_password)
    {
        $this->_config = array(
            'url'       => $_url,
            'username'  => $_username,
            'password'  => $_password,
            'useragent' => 'Tine 2.0 remote client (rv: 0.2)',
            'keepalive' => true
        );

        $this->_httpClient = new Zend_Http_Client($_url, $this->_config);
        $this->_httpClient->setCookieJar();
    }

    
    /**
     * sets the default connection
     *
     * @param  Tinebase_Connection $_connection
     * @return Tinebase_Connection
     */
    public static final function setDefaultConnection(Tinebase_Connection $_connection)
    {
        self::$_defaultConnection = $_connection;
        return self::$_defaultConnection;
    }
    
    /**
     * get the default connection
     *
     * @return Tinebase_Connection
     */
    public static final function getDefaultConnection()
    {
        return self::$_defaultConnection;
    }
    
    /**
     * returns the authenticated user
     * 
     * @return array()
     */
    public function getUser()
    {
        return $this->_user;
    }
    
    /**
     * route function calls to Http_Client
     *
     * @param  string $_functionName
     * @param  array  $_arguments
     * @return mixed
     */
    public function __call($_functionName, $_arguments)
    {
        return call_user_func_array(array($this->_httpClient, $_functionName), $_arguments);
    }
    
    /**
     * Send the HTTP request and return an HTTP response object
     *
     * @todo route all requests throug here??
     * @param string $method
     */
    public function request($method)
    {
        switch ($method) {
            case 'POST' :
                $this->_httpClient->setParameterPost(array(
                    'jsonKey'    => $this->_jsonKey
                ));
                $this->_httpClient->setHeaders('X-Requested-With', 'XMLHttpRequest');
                $this->_httpClient->setHeaders('X-Tine20-Request-Type', 'JSON');
                break;
            case 'GET' :
                $this->_httpClient->setParameterGet(array(
                    'jsonKey'    => $this->_jsonKey
                ));
                $this->_httpClient->setHeaders('X-Requested-With', '');
                $this->_httpClient->setHeaders('X-Tine20-Request-Type', 'HTTP');
                break;
        }
        return $this->_httpClient->request($method);
    }
    
    /**
     * login to remote Tine 2.0 installation
     *
     * @return void
     */
    public function login()
    {
        $this->_httpClient->setParameterPost(array(
            'username'  => $this->_config['username'],
            'password'  => $this->_config['password'],
            'method'    => 'Tinebase.login'
        ));
        
        $response = $this->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $this->_httpClient->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('login failed');
        }
                
        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $this->_jsonKey = $responseData['jsonKey'];
        $this->_user = $responseData['account'];
    }
    
    /**
     * logout from remote Tine 2.0 installation
     * 
     * @return void
     */
    public function logout()
    {
        $this->_httpClient->setParameterPost(array(
            'method'   => 'Tinebase.logout'
        ));
        
        $response = $this->request('POST');

        if($this->debugEnabled === true) {
            var_dump( $this->_httpClient->getLastRequest());
            var_dump( $response );
        }

        if(!$response->isSuccessful()) {
            throw new Exception('logout failed');
        }

        $responseData = Zend_Json::decode($response->getBody());
        
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
        
        $this->_jsonKey = NULL;
        $this->_user = array();
    }
}