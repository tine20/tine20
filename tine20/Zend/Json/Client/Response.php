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
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */

/**
 * Json-RPC Response
 * @see Zend_Json_Server_Response
 */
require_once 'Zend/Json/Server/Response.php';

/**
 * @category   Zend
 * @package    Zend_Json
 * @subpackage Server
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Json_Client_Response extends Zend_Json_Server_Response
{
    /**
     * initialize JSON-RPC object with JSON response from server
     * @param string $json the json encoded response
     * @return void
     */
    public function loadJson($json)
    {
        $response = Zend_Json::decode($json);
        
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