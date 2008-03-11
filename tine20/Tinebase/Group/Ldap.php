<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * Group ldap backend
 * 
 * @package     Tinebase
 * @subpackage  Group
 */
class Tinebase_Group_Ldap implements Tinebase_Group_Interface
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
     * @var Tinebase_Group_Ldap
     */
    private static $instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Tinebase_Group_Ldap
     */
    public static function getInstance() 
    {
        if (self::$instance === NULL) {
            self::$instance = new Tinebase_Group_Ldap;
        }
        
        return self::$instance;
    }
}