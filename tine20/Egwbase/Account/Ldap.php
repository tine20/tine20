<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Accounts
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Account ldap backend
 * 
 * @package     Tinebase
 * @subpackage  Accounts
 */
class Tinebase_Account_Ldap implements Tinebase_Account_Interface
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
     * @var Tinebase_Account_Ldap
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Account_Ldap
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Account_Ldap;
        }
        
        return self::$instance;
    }
    
}