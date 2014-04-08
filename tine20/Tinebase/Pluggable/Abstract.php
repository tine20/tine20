<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Pluggable
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2014 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 *
 */

/**
 * Abstract class for allowing dependency injection through a plugin
 * architecture
 * Use the file init_plugins.php for registering plugins for a class
 *
 * @package Tinebase
 * @subpackage Pluggable
 */
abstract class Tinebase_Pluggable_Abstract
{

    /**
     * Plugins for this class family
     * Contains:
     * '[method]' => '[Complete_Name_Of_Class]'
     * 
     * @var array
     */
    protected static $_plugins = array();

    /**
     * Attaches a plugin
     * Path for classes must be into include_path
     * 
     * @param string $method
     * @param string $namespace
     */
    public static function attachPlugin($method, $namespace)
    {
        self::$_plugins[$method] = $namespace;
        Zend_Loader_Autoloader::getInstance()->registerNamespace(
            current(explode('_', $namespace)));
    }

    /**
     * Attaches several plugins from a same path
     * $methods must contain:
     * '[method]' => '[Complete_Name_Of_Class]'
     *
     * @param array $methods
     * @param string $namespace
     */
    public static function attachPlugins(array $methods, $namespace)
    {
        foreach ($methods as $method => $class) {
            self::$_plugins[$method] = $class;
        }
        Zend_Loader_Autoloader::getInstance()->registerNamespace(
            current(explode('_', $namespace)));
    }

    /**
     * Dependency injection
     * Calling of plugins
     * 
     * @param string $method            
     * @param array $args            
     */
    public function __call($method, array $args)
    {
        if (isset(self::$_plugins[$method])) {
            $class = self::$_plugins[$method];
            $plugin = new $class();
            return call_user_func_array(array(
                $plugin,
                $method
            ), $args);
        } else {
            throw new Tinebase_Exception(
                'Plugin ' . $method . ' was not found in haystack');
        }
    }
}
