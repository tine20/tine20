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
 * @subpackage Client
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Wraps the JSON-RPC SMD introspection methods
 *
 * @category   Zend
 * @package    Zend_Json
 * @subpackage Client
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Json_Client_ServerIntrospection
{
    /**
     * @var Zend_Json_Client
     */
    private $_client = null;

    /**
     * @var Zend_Json_Client_SMD
     */
    private $_smd = null;
    
    /**
     * @param Zend_Json_Client $client
     */
    public function __construct(Zend_Json_Client $client)
    {
        $this->_client = $client;
    }

    /**
     * Call system.methodSignature() for the given method
     *
     * @param  array  $method
     * @return array  array(array(return, param, param, param...))
     */
    public function getMethodSignature($method)
    {
        if($this->_smd === null) {
            $this->fetchSMD();
        }
        $signature = $this->_smd->getMethodSignature($method);
        
        return $signature;
    }

    public function setSMD(Zend_Json_Client_SMD $_smd)
    {
        $this->_smd = $_smd;
    }

    /**
     * Call system.listMethods()
     *
     * @param  array  $method
     * @return array  array(method, method, method...)
     */
    public function fetchSMD()
    {
        $request = new Zend_Json_Server_Request();
        $request->setVersion('2.0');
        $request->setId(1);
        
        $this->_smd = new Zend_Json_Client_SMD();
        $this->_client->doRequest($request, $this->_smd);
        
        return $this->_smd;        
    }
}
