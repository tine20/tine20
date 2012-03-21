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
 * @subpackage Server
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Zend_Server_Interface
 */
require_once 'Zend/Server/Interface.php';

/**
 * Zend_Server_Reflection
 */
require_once 'Zend/Server/Reflection.php';

/**
 * Zend_Json_Server_Exception
 */
require_once 'Zend/Json/Server/Exception.php';

/**
 * Zend_Server_Abstract
 */
require_once 'Zend/Server/Abstract.php';

/**
 * Zend_Json
 */
require_once 'Zend/Json.php';

/**
 * @category   Zend
 * @package    Zend_Http
 * @subpackage Server
 * @copyright  Copyright (c) 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Tinebase_Http_Server extends Zend_Server_Abstract implements Zend_Server_Interface {
    /**
     * @var Zend_Server_Reflection
     */
    private $_reflection = null;
    
    /**
     * Class Constructor Args
     */
    private $_args = array();
    
    /**
     * @var array An array of Zend_Server_Reflect_Method
     */
    private $_functions = array();
    
    /**
     * @var string Current Method
     */
    private $_method;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        set_exception_handler(array($this, "fault"));
        $this->_reflection = new Zend_Server_Reflection();
    }

    /**
     * Implement Zend_Server_Interface::handle()
     * 
     * the called functions output the generated content directly themself
     *
     * @param array $request
     */
    public function handle($request = false)
    {
        if (!$request) {
            $request = $_REQUEST;
        }
        if (isset($request['method'])) {
            $this->_method = $request['method'];
            if (isset($this->_functions[$this->_method])) {
                if ($this->_functions[$this->_method] instanceof Zend_Server_Reflection_Function || $this->_functions[$this->_method] instanceof Zend_Server_Reflection_Method && $this->_functions[$this->_method]->isPublic()) {
                    $request_keys = array_keys($request);
                    array_walk($request_keys, array(__CLASS__, "lowerCase"));
                    $request = array_combine($request_keys, $request);
                    
                    $func_args = $this->_functions[$this->_method]->getParameters();
                    
                    $calling_args = array();
                    foreach ($func_args as $arg) {
                        if (isset($request[strtolower($arg->getName())])) {
                            $calling_args[] = $request[strtolower($arg->getName())];
                        }
                    }
                    
                    foreach ($request as $key => $value) {
                        if (substr($key, 0, 3) == 'arg') {
                            $key = str_replace('arg', '', $key);
                            $calling_args[$key]= $value;
                        }
                    }
                    
                    if (sizeof($calling_args) < sizeof($func_args)) {
                        throw new Zend_Json_Server_Exception('Invalid Method Call to ' .$this->_method. '. Requires ' .sizeof($func_args). ', ' .sizeof($calling_args). ' given.', 400);
                    }
                    
                    if ($this->_functions[$this->_method] instanceof Zend_Server_Reflection_Method) {
                        // Get class
                        $class = $this->_functions[$this->_method]->getDeclaringClass()->getName();
        
                        if ($this->_functions[$this->_method]->isStatic()) {
                            // for some reason, invokeArgs() does not work the same as 
                            // invoke(), and expects the first argument to be an object. 
                            // So, using a callback if the method is static.
                            $result = call_user_func_array(array($class, $this->_functions[$this->_method]->getName()), $calling_args);
                        }
        
                        // Object methods
                        try {
                            if ($this->_functions[$this->_method]->getDeclaringClass()->getConstructor()) {
                                $object = $this->_functions[$this->_method]->getDeclaringClass()->newInstanceArgs($this->_args);
                            } else {
                                $object = $this->_functions[$this->_method]->getDeclaringClass()->newInstance();
                            }
                        } catch (Exception $e) {
                            throw new Zend_Json_Server_Exception('Error instantiating class ' . $class . ' to invoke method ' . $this->_functions[$this->_method]->getName(), 500);
                        }
                        
                        // the called function generates the needed output
                        $this->_functions[$this->_method]->invokeArgs($object, $calling_args);
                    } else {
                        // the called function generates the needed output
                        call_user_func_array($this->_functions[$this->_method]->getName(), $calling_args); //$result = $this->_functions[$this->_method]->invokeArgs($calling_args);
                    }
                    
                } else {
                    throw new Zend_Json_Server_Exception("Unknown Method '$this->_method'.", 400);
                }
            } else {
                throw new Zend_Json_Server_Exception("Unknown Method '$this->_method'.", 400);
            }
        } else {
            throw new Zend_Json_Server_Exception("No Method Specified.", 404);
        }
    }
    
    /**
     * Implement Zend_Server_Interface::setClass()
     *
     * @param string $classname Class name
     * @param string $namespace Class namespace (unused)
     * @param array $argv An array of Constructor Arguments
     */
    public function setClass($classname, $namespace = '', $argv = array())
    {
        $this->_args = $argv;
        foreach ($this->_reflection->reflectClass($classname, $argv)->getMethods() as $method) {
            $prefix = ($namespace === '' ? $classname : $namespace);
            $this->_functions[$prefix .'.'. $method->getName()] = $method;
        }
    }
    
    /**
     * Implement Zend_Server_Interface::fault()
     *
     * @param string $fault Message
     * @param int $code Error Code
     */
    public function fault($exception = null, $code = null)
    {
        if (isset($this->_functions[$this->_method])) {
            $function = $this->_functions[$this->_method];
        } else {
            $function = $this->_method;
        }
        
        if ($function instanceof Zend_Server_Reflection_Method) {
            $class = $function->getDeclaringClass()->getName();
        } else {
            $class = false;
        }
        
        if ($function instanceof Zend_Server_Reflection_Method) {
            $method = $function->getName();
        } else {
            $method = $function;
        }
        
        $error['msg'] = "Call to $method failed.";
        $error['code'] = 404;
        
        if ($exception instanceof Exception) {
            $error['msg'] = $exception->getMessage();
            $error['code'] = $exception->getCode();
            $error['trace'] = $exception->getTrace();
        } elseif (!is_null($exception)) {
            $error['msg'] = $exception;
        }
        
        if (!is_null($code)) {
            $error['code'] = $code;
        }
        
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            if (is_null($code)) {
                header("HTTP/1.0 400 Bad Request");
            } else {
                if ($code == 404) {
                    header("HTTP/1.0 $code File Not Found");
                } else {
                    header("HTTP/1.0 $code Bad Request");
                }
            }
        }
        echo "<pre>";
        var_dump($error);
        echo "</pre>";
    }
    
    /**
     * Implement Zend_Server_Interface::addFunction()
     *
     * @param string $function Function Name
     * @param string $namespace Function namespace (unused)
     */
    public function addFunction($function, $namespace = '')
    {
        if (!is_array($function)) {
            $function = (array) $function;
        }
        
        foreach ($function as $func) {
            if (is_callable($func) && !in_array($func, self::$magic_methods)) {
                $this->_functions[$func] = $this->_reflection->reflectFunction($func);
            } else {
                throw new Zend_Json_Server_Exception("Invalid Method Added to Service.");
            }
        }
    }
    
    /**
     * Implement Zend_Server_Interface::getFunctions()
     *
     * @return array An array of Zend_Server_Reflection_Method's
     */
    public function getFunctions()
    {
        return $this->_functions;
    }
    
    /**
     * Implement Zend_Server_Interface::loadFunctions()
     *
     * @todo Implement
     * @param array $functions
     */
    public function loadFunctions($functions)
    {
        
    }
    
    /**
     * Implement Zend_Server_Interface::setPersistence()
     * 
     * @todo Implement
     * @param int $mode
     */
    public function setPersistence($mode)
    {
        
    }

    /**
     * Map PHP type to protocol type
     * -> we need to implement that because it is declared abstract in Zend_Server_Abstract
     * -> is needed for ZF 1.7
     * 
     * @param  string $type 
     * @return string
     * 
     */
    protected function _fixType($type)
    {
        return $type;
    }
    
    /**
     * Lowercase a string
     *
     * Lowercase's a string by reference
     * -> moved here from Zend_Server_Abstract because it is marked as deprecated there
     * 
     * @param  string $string value
     * @param  string $key
     * @return string Lower cased string
     */
    public static function lowerCase(&$value, &$key)
    {
        return $value = strtolower($value);
    }    
}
