<?php
/**
 * Tine Tunnel
 * Low-level HTTP request abstraction. Handles post data, headers and cookies.
 *
 * @package   ExpressoLite\TineTunnel
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\TineTunnel;

use \DateTime;
use ExpressoLite\Exception\RpcException;

class Request
{

    /**
     * HTTP method GET
     */
    const GET = 'GET';

    /**
     * HTTP method POST
     */
    const POST = 'POST';

    /**
     * @var CookieHandler $cookieHandler Object that stores all cookies to
     * be sent in this request. It is usually a TineSession, but may be
     * anything that implements the CookieHandler interface
     */
    private $cookieHandler = null;

    /**
     * @var string $url The URL to which this request will be sent
     */
    private $url = null;

    /**
     * @var string $postFields A string representation of all the post fields
     * associated to this request. ATTENTION: this is NOT an indexed array,
     * but a single string with all post data
     */
    private $postFields = array(); // http://stackoverflow.com/questions/5224790/curl-post-format-for-curlopt-postfields

    /**
     * @var array $headers Simple array of 'Key: value' strings, NOT an
     *associative array with key/value!
     * associated to this request. ATTENTION: this is NOT an indexed array,
     * but a single string with all post data
     */
    private $headers = array();

    /**
     * @var boolean $binaryOutput indicates if the request output is binary.
     * If it is, the response is streamed directly instead of being stored in a variable
     */
    private $binaryOutput = false;

    /**
     * Sets the URL to which the Request will sent
     *
     * @param $url The new URL
     *
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * The Request response may have HTTP headers indicating the
     * presence of cookies. These cookies will be parsed and will
     * be stored by the $cookieHandler (usually, a TineSession object)
     *
     * @param $cookieHandler The new cookie handler
     *
     */
    public function setCookieHandler($cookieHandler)
    {
        $this->cookieHandler = $cookieHandler;
    }

    /**
     * An array of strings containing the header values. These strings
     * should be in the format 'header_name: header_value'.
     *
     * @param $headers Array of strings containing the header values
     *
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * Indicates if the expected response is binary output. In these cases,
     * the response will be outputed directly, and not stored in a variable.
     *
     * @param boolean $binaryOutput Indicates if a binary output is expected
     *
     */
    public function setBinaryOutput($binaryOutput)
    {
        $this->binaryOutput = $binaryOutput;
    }

    /**
     * Sets the content of the section of post fields in the HTTP POST
     *
     * @param string $fields A string with the content of the section of post fields
     * in the HTTP POST
     *
     */
    public function setPostFields($fields)
    {
        $this->postFields = $fields;
    }

    /**
     * Sends the request to the target url.
     *
     * @param string $method the HTTP method (POST or GET)
     *
     * @return The request response.
     *
     */
    public function send($method = self::GET)
    {
        $this->checkMandatoryProperties();

        $curl = curl_init($this->url);
        curl_setopt_array($curl, array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_RETURNTRANSFER => ! $this->binaryOutput,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HEADER => ! $this->binaryOutput,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_COOKIE => $this->getFormattedCookies(),
            CURLOPT_POSTFIELDS => $this->postFields,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_FRESH_CONNECT => true
        ));
        $response = curl_exec($curl);

        if ($response === false) {
            $errMsg = sprintf('cURL failed. #%d: %s', curl_errno($curl), curl_error($curl));
        } else {
            $errMsg = '';
        }

        curl_close($curl);

        if ($errMsg != '') {
            throw new RpcException($errMsg);
        }

        if ($this->binaryOutput) {
            return true;
        } elseif ($this->isImage($response)) {
            return bin2hex(base64_encode(substr($response, strpos($response, "\r\n\r\n") + 4)));
        } else {
            return $this->parseResponse($response);
        }
    }

    /**
     * Sends the request to the target url.
     *
     * @param string $method the HTTP method (POST or GET)
     *
     * @return The request response.
     *
     */
    private function getFormattedCookies()
    {
        if ($this->cookieHandler == null) {
            return '';
        }

        $cookies = $this->cookieHandler->getCookies();
        $vals = array();
        foreach ($cookies as $cookie) {
            if ($this->cookieShouldBeDeleted($cookie)) {
                $this->cookieHandler->deleteCookie($cookie->name);
            } else {
                $vals[] = ($cookie->name . '=' . $cookie->value);
            }
        }
        return implode('; ', $vals);
    }

    /**
     * Checks if the response is an image
     *
     * @param $res the received response
     *
     * @return true if the result is an image, false otherwise
     *
     */
    private function isImage($res)
    {
        $lines = explode("\r\n", $res);

        foreach ($lines as $line) {
            if (in_array($line, array(
                'Content-Type: image/gif',
                'Content-Type: image/jpeg',
                'Content-Type: image/png',
                'Content-Type: image/tiff'
            ))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parses the response to check for cookies and returns the response
     * stripped from headers
     *
     * @param $response the received response
     *
     * @return the response striped from headers
     *
     */
    private function parseResponse($response)
    {
        $lines = explode("\r\n", $response);

        if ($this->cookieHandler != null) {
            foreach ($lines as $line) {
                if ($this->isCookieLine($line)) {
                    $this->parseCookieLine($line);
                }
            }
        }

        return $lines[count($lines) - 1];
    }

    /**
     * Checks if a line of the response header
     * corresponds to a cookie definition
     *
     * @param $line a line from the received response
     *
     * @return true if the line corresponds to a cookie
     *
     */
    private function isCookieLine($line)
    {
        // TODO: make this easier to read
        return strncasecmp($line, 'Set-Cookie: ', strlen('Set-Cookie: ')) === 0;
    }

    /**
     * Parses a line of the response to find cookie name and value.
     * Is stores the cookie using the cookie handler
     *
     * @param $line a line from the received response
     *
     */
    private function parseCookieLine($line)
    {
        $cookie = (object) array(
            'name' => '',
            'value' => '',
            'expires' => 0,
            'path' => '',
            'domain' => '',
            'secure' => false,
            'httpOnly' => false
        );

        $cookieParts = explode(';', substr($line, strlen('Set-Cookie: ')));

        foreach ($cookieParts as $cookiePart) {
            $isExpired = false;
            $cookiePart = trim($cookiePart);
            $pair = explode('=', $cookiePart);
            switch ($pair[0]) {
                case 'path':
                    $cookie->path = $pair[1];
                    break;
                case 'HttpOnly':
                    $cookie->httpOnly = true;
                    break;
                case 'secure':
                    $cookie->secure = true;
                    break;
                case 'expires':
                    $expiresValue = $pair[1];
                    if ($expiresValue != 0) {
                        $cookie->expires = DateTime::createFromFormat('D, d-M-Y H:i:s T', $expiresValue);
                    }
                    break;
                default:
                    if ($cookie->name === '') {
                        $cookie->name = $pair[0];
                        $cookie->value = $pair[1];
                    } else {
                        // we already have a name and value for the cookie.
                        // this means this is another cookie field we don't usually
                        // care about
                        $cookie->{$pair[0]} = $pair[1];
                    }
            }
        }

        if ($this->cookieShouldBeDeleted($cookie)) {
            $this->cookieHandler->deleteCookie($cookie->name);
        } else {
            $this->cookieHandler->storeCookie($cookie);
        }
    }

    /**
     * Checks if all mandatory fields of the request are set before
     * sending it
     *
     * @return false if the target url is not set
     *
     */
    private function checkMandatoryProperties()
    {
        if ($this->url == null) {
            throw RpcException('Request url is not defined');
        }
    }

    /**
     * Checks if an specific cookie is expired or with a value='deleted'
     *
     * @return true if the cookie should be deleted, false otherwise
     *
     */
    private function cookieShouldBeDeleted($cookie) {
        $now = new DateTime();
        $isExpired = isset($cookie->expires) &&
                     $cookie->expires !== 0 &&
                     ($cookie->expires->getTimestamp() < $now->getTimestamp());
                     //cookie expires is previous to current time

        return $isExpired || ($cookie->value === 'deleted');
    }
}
