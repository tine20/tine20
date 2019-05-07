<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2016 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
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
     * factory function to return a selected authentication backend class
     *
     * @param   string $_type
     * @param   array $_options
     * @return  Tinebase_Auth_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     */
    static public function factory($_type, $_options = null)
    {
        switch($_type) {
            case Tinebase_Auth::LDAP:
                $options = array('ldap' => Tinebase_Auth::getBackendConfiguration()); //only pass ldap options without e.g. sql options
                $instance = new Tinebase_Auth_Ldap($options);
                break;
                
            case Tinebase_Auth::SQL:
                $instance = new Tinebase_Auth_Sql(
                    Tinebase_Core::getDb(),
                    SQL_TABLE_PREFIX . 'accounts',
                    'login_name',
                    'password',
                    'MD5(?)'
                );
                break;

            case Tinebase_Auth::SQL_EMAIL:
                $instance = new Tinebase_Auth_Sql(
                    Tinebase_Core::getDb(),
                    SQL_TABLE_PREFIX . 'accounts',
                    'email',
                    'password',
                    'MD5(?)',
                    true
                );
                break;

            case Tinebase_Auth::PIN:
                $instance = new Tinebase_Auth_Sql(
                    Tinebase_Core::getDb(),
                    SQL_TABLE_PREFIX . 'accounts',
                    'login_name',
                    'pin'
                );
                break;
                
            case Tinebase_Auth::IMAP:
                $instance = new Tinebase_Auth_Imap(
                    Tinebase_Auth::getBackendConfiguration()
                );
                break;
            
            case Tinebase_Auth::MODSSL:
                $instance = new Tinebase_Auth_ModSsl(
                    Tinebase_Auth::getBackendConfiguration()
                );
                break;
                
            default:
                // check if we have a Tinebase_Auth_$_type backend
                $authProviderClass = 'Tinebase_Auth_' . $_type;
                if (class_exists($authProviderClass)) {
                    $instance = new $authProviderClass($_options);
                } else {
                    throw new Tinebase_Exception_InvalidArgument('Unknown authentication backend');
                }
                break;
        }
        
        return $instance;
    }
}
