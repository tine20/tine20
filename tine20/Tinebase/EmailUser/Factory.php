<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  EmailUser
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009-2015 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 *
 */

/**
 * class Tinebase_EmailUser_Factory
 *
 * @package    Tinebase
 * @subpackage EmailUser
 */
class Tinebase_EmailUser_Factory
{
    /**
     * 
     * @var Tinebase_Model_Application
     */
    protected static $_emailApplication = NULL;
    
    /**
     * Return name of configured mail application
     * 
     * @return string
     */
    public static function getMailApplicationName()
    {
        if (NULL === self::$_emailApplication){
            $applications = Tinebase_Core::getUser()->getApplications();
            
            foreach (array('Felamimail', 'Expressomail') as $emailApplication) {
                if (self::$_emailApplication = $applications->find('name', $emailApplication)) {
                    break;
                }
            }
        }
        
        return self::$_emailApplication->name;
    }
    
    /**
     * if class could be for instance Felamimail_Controller_Account,
     * namespaceWithoutBasePrefix is only Controller_Account
     * class must implements getInstance() method
     *
     * @param string $namespaceWithoutBasePrefix
     * @return mixed
     */
    public static function getInstance($namespaceWithoutBasePrefix)
    {
        $class = self::getMailApplicationName() . '_' . $namespaceWithoutBasePrefix;
        
        return $class::getInstance();
    }
    
    /**
     * if class could be for instance Felamimail_Controller_Account,
     * namespaceWithoutBasePrefix is only Controller_Account
     * class must implements getInstance() method

     * @param string $namespaceWithoutBasePrefix
     * @param string $name constant name
     * @return mixed
     */
    public static function getConstant($namespaceWithoutBasePrefix, $name)
    {
        $class = self::getMailApplicationName() . '_' . $namespaceWithoutBasePrefix;
        
        return constant($class . '::' . $name);
    }
    
    /**
     * Calls a static method from email application
     *
     * if class could be for instance Felamimail_Controller_Account,
     * namespaceWithoutBasePrefix is only Controller_Account
     * class must implements getInstance() method
     * @param string $namespaceWithoutBasePrefix
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function callStatic($namespaceWithoutBasePrefix, $method, array $args)
    {
        $class = self::getMailApplicationName() . '_' . $namespaceWithoutBasePrefix;
        
        if (!class_exists($class)) {
            $class = 'Tinebase_' . $namespaceWithoutBasePrefix;
        }
        
        return call_user_func_array(array($class, $method), $args);
    }
    
    /**
     * Adds the properly filter according e-mail application
     * @param Tinebase_DateTime $received
     */
    public static function getPeriodFilter($received)
    {
        $mailApplication = self::getMailApplicationName();
        
        if ($mailApplication !== 'Felamimail') {
            $class = $mailApplication . '_Model_Filter_DateTime';
            return new $class('received', 'after', $received->get(Tinebase_Record_Abstract::ISO8601LONG));
        } else {
            return new Tinebase_Model_Filter_DateTime('received', 'after', $received->get(Tinebase_Record_Abstract::ISO8601LONG));
        }
    }
}