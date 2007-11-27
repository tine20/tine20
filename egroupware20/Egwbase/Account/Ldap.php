<?php
class Egwbase_Account_Ldap
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {}
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() {}

    /**
     * holdes the instance of the singleton
     *
     * @var Egwbase_Account_Ldap
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Egwbase_Account_Ldap
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Egwbase_Account_Ldap;
        }
        
        return self::$instance;
    }
    
}