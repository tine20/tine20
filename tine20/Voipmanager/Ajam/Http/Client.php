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
 * @package    Zend_Http
 * @subpackage Client
 * @version    $Id$
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Zend_Http_Client is an implemetation of an HTTP client in PHP. The client
 * supports basic features like sending different HTTP requests and handling
 * redirections, as well as more advanced features like proxy settings, HTTP
 * authentication and cookie persistance (using a Zend_Http_CookieJar object)
 *
 * @todo Implement proxy settings
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Client
 * @throws     Zend_Http_Client_Exception
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Asterisk_Ajam_Http_Client extends Zend_Http_Client
{
    /**
     * Send the HTTP request and return an HTTP response object
     *
     * @param string $method
     * @return Zend_Http_Response
     */
    public function request($method = null)
    {
        if (! $this->uri instanceof Zend_Uri_Http) {
            require_once 'Zend/Http/Client/Exception.php';
            throw new Zend_Http_Client_Exception('No valid URI has been passed to the client');
        }

        if ($method) $this->setMethod($method);
        $this->redirectCounter = 0;
        $response = null;

        // Make sure the adapter is loaded
        if ($this->adapter == null) $this->setAdapter($this->config['adapter']);

        // Send the first request. If redirected, continue.
        do {
            // Clone the URI and add the additional GET parameters to it
            $uri = clone $this->uri;
            if (! empty($this->paramsGet)) {
                $query = $uri->getQuery();
                foreach($this->paramsGet as $key => $parameter) {
                    if (! empty($query)) $query .= '&';
                    $query .= $key . '=' . rawurlencode($parameter);
                }
                #   if (! empty($query)) $query .= '&';
                #$query .= http_build_query($this->paramsGet, null, '&');
                $uri->setQuery($query);
            }

            $body = $this->prepare_body();
            $headers = $this->prepare_headers();

            // Open the connection, send the request and read the response
            $this->adapter->connect($uri->getHost(), $uri->getPort(),
                ($uri->getScheme() == 'https' ? true : false));

            $this->last_request = $this->adapter->write($this->method,
                $uri, $this->config['httpversion'], $headers, $body);

            $response = $this->adapter->read();
            if (! $response) {
                require_once 'Zend/Http/Client/Exception.php';
                throw new Zend_Http_Client_Exception('Unable to read response, or response is empty');
            }

            $response = Zend_Http_Response::fromString($response);
            if ($this->config['storeresponse']) $this->last_response = $response;

            // Load cookies into cookie jar
            if (isset($this->cookiejar)) $this->cookiejar->addCookiesFromResponse($response, $uri);

            // If we got redirected, look for the Location header
            if ($response->isRedirect() && ($location = $response->getHeader('location'))) {

                // Check whether we send the exact same request again, or drop the parameters
                // and send a GET request
                if ($response->getStatus() == 303 ||
                   ((! $this->config['strictredirects']) && ($response->getStatus() == 302 ||
                       $response->getStatus() == 301))) {

                    $this->resetParameters();
                    $this->setMethod(self::GET);
                }

                // If we got a well formed absolute URI
                if (Zend_Uri_Http::check($location)) {
                    $this->setHeaders('host', null);
                    $this->setUri($location);

                } else {

                    // Split into path and query and set the query
                    if (strpos($location, '?') !== false) {
                        list($location, $query) = explode('?', $location, 2);
                    } else {
                        $query = '';
                    }
                    $this->uri->setQuery($query);

                    // Else, if we got just an absolute path, set it
                    if(strpos($location, '/') === 0) {
                        $this->uri->setPath($location);

                        // Else, assume we have a relative path
                    } else {
                        // Get the current path directory, removing any trailing slashes
                        $path = $this->uri->getPath();
                        $path = rtrim(substr($path, 0, strrpos($path, '/')), "/");
                        $this->uri->setPath($path . '/' . $location);
                    }
                }
                ++$this->redirectCounter;

            } else {
                // If we didn't get any location, stop redirecting
                break;
            }

        } while ($this->redirectCounter < $this->config['maxredirects']);

        return $response;
    }
}
