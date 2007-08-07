<?php
/**
 * main authentication class
 * 
 * @author Lars Kneschke <l.kneschke@metaways.de>
 * @package Egwbase
 *
 */
class Egwbase_Auth
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'Sql';
    
    /**
     * constant for LDAP contacts backend class
     *
     */
    const LDAP = 'Ldap';
    
    /**
     * factory function to return a selected authentication backend class
     *
     * @param string $type
     * @return object
     */
    static public function factory($type)
    {
        $className = Addressbook_Contacts_.$type;
        $instance = new $className();
        
        //throw new Exception('unknown type');
        
        return $instance;
    }
}
?>