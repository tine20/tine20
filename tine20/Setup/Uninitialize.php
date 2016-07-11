<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @subpackage  Uninitialize
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2016 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Class to handle application uninitialization
 *
 * @package     Setup
 * @subpackage  Uninitialize
 */
class Setup_Uninitialize
{
    /**
     * Call {@see _uninitialize} on an instance of the concrete Setup_Uninitialize class for the given {@param $_application}
     *
     * @param Tinebase_Model_Application $_application
     * @param array | optional $_options
     * @return void
     */
    public static function uninitialize(Tinebase_Model_Application $_application, $_options = null)
    {
        $applicationName = $_application->name;
        $classname = "{$applicationName}_Setup_Uninitialize";
        if (true !== class_exists($classname)) {
            return;
        }
        $instance = new $classname;

        Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Initializing application: ' . $applicationName);

        $instance->_uninitialize($_application, $_options);
    }

    /**
     * uninitialize application
     *
     * @param Tinebase_Model_Application $_application
     * @param array | optional $_options
     * @return void
     */
    protected function _uninitialize(Tinebase_Model_Application $_application, $_options = null)
    {
        $reflectionClass = new ReflectionClass($this);
        $methods = $reflectionClass->getMethods();
        foreach ($methods as $method) {
            $methodName = $method->name;
            if (strpos($methodName, '_uninitialize') === 0 && $methodName !== '_uninitialize') {
                Setup_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Calling uninit function ' . get_class($this) . '::' . $methodName);

                $this->$methodName($_application, $_options);
            }
        }
    }
}