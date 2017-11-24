<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Expressive
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

/**
 * Tinebase Expressive Route Handler
 *
 * use the toArray method and put the result as route handler into FastRoute
 * use the fromArray method with the data received as route handler from FastRoute
 *
 * as for each route one "instance" will be cached in FastRoute (using var_export), the most efficient method is
 * to use a plain and simple array.
 *
 * @package     Tinebase
 * @subpackage  Expressive
 */
class Tinebase_Expressive_RouteHandler
{
    const IS_PUBLIC = 'isPublic';
    const CLASS_NAME = 'class';
    const METHOD = 'method';

    /**
     * @var array
     */
    protected $_vars = null;

    protected $_isPublic = false;

    protected $_class = null;
    protected $_method = null;
    protected $_methodParams = [];
    protected $_isStatic = false;
    protected $_hasGetInstance = true;

    /**
     * Tinebase_Expressive_RouteHandler constructor.
     * @param string $class
     * @param string $method
     * @param array $_options
     */
    public function __construct($class, $method, array $_options = [])
    {
        if (isset($_options[self::IS_PUBLIC])) {
            $this->_isPublic = (bool) $_options[self::IS_PUBLIC];
        }

        $this->_class = $class;
        $this->_method = $method;
        $reflection = new ReflectionMethod($class, $method);
        $this->_methodParams = $reflection->getParameters();
        $this->_isStatic = $reflection->isStatic();
        $this->_hasGetInstance = method_exists($class, 'getInstance');
    }

    public function toArray()
    {
        return [
            self::CLASS_NAME        => $this->_class,
            self::METHOD            => $this->_method,
            self::IS_PUBLIC         => $this->_isPublic,
        ];
    }

    /**
     * @param array $data
     * @return Tinebase_Expressive_RouteHandler
     */
    public static function fromArray(array $data)
    {
        $className = static::class;
        $instance = new $className($data[self::CLASS_NAME], $data[self::METHOD], $data);
        return $instance;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->_isPublic;
    }

    /**
     * @param array $array
     */
    public function setVars(array $array)
    {
        $this->_vars = $array;
    }

    /**
     * @return string
     */
    public function getApplicationName()
    {
        return current(explode('_', $this->_class));
    }

    /**
     * @return array
     *
    public function getVars()
    {
        return $this->_vars;
    }*/

    /**
     * @return mixed
     * @throws Tinebase_Exception_InvalidArgument
     */
    public function dispatch()
    {
        $orderedParams = [];
        foreach ($this->_methodParams as $refParam) {
            $refParamName = $refParam->getName();
            if (isset($this->_vars[$refParamName])) {
                // TODO: $refParam->getClass() -> create instance
                $orderedParams[$refParamName] = $this->_vars[$refParamName];
            } elseif ($refParam->isOptional()) {
                $orderedParams[$refParamName] = $refParam->getDefaultValue();
            } else {
                // TODO: $refParam->getClass() -> check for factory maybe?
                // TODO: dependency injection!
                throw new Tinebase_Exception_InvalidArgument($this->_class . '::' . $this->_method
                    . ' is missing required parameter: ' . $refParam->getName());
            }
        }

        if ($this->_isStatic) {
            $callable = [$this->_class, $this->_method];
        } elseif ($this->_hasGetInstance) {
            $callable = [call_user_func([$this->_class, 'getInstance']), $this->_method];
        } else {
            $callable = [new $this->_class, $this->_method];
        }

        return call_user_func_array($callable, $orderedParams);
    }
}