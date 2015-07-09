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
 */

/**
 * @category   Zend
 * @package    Zend_Service
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @var Zend_Cache_Core
     */
    protected $_cache;
    
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

    public function setCache(Zend_Cache_Core $_cache)
    {
        $this->_cache = $_cache;
    }

    /**
     * login to Tine 2.0 installation 
     * 
     * @param string $loginname
     * @param string $password
     * @return array decoded JSON responce
     * @throws Zend_Service_Exception
     */
    public function login($loginname, $password)
    {
        $this->setSkipSystemLookup(true);
        
        $response = $this->call('Tinebase.login', array(
            'username'  => $loginname,
            'password'  => $password
        ));
        
        if($response['success'] !== true) {
            throw new Zend_Service_Exception($response['errorMessage']);
        }
        
        $this->_jsonKey = $response['jsonKey'];
        $this->_account = $response['account'];
        $this->getHttpClient()->setHeaders('X-Tine20-JsonKey', $this->_jsonKey);
        
        if($this->_cache instanceof Zend_Cache_Core) {
            if($this->_cache->test('tine20PrivateSMD')) {
                $smd = $this->_cache->load('tine20PrivateSMD');
                $this->getIntrospector()->setSMD($smd);
            } else {
                $smd = $this->getIntrospector()->fetchSMD();
                $this->_cache->save($smd, 'tine20PrivateSMD');
            }
        } else {
            $this->getIntrospector()->fetchSMD();
        }
        
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
