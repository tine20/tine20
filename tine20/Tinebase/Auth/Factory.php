<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id: Auth.php 390 2007-12-03 15:29:54Z nelius_weiss $
 */

/**
 * authentication backend factory class
 *  
 * @package     Tinebase
 * @subpackage  Auth
 */

class Tinebase_Auth_Factory
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
     * @return Zend_Auth_Adapter_Interface
     */
    static public function factory($_type)
    {
        switch($_type) {
            case self::LDAP:
                $options = Zend_Registry::get('configFile')->authentication->toArray();
                unset($options['backend']);

                $instance = new Tinebase_Auth_Ldap($options);
                break;
                
            case self::SQL:
                $instance = new Tinebase_Auth_Sql(
                    Zend_Registry::get('dbAdapter'),
                    SQL_TABLE_PREFIX . 'accounts',
                    'login_name',
                    'password',
                    'MD5(?)'
                );
                break;
                
            default:
                throw new Exception('unknown authentication backend');
                break;
        }
        
        return $instance;
    }
}
