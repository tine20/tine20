<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

class TineClient_Connection extends Zend_Http_Client
{
    /**
     * status of debug modus
     *
     * @var bool
     */
    protected $debugEnabled = false;
    
    /**
     * Json key of the current session
     *
     * @var string
     */
    protected  $_jsonKey = NULL;
    
    /**
     * Account data for the current session
     *
     * @var array
     */
    public $account = array();
    /**
     * @see Zend_Http_Client
     */
    public function __construct($_uri, array $_config = array())
    {
        $_config['useragent'] = 'Tine 2.0 remote client (rv: 0.1)';
        $_config['keepalive'] = TRUE;
        
        parent::__construct($_uri, $_config);
        
        $this->setCookieJar();
        $this->setHeaders('X-Requested-With', 'XMLHttpRequest');
    }
    
    /**
     * Send the HTTP request and return an HTTP response object
     *
     * @param string $method
     */
    public function request($method)
    {
         $this->setParameterPost(array(
            'jsonKey'    => $this->_jsonKey
        ));
        return parent::request($method);
    }
    
    public function login($_username, $_password)
    {
        $this->setParameterPost(array(
            'username'  => $_username,
            'password'  => $_password,
            'method'    => 'Tinebase.login'
        ));
        
        $response = $this->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
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
        $this->account = $responseData['account'];
    }
    
    public function logout()
    {
        $this->setParameterPost(array(
            'method'   => 'Tinebase.logout'
        ));
        
        $response = $this->request('POST');
        
        if($this->debugEnabled === true) {
            var_dump( $this->getLastRequest());
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
        $this->account = array();
    }
    
    public function setDebugEnabled($_status)
    {
        $this->debugEnabled = (bool)$_status;
    }
}