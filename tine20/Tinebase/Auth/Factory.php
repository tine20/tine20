<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Auth
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2007-2010 Metaways Infosystems GmbH (http://www.metaways.de)
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
     * @param   string $type
     * @return  Tinebase_Auth_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     */
    static public function factory($_type)
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
                
            case Tinebase_Auth::IMAP:
                $options = array(Tinebase_Auth::getBackendConfiguration());
                $instance = new Tinebase_Auth_Imap($options);
                break;
                
            default:
                throw new Tinebase_Exception_InvalidArgument('Unknown authentication backend');
                break;
        }
        
        return $instance;
    }
}
