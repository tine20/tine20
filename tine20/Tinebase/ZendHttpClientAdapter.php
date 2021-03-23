<?php declare(strict_types=1);
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class Tinebase_ZendHttpClientAdapter extends Zend_Http_Client_Adapter_Test
{
    public $lastRequestBody = '';
    public $writeBodyCallBack = null;

    public function write($method, $uri, $http_ver = '1.1', $headers = array(), $body = '')
    {
        $this->lastRequestBody = $body;

        if (is_callable($this->writeBodyCallBack)) {
            ($this->writeBodyCallBack)($body);
        }

        return parent::write($method, $uri, $http_ver, $headers, $body);
    }
}
