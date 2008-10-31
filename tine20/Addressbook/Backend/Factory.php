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
     * object instance
     *
     * @var Addressbook_Backend_Factory
     */
    private static $_instance = NULL;
    
    /**
     * backend object instances
     */
    private static $_backends = array();
    
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
     * constant for LDAP contacts backend class
     *
     */
    const SALUTATION = 'salutation';

    /**
     * factory function to return a selected contacts backend class
     *
     * @param   string $_type
     * @return  Tinebase_Application_Backend_Interface
     * @throws  Addressbook_Exception_InvalidArgument if unsupported type was given
     */
    static public function factory ($_type)
    {
        switch ($_type) {
            case self::SQL:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Addressbook_Backend_Sql();
                }
                $instance = self::$_backends[$_type];
                break;            
            case self::LDAP:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Addressbook_Backend_Ldap();
                }
                $instance = self::$_backends[$_type];
                break;            
            case self::SALUTATION:
                if (!isset(self::$_backends[$_type])) {
                    self::$_backends[$_type] = new Addressbook_Backend_Salutation();
                }
                $instance = self::$_backends[$_type];
                break;            
            default:
                throw new Addressbook_Exception_InvalidArgument('Unknown backend type (' . $_type . ').');
                break;
        }
        return $instance;
    }
}    
