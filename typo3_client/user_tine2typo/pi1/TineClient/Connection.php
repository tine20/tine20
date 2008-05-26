<?php
/**
 * Tine 2.0
 *
 * @package     Egwbase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Connection.php 1314 2008-03-23 21:34:20Z lkneschke $
 */
require_once( PATH_site . 'typo3conf/ext/user_tine2typo/pi1/Zend/Http/Client.php');
require_once( PATH_site . 'typo3conf/ext/user_tine2typo/pi1/Zend/Json.php');
class TineClient_Connection extends Zend_Http_Client
{
    /**
     * status of debug modus
     *
     * @var bool
     */
    protected $debugEnabled = false;
	public $jsonKey = '';
   
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
    
    public function login($_username, $_password)
    {
        $this->setParameterPost(array(
            'username'  => $_username,
            'password'  => $_password,
            'method'    => 'Tinebase.login',
			'remote'	=> true
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
        $this->jsonKey = $responseData['jsonKey'];
        if($this->debugEnabled === true) {
            var_dump($responseData);
        }
    }
    
    public function logout()
    {
        $this->setParameterPost(array(
            'method'   => 'Tinebase.logout',
			'jsonKey'	=> $this->jsonKey
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
    }
    
    public function setDebugEnabled($_status)
    {
        $this->debugEnabled = (bool)$_status;
    }
}