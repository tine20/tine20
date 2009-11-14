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
 * @package    Zend_Service
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Zend_Json_Client
 */
require_once 'Zend/Json/Client.php';

/**
 * @category   Zend
 * @package    Zend_Service
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Tine20 extends Zend_Json_Client
{
    protected $_jsonKey;
    
    public function __construct($url, $httpClient = null)
    {
        $this->_url = $url;
        
        if(!$httpClient instanceof Zend_Http_Client) {
            $httpClient = new Zend_Http_Client();
        }
        
        if(!$httpClient->getCookieJar() instanceof Zend_Http_CookieJar) {
            $httpClient->setCookieJar();
        }
        
        parent::__construct($url, $httpClient);        
    }    
    
    public function login($loginname, $password)
    {
        $response = $this->call('Tinebase.login', array(
            'username'  => $loginname,
            'password'  => $password
        ));
        
        if($response['success'] !== true) {
            throw new Zend_Service_Exception($respose['errorMessage']);
        }
        
        $this->_jsonKey = $response['jsonKey'];
        $this->getHttpClient()->setHeaders('X-Tine20-JsonKey', $this->_jsonKey);
        
        $this->getIntrospector()->fetchSMD();
        
        return $response;
    }
    
    public function logout()
    {
        $response = $this->call('Tinebase.logout');
        
        return $response;
    }
    
}

