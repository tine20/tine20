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
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter_Http
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Abstract HTTP Authentication Adapter
 *
 * @category   Zend
 * @package    Zend_Auth
 * @subpackage Zend_Auth_Adapter_Http
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Auth_Adapter_Http_Abstract
{
    /**
     * Reference to the HTTP Request object
     *
     * @var Zend_Controller_Request_Http
     */
    protected $_request;

    /**
     * Reference to the HTTP Response object
     *
     * @var Zend_Controller_Response_Http
     */
    protected $_response;
    
    /**
     * Whether or not to do Proxy Authentication instead of origin server
     * authentication (send 407's instead of 401's). Off by default.
     *
     * @var boolean
     */
    protected $_imaProxy;
    
    /**
     * Setter for the Request object
     *
     * @param  Zend_Controller_Request_Http $request
     * @return Zend_Auth_Adapter_Http Provides a fluent interface
     */
    public function setRequest(Zend_Controller_Request_Http $request)
    {
        $this->_request = $request;

        return $this;
    }

    /**
     * Getter for the Request object
     *
     * @return Zend_Controller_Request_Http
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Setter for the Response object
     *
     * @param  Zend_Controller_Response_Http $response
     * @return Zend_Auth_Adapter_Http Provides a fluent interface
     */
    public function setResponse(Zend_Controller_Response_Http $response)
    {
        $this->_response = $response;

        return $this;
    }

    /**
     * Getter for the Response object
     *
     * @return Zend_Controller_Response_Http
     */
    public function getResponse()
    {
        return $this->_response;
    }
    
    /**
     * Setter for the _resolver property
     *
     * @param  Zend_Auth_Adapter_Http_Resolver_Interface $resolver
     * @return Zend_Auth_Adapter_Http Provides a fluent interface
     */
    public function setResolver($resolver)
    {
        $this->_resolver = $resolver;

        return $this;
    }

    /**
     * Getter for the _resolver property
     *
     * @return Zend_Auth_Adapter_Http_Resolver_Interface
     */
    public function getResolver()
    {
        return $this->_resolver;
    }
    /**
     * Challenge Client
     *
     * Sets a 401 or 407 Unauthorized response code, and creates the
     * appropriate Authenticate header(s) to prompt for credentials.
     *
     * @return Zend_Auth_Result Always returns a non-identity Auth result
     */
    protected function _challengeClient()
    {
        if ($this->_imaProxy) {
            $statusCode = 407;
            $headerName = 'Proxy-Authenticate';
        } else {
            $statusCode = 401;
            $headerName = 'WWW-Authenticate';
        }

        $this->_response->setHttpResponseCode($statusCode);

        $this->_response->setHeader($headerName, $this->_getAuthHeader());
        return new Zend_Auth_Result(
            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
            array(),
            array('Invalid or absent credentials; challenging client')
        );
    }
}