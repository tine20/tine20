<?php
/**
 * Tine 2.0
 *
 * @package     php_client
 * @subpackage  Tinebase
 * @license     New BSD License
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * Class all Connections / Request to remote Tine 2.0 installation are handled via
 * 
 * @todo $this->_user: array -> model
 *
 */
class Tinebase_Connection
{
    /**
     * holds config of connection
     *
     * @var array
     */
    protected $_config = array();
    
    /**
     * status of debug modus
     *
     * @var bool
     */
    protected $_debugEnabled = false;
    
    /**
     * Json key of the current session
     *
     * @var string
     */
    public  $jsonKey = NULL;
    
    /**
     * Account data for the current users session
     *
     * @var array
     */
    public $user = array();
    
    /**
     * @var Zend_Http_Client
     */
    protected $_httpClient = NULL;
    
    /**
     * holds array of selfs (one for each connection)
     * 
     * @var Tinebase_Connection
     */
    private static $_instance = array();
    
    
    /**
     * singleton per url and username
     *
     * @return Tinebase_Connection
     */
    public static function getInstance($_url=NULL, $_username='', $_password='') 
    {
        // return connection if we have _one_
        if (! $_url) {
            $urls = array_keys(self::$_instance);
            if (count($urls) === 1) {
                $users = array_keys(self::$_instance[$urls[0]]);
                if (count($users) === 1) {
                    return self::$_instance[$urls[0]][$users[0]];
                }
            }
            throw new Exception('instance not specified');
        }
        
        if (! isset(self::$_instance[$_url]) || ! isset(self::$_instance[$_url][$_username])) {
            self::$_instance[$_url][$_username] = new Tinebase_Connection($_url, $_username, $_password);
        }
        return self::$_instance[$_url][$_username];
    }
    
    /**
     * @see Zend_Http_Client
     */
    private function __construct($_url, $_username, $_password)
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
        $this->_httpClient->setHeaders('X-Requested-With', 'XMLHttpRequest');
        $this->_httpClient->setHeaders('X-Tine20-Request-Type', 'JSON');
    }
    
    /**
     * returns the authenticated user
     * 
     * @return array()
     */
    public function getUser()
    {
        return $this->user;
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
     * sets config values
     *
     * @param  string $_configName
     * @param  mixed  $_configValue
     * @return void
     */
    public function __set($_configName, $_configValue)
    {
        $this->_config[$_configName] = $_configValue;
    }
    
    /**
     * gets config value
     *
     * @param  string $_configName
     * @return mixed
     */
    public function __get($_configName)
    {
        return $this->_config[$_configName];
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
                    'jsonKey'    => $this->jsonKey
                ));
                $this->_httpClient->setHeaders('X-Requested-With', 'XMLHttpRequest');
                $this->_httpClient->setHeaders('X-Tine20-Request-Type', 'JSON');
                break;
            case 'GET' :
                $this->_httpClient->setParameterGet(array(
                    'jsonKey'    => $this->jsonKey
                ));
                $this->_httpClient->setHeaders('X-Requested-With', '');
                $this->_httpClient->setHeaders('X-Tine20-Request-Type', 'HTTP');
                break;
        }
        return $this->_httpClient->request($method);
    }
    
    /**
     * enable/disable debugging
     *
     * @param  bool $_status
     * @return void
     */
    public function setDebugEnabled($_status)
    {
        $this->_debugEnabled = (bool)$_status;
    }
}