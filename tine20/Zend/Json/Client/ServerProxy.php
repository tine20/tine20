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
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * The namespace decorator enables object chaining to permit
 * calling JSON-RPC namespaced functions like "foo.bar.baz()"
 * as "$remote->foo->bar->baz()".
 *
 * @category   Zend
 * @package    Zend_Json
 * @subpackage Client
 * @author     Lars Kneschke <l.kneschke@metaways.de>
 * @copyright  Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright  Copyright (c) 2005-2009 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Json_Client_ServerProxy
{
    /**
     * @var Zend_Json_Client
     */
    private $_client = null;

    /**
     * @var string
     */
    private $_namespace = '';


    /**
     * @var array of Zend_Json_Client_ServerProxy
     */
    private $_cache = array();


    /**
     * Class constructor
     *
     * @param string             $namespace
     * @param Zend_Json_Client $client
     */
    public function __construct($client, $namespace = '')
    {
        $this->_namespace = $namespace;
        $this->_client    = $client;
    }


    /**
     * Get the next successive namespace
     *
     * @param string $name
     * @return Zend_Json_Client_ServerProxy
     */
    public function __get($namespace)
    {
        $namespace = ltrim("$this->_namespace.$namespace", '.');
        if (!isset($this->_cache[$namespace])) {
            $this->_cache[$namespace] = new $this($this->_client, $namespace);
        }
        return $this->_cache[$namespace];
    }


    /**
     * Call a method in this namespace.
     *
     * @param  string $methodN
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $method = ltrim("$this->_namespace.$method", '.');
        return $this->_client->call($method, $args);
    }
}
