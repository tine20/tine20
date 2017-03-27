<?php
/**
 * Provides method to test protected methods
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
trait GetProtectedMethodTrait
{
    /**
     * GetProtectedMethod constructor.
     * @param $object
     * @param $method
     * @return ReflectionMethod
     */
    public function getProtectedMethod($object, $method)
    {
        $class = new ReflectionClass($object);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }
}