<?php declare(strict_types=1);
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Server_ZendJsonWrapper extends Zend_Json_Server
{
    /**
     * Register a class with the server
     *
     * @param string $class
     * @param string $namespace Ignored
     * @param mixed $argv Ignored
     * @return Zend_Json_Server
     * @throws Zend_Server_Exception
     * @throws Zend_Server_Reflection_Exception
     */
    public function setClass($class, $namespace = '', $argv = null)
    {
        $argv = null;
        if (3 < func_num_args()) {
            $argv = func_get_args();
            $argv = array_slice($argv, 3);
        }

        $reflection = new Tinebase_Server_ZendReflectionClassWrapper(new ReflectionClass($class), $namespace, $argv);

        foreach ($reflection->getMethods() as $method) {
            $definition = $this->_buildSignature($method, $class);
            $this->_addMethodServiceMap($definition);
        }
        return $this;
    }

    /**
     * Add service method to service map
     *
     * @param Zend_Server_Method_Definition $method
     * @return void
     * @throws Zend_Json_Server_Exception
     */
    protected function _addMethodServiceMap(Zend_Server_Method_Definition $method)
    {
        $serviceInfo = [
            'name'   => $method->getName(),
            'return' => $this->_getReturnType($method),
        ];
        $params = $this->_getParams($method);
        $serviceInfo['params'] = $params;

        $serviceMap = $this->getServiceMap();
        if (false !== $serviceMap->getService($serviceInfo['name'])) {
            $serviceMap->removeService($serviceInfo['name']);
        }
        $serviceInfo = new Tinebase_Server_ZendSmdServiceWrapper($serviceInfo);
        if ($method instanceof Tinebase_Server_ZendMethodDefinitionWrapper) {
            $serviceInfo->setApiTimeout($method->getApiTimeout());
        }
        $serviceMap->addService($serviceInfo);
    }

    /**
     * Build a method signature
     *
     * @param  Zend_Server_Reflection_Function_Abstract $reflection
     * @param  null|string|object $class
     * @return Tinebase_Server_ZendMethodDefinitionWrapper
     * @throws Zend_Server_Exception on duplicate entry
     */
    protected function _buildSignature(Zend_Server_Reflection_Function_Abstract $reflection, $class = null)
    {
        /** @var Tinebase_Server_ZendReflectionMethodWrapper $reflection */

        $ns         = $reflection->getNamespace();
        $name       = $reflection->getName();
        $method     = empty($ns) ? $name : $ns . '.' . $name;

        /** @phpstan-ignore-next-line */
        if (!$this->_overwriteExistingMethods && $this->_table->hasMethod($method)) {
            require_once 'Zend/Server/Exception.php';
            throw new Zend_Server_Exception('Duplicate method registered: ' . $method);
        }

        $definition = new Tinebase_Server_ZendMethodDefinitionWrapper();
        $definition->setApiTimeout($reflection->getApiTimeout())
            ->setName($method)
            ->setCallback($this->_buildCallback($reflection))
            ->setMethodHelp(/** @phpstan-ignore-line */ $reflection->getDescription())
            ->setInvokeArguments($reflection->getInvokeArguments());

        foreach ($reflection->getPrototypes() as $proto) {
            $prototype = new Zend_Server_Method_Prototype();
            $prototype->setReturnType($this->_fixType($proto->getReturnType()));
            foreach ($proto->getParameters() as $parameter) {
                $param = new Zend_Server_Method_Parameter([
                    'type'     => $this->_fixType($parameter->getType()),
                    'name'     => $parameter->getName(),
                    'optional' => $parameter->isOptional(),
                ]);
                if ($parameter->isDefaultValueAvailable()) {
                    $param->setDefaultValue($parameter->getDefaultValue());
                }
                $prototype->addParameter($param);
            }
            $definition->addPrototype($prototype);
        }
        if (is_object($class)) {
            $definition->setObject($class);
        }
        /** @phpstan-ignore-next-line */
        $this->_table->addMethod($definition);
        return $definition;
    }
}
