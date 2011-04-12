<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tests
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Jonas Fischer <j.fischer@metaways.de>
 */


class BaseTest extends PHPUnit_Framework_TestCase
{

    public static function main()
    {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite(get_class(self));
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Create proxy to the given {@param $className} that allows to test protected class methods
     * 
     * @param String $_className
     * @param Array | optional $_params
     * 
     * @return object [instance of the proxy class] 
     */
    public function getProxy($_className, array $_params = null)
    {
        $proxyClassName = "{$_className}Proxy";
     
        if (!class_exists($proxyClassName, false)) {
     
            $proxyClass = "
                class $proxyClassName extends $_className
                {
                    public function __call(\$function, \$args)
                    {
                        \$function = str_replace('proxy_', '_', \$function);
                        return call_user_func_array(array(\$this, \$function), \$args);
                    }
                }
            ";
            eval($proxyClass);
        }
     
        return new $proxyClassName($_params);
    }
}
