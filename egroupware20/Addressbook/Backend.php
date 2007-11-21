<?php
/**
 * backend factory class for the addressbook
 * 
 * a instance of the addressbook backendclass should be created using this class
 * 
 * $contacts = Addressbook_Backend::factory(Addressbook_Backend::$type);
 * 
 * currently implemented backend classes: Addressbook_Backend::Sql
 * 
 * currently planned backend classed: Addressbook_Backend::Ldap
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$ *
 */
class Addressbook_Backend
{
    /**
     * constant for Sql contacts backend class
     *
     */
    const SQL = 'sql';
    
    /**
     * constant for LDAP contacts backend class
     *
     */
    const LDAP = 'ldap';
    
    const PERSONAL = 'personal';
    
    const SHARED = 'shared';
    
    /**
     * factory function to return a selected contacts backend class
     *
     * @param string $type
     * @return object
     */
    static public function factory($type)
    {
        switch($type) {
            case self::SQL:
            case self::LDAP:
                $className = Addressbook_Backend_ . ucfirst($type);
                $instance = new $className();
                break;
                
            default:
                throw new Exception('unknown type');
        }

        return $instance;
    }
    
}    
