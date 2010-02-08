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
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
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
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Service_Tine20 extends Zend_Json_Client
{
    /**
     * @var string json key required to send with any request
     */
    protected $_jsonKey;
    
    /**
     * the url of the Tine 2.0 installation
     * 
     * @var string (for example http://demo.tine20.org/index.php)
     */
    protected $_url;
    
    /**
     * @var array stores information about the account logged in
     */
    protected $_account;
    
    /**
     * constructor for Zend_Service_Tine20
     * @param string           $url         the url of the Tine 2.0 installation
     * @param Zend_Http_Client $httpClient
     * @return void
     */
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

    /**
     * login to Tine 2.0 installation 
     * 
     * @param string $loginname
     * @param string $password
     * @return array decoded JSON responce
     * @thorws Zend_Service_Exception
     */
    public function login($loginname, $password)
    {
        $this->setSkipSystemLookup(true);
        
        $response = $this->call('Tinebase.login', array(
            'username'  => $loginname,
            'password'  => $password
        ));
        
        if($response['success'] !== true) {
            throw new Zend_Service_Exception($respose['errorMessage']);
        }
        
        $this->_jsonKey = $response['jsonKey'];
        $this->_account = $response['account'];
        $this->getHttpClient()->setHeaders('X-Tine20-JsonKey', $this->_jsonKey);
        
        $this->getIntrospector()->fetchSMD();
        
        $this->setSkipSystemLookup(false);
        
        return $response;
    }

    /**
     * logout from Tine 2.0 installation
     * 
     * @return array decoded JSON responce
     */
    public function logout()
    {
        $this->setSkipSystemLookup(true);
        
        $response = $this->call('Tinebase.logout');
        
        $this->_jsonKey = null;
        $this->_account = null;
        
        // unset header
        $this->getHttpClient()->setHeaders('X-Tine20-JsonKey');
        
        $this->setSkipSystemLookup(false);
        
        return $response;
    }
    
}
