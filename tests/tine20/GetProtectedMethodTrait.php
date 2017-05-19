<?php
/**
 * Provides method to test protected methods
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Michael Spahn <m.spahn@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 * usage:
 *
 * 1) add trait:
 *
 *    use GetProtectedMethodTrait;
 *
 * 2) use trait:
 *
 *    $reflectionMethod = $this->getProtectedMethod(Felamimail_Model_Message::class, 'sanitizeMailAddress');
 *    $result = $reflectionMethod->invokeArgs(new Felamimail_Model_Message(), [$obfuscatedMail]);
 *
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