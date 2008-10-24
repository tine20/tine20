<?php
/**
 * backend factory class for the addressbook
 * 
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
/**
 * backend factory class for the addressbook
 * 
 * An instance of the addressbook backendclass should be created using this class
 * $contacts = Addressbook_Backend_Factory::factory(Addressbook_Backend::$type);
 * currently implemented backend classes: Addressbook_Backend_Factory::Sql
 * currently planned backend classed: Addressbook_Backend_Factory::Ldap
 * 
 * @package     Addressbook
 */
class Addressbook_Backend_Factory
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
    /**
     * factory function to return a selected contacts backend class
     *
     * @param string $type
     * @return Tinebase_Application_Backend_Interface
     */
    static public function factory ($type)
    {
        switch ($type) {
            case self::SQL:
                $instance = Addressbook_Backend_Sql::getInstance();
                break;
            case self::LDAP:
                $instance = Addressbook_Backend_Ldap::getInstance();
                break;
            default:
                throw new Exception('unknown type');
                break;
        }
        return $instance;
    }
}    
