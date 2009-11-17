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
 * @category   Zend
 * @package    Zend_Json
 * @subpackage Server
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Json_Client_SMD
{
    /**
     * @var array json decoded SMD response
     */
    protected $_smd = null;
    
    /**
     * load and decode json encoded SMD response
     * 
     * @param string $json json encoded SMD response
     * @return void
     */
    public function loadJson($json)
    {
        $this->_smd = Zend_Json_Decoder::decode($json);
    }
    
    /**
     * return method signature for requested method
     * 
     * @param string $method name of the method
     * @return array method signature
     * @throws Zend_Json_Client_IntrospectException
     */
    public function getMethodSignature($method)
    {
        if(!array_key_exists($method, $this->_smd['services'])) {
            /**
             * Exception thrown when method not found
             * @see Zend_Json_Client_IntrospectException
             */
            require_once 'Zend/Json/Client/IntrospectException.php';
            throw new Zend_Json_Client_IntrospectException("method $method not found in smd");
        }
        
        return $this->_smd['services'][$method];
    }
}