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
 * @package    Zend_Json
 * @subpackage Server
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * @category   Zend
 * @package    Zend_Json
 * @subpackage Server
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Json_Client_Response extends Zend_Json_Server_Response
{
    public function loadJson($json)
    {
        #echo "JSON: $json" . PHP_EOL;
        $response = Zend_Json_Decoder::decode($json);
        #var_dump($response);
        
        if(array_key_exists('error', $response)) {
            $this->setError(new Zend_Json_Server_Error(
                $response['error']['message'],
                $response['error']['code'],
                $response['error']['data']
            ));
        } else {
            $this->setResult($response['result']);
        }
        
        if(array_key_exists('jsonrpc', $response)) {
            $this->setVersion($response['jsonrpc']);
        }
        
        if(array_key_exists('id', $response)) {
            $this->setId($response['jsonrpc']);
        }
    }
}