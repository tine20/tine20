<?php declare(strict_types=1);
/**
 * Tine 2.0
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

class Tinebase_Server_ZendReflectionClassWrapper extends Zend_Server_Reflection_Class
{
    /**
     * Constructor
     *
     * Create array of dispatchable methods, each a
     * {@link Zend_Server_Reflection_Method}. Sets reflection object property.
     *
     * @param ReflectionClass $reflection
     * @param string $namespace
     * @param mixed $argv
     * @return void
     */
    public function __construct(ReflectionClass $reflection, $namespace = null, $argv = false)
    {
        $this->_reflection = $reflection;
        $this->setNamespace($namespace);

        foreach ($reflection->getMethods() as $method) {
            // Don't aggregate magic methods
            if ('__' == substr($method->getName(), 0, 2)) {
                continue;
            }

            if ($method->isPublic()) {
                // Get signatures and description
                $this->_methods[] = new Tinebase_Server_ZendReflectionMethodWrapper($this, $method, $this->getNamespace(), $argv);
            }
        }
    }
}
