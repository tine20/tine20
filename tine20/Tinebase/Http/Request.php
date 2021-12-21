<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Class Tinebase_Http_Request
 *
 * NOTE: ^%*&* Zend\Http\PhpEnvironment\Request can't cope with input streams
 *       which leads to waste of mem e.g. on large file upload via PUT (WebDAV)
 */
class Tinebase_Http_Request extends Laminas\Http\PhpEnvironment\Request
{
    /**
     * @var resource
     */
    protected $_inputStream;

    /**
     * @var string
     */
    protected $_remoteAddress;

    /**
     * @param bool $rewind
     * @return bool|resource
     */
    public function getContentStream($rewind = true)
    {
        if (! $this->_inputStream) {
            if (! empty($this->content)) {
                $this->_inputStream = fopen('php://temp', 'r+');
                fputs($this->_inputStream, $this->content);
            } else {
                // NOTE: as of php 5.6 php://input can be rewinded ... but for POST only and this is even SAPI dependend
                $this->_inputStream = fopen('php://temp', 'r+');
                stream_copy_to_stream(fopen('php://input', 'r'), $this->_inputStream);
            }
        }

        if ($rewind) {
            rewind($this->_inputStream);
        }

        return $this->_inputStream;
    }

    /**
     * Get raw request body
     *
     * @return string
     */
    public function getContent()
    {
        if (empty($this->content)) {
            $requestBody = stream_get_contents($this->getContentStream(true));
            rewind($this->_inputStream);
            if (strlen($requestBody) > 0) {
                $this->content = $requestBody;
            }
        }

        return $this->content;
    }

    /**
     * @return mixed|\Zend\Stdlib\ParametersInterface
     */
    public function getRemoteAddress()
    {
        // get trusted proxies from config
        if (! $this->_remoteAddress) {
            $this->_remoteAddress = $this->getServer('REMOTE_ADDR');
            $trustedProxies = Tinebase_Config::getInstance()->get(Tinebase_Config::TRUSTED_PROXIES);
            if (is_array($trustedProxies) && in_array($this->getServer('REMOTE_ADDR'), $trustedProxies)) {
                $forwardedFor = $this->getServer('HTTP_X_FORWARDED_FOR');
                if ($forwardedFor) {
                    // set forwarded-for as real remote address
                    $this->_remoteAddress = $this->getServer('HTTP_X_FORWARDED_FOR');
                    // TODO set PROXY_ADDR?
                    // $_SERVER['PROXY_ADDR'] = $_SERVER['REMOTE_ADDR'];
                }
            }
        }

        return $this->_remoteAddress;
    }
}
